<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$products = $db->getDatabase()->products;

// Ambil daftar produk
$productList = $products->find([], ['sort' => ['created_at' => -1]]);

// Hitung statistik produk
$stats = [
    'total' => $products->countDocuments(),
    'out_of_stock' => $products->countDocuments(['stock' => 0]),
    'low_stock' => $products->countDocuments(['stock' => ['$gt' => 0, '$lte' => 5]])
];

// Proses tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $newProduct = [
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'price' => (float)$_POST['price'],
        'stock' => (int)$_POST['stock'],
        'description' => $_POST['description'],
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // Handle upload gambar
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            // Buat nama file unik
            $newName = uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/products/' . $newName;

            // Buat direktori jika belum ada
            if (!file_exists('../uploads/products')) {
                mkdir('../uploads/products', 0777, true);
            }

            // Pindahkan file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $newProduct['images'] = ['uploads/products/' . $newName];
            }
        }
    }

    try {
        $products->insertOne($newProduct);
        $_SESSION['success'] = "Produk berhasil ditambahkan";
        header('Location: products.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menambahkan produk: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Produk</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-lg"></i> Tambah Produk
                    </button>
                </div>

                <!-- Statistik Produk -->
                <div class="row stats-row g-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Produk</h6>
                                        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-box"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Stok Menipis</h6>
                                        <h5 class="mb-0"><?php echo $stats['low_stock']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Stok Habis</h6>
                                        <h5 class="mb-0"><?php echo $stats['out_of_stock']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Produk -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Merek</th>
                                        <th>Model</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productList as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (isset($product->images) && !empty($product->images)): ?>
                                                <img src="../<?php echo $product->images[0]; ?>" 
                                                     alt="<?php echo $product->model; ?>"
                                                     class="product-image rounded">
                                            <?php else: ?>
                                                <img src="../uploads/products/default.jpg" 
                                                     alt="No Image"
                                                     class="product-image rounded">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $product->brand; ?></td>
                                        <td><?php echo $product->model; ?></td>
                                        <td>Rp <?php echo number_format($product->price, 0, ',', '.'); ?></td>
                                        <td><?php echo $product->stock; ?></td>
                                        <td>
                                            <?php if ($product->stock > 5): ?>
                                                <span class="badge bg-success">Tersedia</span>
                                            <?php elseif ($product->stock > 0): ?>
                                                <span class="badge bg-warning">Stok Menipis</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Habis</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product->_id; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="deleteProduct('<?php echo $product->_id; ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <!-- Modal Tambah Produk -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Merek</label>
                            <select name="brand" class="form-select" required>
                                <option value="">Pilih Merek</option>
                                <option value="Samsung">Samsung</option>
                                <option value="iPhone">iPhone</option>
                                <option value="Xiaomi">Xiaomi</option>
                                <option value="OPPO">OPPO</option>
                                <option value="Vivo">Vivo</option>
                                <option value="Realme">Realme</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteProduct(productId) {
        if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
            window.location.href = 'delete_product.php?id=' + productId;
        }
    }
    </script>
</body>
</html>