<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$cart = $db->getDatabase()->cart;
$products = $db->getDatabase()->products;
$orders = $db->getDatabase()->orders;

// Ambil item keranjang
$cart_items = $cart->find(['user_id' => $_SESSION['user']['id']]);
$cart_products = [];
$total = 0;

foreach ($cart_items as $item) {
    $product = $products->findOne(['_id' => $item->product_id]);
    if ($product) {
        $cart_products[] = [
            'cart_id' => $item->_id,
            'product' => $product,
            'quantity' => $item->quantity
        ];
        $total += $product->price * $item->quantity;
    }
}

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($cart_products)) {
        // Buat order baru
        $orderItems = [];
        foreach ($cart_products as $item) {
            $orderItems[] = [
                'product_id' => $item['product']->_id,
                'brand' => isset($item['product']->brand) ? $item['product']->brand : '',
                'model' => $item['product']->model,
                'price' => $item['product']->price,
                'quantity' => $item['quantity'],
                'variant' => isset($item['product']->variant) ? $item['product']->variant : null
            ];

            // Update stok produk
            $products->updateOne(
                ['_id' => $item['product']->_id],
                ['$inc' => ['stock' => -$item['quantity']]]
            );
        }

        // Simpan order
        $orders->insertOne([
            'user_id' => $_SESSION['user']['id'],
            'items' => $orderItems,
            'total_amount' => $total,
            'shipping_address' => [
                'name' => $_POST['name'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'city' => $_POST['city'],
                'postal_code' => $_POST['postal_code']
            ],
            'status' => 'pending',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        // Kosongkan keranjang
        $cart->deleteMany(['user_id' => $_SESSION['user']['id']]);

        // Redirect ke halaman sukses
        header('Location: order_success.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Toko Handphone</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Kembali ke Toko</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Checkout</h2>

        <?php if (empty($cart_products)): ?>
            <div class="alert alert-warning">
                Keranjang belanja Anda kosong. 
                <a href="index.php" class="alert-link">Kembali berbelanja</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Informasi Pengiriman</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nama Penerima</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alamat Lengkap</label>
                                    <textarea name="address" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kota</label>
                                        <input type="text" name="city" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kode Pos</label>
                                        <input type="text" name="postal_code" class="form-control" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    Buat Pesanan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Ringkasan Pesanan</h5>
                            <?php foreach ($cart_products as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-0"><?php echo $item['product']->brand . ' ' . $item['product']->model; ?></h6>
                                    <small class="text-muted"><?php echo $item['quantity']; ?> x Rp <?php echo number_format($item['product']->price, 0, ',', '.'); ?></small>
                                </div>
                                <span>Rp <?php echo number_format($item['product']->price * $item['quantity'], 0, ',', '.'); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Total</h6>
                                <h5 class="mb-0">Rp <?php echo number_format($total, 0, ',', '.'); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 