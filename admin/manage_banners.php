<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$banners = $db->getDatabase()->banners;

// Handle form submission untuk menambah/mengupdate banner
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'update') {
            $bannerData = [
                'status' => $_POST['status'],
                'order' => (int)$_POST['order'],
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            // Validasi dan upload gambar jika ada
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $maxFileSize = 2 * 1024 * 1024; // 2MB
                
                $filename = $_FILES['image']['name'];
                $fileSize = $_FILES['image']['size'];
                $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Cek ukuran file
                if ($fileSize > $maxFileSize) {
                    $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 2MB.";
                    header('Location: manage_banners.php');
                    exit();
                }
                
                // Cek format file
                if (!in_array($fileExt, $allowed)) {
                    $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, PNG, atau WebP.";
                    header('Location: manage_banners.php');
                    exit();
                }
                
                // Cek dimensi gambar
                list($width, $height) = getimagesize($_FILES['image']['tmp_name']);
                $aspectRatio = $width / $height;
                
                // Toleransi untuk rasio aspek (16:9 ± 10%)
                $targetRatio = 16/9; // Rasio 16:9
                $tolerance = 0.1; // 10% toleransi
                
                if ($aspectRatio < ($targetRatio * (1 - $tolerance)) || $aspectRatio > ($targetRatio * (1 + $tolerance))) {
                    $_SESSION['warning'] = "Perhatian: Rasio gambar tidak ideal (16:9). Gambar mungkin akan terpotong atau memiliki ruang kosong.";
                }

                $targetDir = "../assets/banners/";
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = uniqid() . '.' . $fileExt;
                $targetFile = $targetDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $bannerData['image_path'] = 'assets/banners/' . $fileName;
                }
            }

            if ($_POST['action'] === 'add') {
                $bannerData['created_at'] = new MongoDB\BSON\UTCDateTime();
                $banners->insertOne($bannerData);
                $_SESSION['success'] = "Banner berhasil ditambahkan.";
            } else {
                $bannerId = new MongoDB\BSON\ObjectId($_POST['banner_id']);
                $banners->updateOne(
                    ['_id' => $bannerId],
                    ['$set' => $bannerData]
                );
                $_SESSION['success'] = "Banner berhasil diperbarui.";
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['banner_id'])) {
            $bannerId = new MongoDB\BSON\ObjectId($_POST['banner_id']);
            // Ambil data banner untuk hapus file
            $banner = $banners->findOne(['_id' => $bannerId]);
            if ($banner && isset($banner->image_path)) {
                $filePath = '../' . $banner->image_path;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $banners->deleteOne(['_id' => $bannerId]);
            $_SESSION['success'] = "Banner berhasil dihapus.";
        }
        header('Location: manage_banners.php');
        exit();
    }
}

// Ambil daftar banner
$bannerList = $banners->find([], ['sort' => ['order' => 1]]);

// Hitung statistik banner
$stats = [
    'total' => $banners->countDocuments(),
    'active' => $banners->countDocuments(['status' => 'active']),
    'inactive' => $banners->countDocuments(['status' => 'inactive'])
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Banner - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .banner-preview {
            width: 200px;
            height: 112.5px; /* Mengikuti rasio 16:9 (200 * 9/16) */
            object-fit: cover;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        .upload-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .upload-info ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Banner</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                        <i class="bi bi-plus-lg"></i> Tambah Banner
                    </button>
                </div>

                <!-- Statistik Banner -->
                <div class="row stats-row g-4">
                    <!-- Total Banner -->
                    <div class="col-md-4">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Banner</h6>
                                        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-images"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Aktif -->
                    <div class="col-md-4">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Banner Aktif</h6>
                                        <h5 class="mb-0"><?php echo $stats['active']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Nonaktif -->
                    <div class="col-md-4">
                        <div class="card bg-danger text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Banner Nonaktif</h6>
                                        <h5 class="mb-0"><?php echo $stats['inactive']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Banner -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Urutan</th>
                                        <th>Status</th>
                                        <th>Terakhir Diupdate</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bannerList as $banner): ?>
                                    <tr>
                                        <td>
                                            <img src="../<?php echo $banner->image_path; ?>" 
                                                 class="banner-preview" 
                                                 alt="Banner">
                                        </td>
                                        <td><?php echo $banner->order; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $banner->status === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $banner->status === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td class="text-muted">
                                            <?php 
                                            if (isset($banner->updated_at)) {
                                                echo date('d/m/Y H:i', $banner->updated_at->toDateTime()->getTimestamp());
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editBannerModal"
                                                        data-banner='<?php echo json_encode($banner); ?>'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="deleteBanner('<?php echo $banner->_id; ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Banner -->
    <div class="modal fade" id="addBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Gambar Banner</label>
                            <input type="file" class="form-control" name="image" required accept="image/jpeg,image/png,image/webp">
                            <div class="upload-info mt-2">
                                <strong>Panduan Upload Banner:</strong>
                                <ul>
                                    <li>Ukuran yang disarankan: 1920x1080 pixel</li>
                                    <li>Rasio aspek: 16:9</li>
                                    <li>Format: JPG, PNG, atau WebP</li>
                                    <li>Maksimal ukuran file: 2MB</li>
                                    <li>Area penting gambar sebaiknya di tengah</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="order" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Banner -->
    <div class="modal fade" id="editBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="banner_id" id="edit_banner_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Gambar Banner</label>
                            <input type="file" class="form-control" name="image">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="order" id="edit_order" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk mengisi form edit
        document.getElementById('editBannerModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const banner = JSON.parse(button.getAttribute('data-banner'));
            
            document.getElementById('edit_banner_id').value = banner._id.$oid;
            document.getElementById('edit_order').value = banner.order;
            document.getElementById('edit_status').value = banner.status;
        });

        // Fungsi untuk menghapus banner
        function deleteBanner(bannerId) {
            if (confirm('Apakah Anda yakin ingin menghapus banner ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="banner_id" value="${bannerId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Preview gambar sebelum upload
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    const preview = document.createElement('img');
                    preview.className = 'img-fluid mt-2 rounded';
                    preview.style.maxHeight = '200px';
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                    
                    // Hapus preview lama jika ada
                    const oldPreview = this.parentElement.querySelector('img');
                    if (oldPreview) oldPreview.remove();
                    
                    this.parentElement.appendChild(preview);
                }
            });
        });
    </script>
</body>
</html>
