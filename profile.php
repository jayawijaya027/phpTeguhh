<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$users = $db->getDatabase()->users;

// Ambil data user
$user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user']['id'])]);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateData = [
        'name' => $_POST['name'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address']
    ];

    // Upload foto profil jika ada
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        // Buat direktori upload jika belum ada
        $upload_dir = 'uploads/profiles';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $_SESSION['user']['id'] . '.' . $ext;
        $upload_path = $upload_dir . '/' . $filename;

        // Hapus foto lama jika ada
        if (isset($user->photo) && file_exists($user->photo)) {
            unlink($user->photo);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            $updateData['photo'] = $upload_path;
        } else {
            $_SESSION['error'] = "Gagal mengupload foto profil";
        }
    }

    $users->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user']['id'])],
        ['$set' => $updateData]
    );

    $_SESSION['success'] = "Profil berhasil diperbarui";
    header('Location: profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Profil Saya</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mb-4">
                            <?php 
                            $photo_path = isset($user->photo) ? $user->photo : 'uploads/profiles/default.jpg';
                            if (!file_exists($photo_path)) {
                                $photo_path = 'https://via.placeholder.com/150';
                            }
                            ?>
                            <img src="<?php echo $photo_path; ?>" 
                                 class="rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                            <h4><?php echo htmlspecialchars($user->name); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($user->email); ?></p>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Foto Profil</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <small class="text-muted">Upload foto dalam format JPG, PNG (Max 2MB)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user->name); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo isset($user->phone) ? htmlspecialchars($user->phone) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo isset($user->address) ? htmlspecialchars($user->address) : ''; ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>
                                    Simpan Perubahan
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>
                                    Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 