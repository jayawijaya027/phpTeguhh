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

// Ambil ID produk dari parameter URL
$product_id = isset($_GET['id']) ? new MongoDB\BSON\ObjectId($_GET['id']) : null;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

// Ambil data produk
$product = $products->findOne(['_id' => $product_id]);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateData = [
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'price' => (float)$_POST['price'],
        'stock' => (int)$_POST['stock'],
        'description' => $_POST['description']
    ];

    // Cek dan bersihkan array gambar yang kosong
    if (isset($product->images)) {
        $current_images = $product->images->getArrayCopy();
        $valid_images = array_filter($current_images, function($img) {
            return file_exists('../' . $img);
        });
        if (!empty($valid_images)) {
            $updateData['images'] = array_values($valid_images);
        } else {
            $updateData['images'] = [];
        }
    }

    // Handle upload gambar baru jika ada
    if (!empty($_FILES['images']['name'][0])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $images = [];
        
        // Ambil images yang sudah ada (jika ada)
        if (isset($product->images)) {
            $images = $product->images;
        }

        // Loop through each uploaded file
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = $_FILES['images']['name'][$key];
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
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $images[] = 'uploads/products/' . $newName;
                }
            }
        }

        $updateData['images'] = $images;
    }

    try {
        $products->updateOne(
            ['_id' => $product_id],
            ['$set' => $updateData]
        );

        $_SESSION['success'] = "Produk berhasil diperbarui";
        header('Location: products.php');
        exit();
    } catch (Exception $e) {
        $error = "Gagal memperbarui produk: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit Produk</h2>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

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
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Merek</label>
                                        <select name="brand" class="form-select" required>
                                            <?php
                                            $brands = ['Samsung', 'iPhone', 'Xiaomi', 'OPPO', 'Vivo', 'Realme', 'POCO', 'Infinix'];
                                            foreach ($brands as $brand) {
                                                $selected = ($brand === $product->brand) ? 'selected' : '';
                                                echo "<option value=\"$brand\" $selected>$brand</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Model</label>
                                        <input type="text" name="model" class="form-control" value="<?php echo $product->model; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Harga</label>
                                        <input type="number" name="price" class="form-control" value="<?php echo $product->price; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Stok</label>
                                        <input type="number" name="stock" class="form-control" value="<?php echo $product->stock; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="description" class="form-control" rows="4" required><?php echo $product->description; ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Gambar Produk</label>
                                        
                                        <!-- Image List -->
                                        <?php if (isset($product->images) && !empty($product->images)): ?>
                                        <div class="product-images-list mb-3">
                                            <?php foreach ($product->images as $index => $image): ?>
                                                <?php if (file_exists('../' . $image)): ?>
                                                <div class="product-image-item mb-2 p-2 border rounded">
                                                    <div class="d-flex align-items-center">
                                                        <img src="../<?php echo $image; ?>" 
                                                             class="img-thumbnail me-2"
                                                             style="width: 80px; height: 80px; object-fit: cover;"
                                                             alt="Product Image <?php echo $index + 1; ?>">
                                                        <div class="flex-grow-1">
                                                            <small class="text-muted d-block">Gambar <?php echo $index + 1; ?></small>
                                                            <a href="delete_product_image.php?id=<?php echo $product_id; ?>&image=<?php echo urlencode($image); ?>" 
                                                               class="btn btn-danger btn-sm mt-1"
                                                               onclick="return confirm('Apakah Anda yakin ingin menghapus gambar ini?')">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Multiple File Upload -->
                                        <input type="file" name="images[]" class="form-control mb-2" accept="image/*" multiple>
                                        <small class="text-muted">
                                            Format: JPG, JPEG, PNG, GIF, WEBP (Max 2MB per file)<br>
                                            Anda dapat memilih beberapa file sekaligus
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
                                </button>
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