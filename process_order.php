<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit();
}

$db = new Database();
$orders = $db->getDatabase()->orders;
$cart = $db->getDatabase()->cart;

try {
    // Ambil item dari keranjang
    $cart_items = $cart->find([
        'user_id' => $_SESSION['user']['id']
    ])->toArray();

    if (empty($cart_items)) {
        $_SESSION['error'] = "Keranjang belanja kosong";
        header('Location: cart.php');
        exit();
    }

    // Validasi data pengiriman
    $required_fields = ['name', 'phone', 'address', 'city', 'postal_code'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $_SESSION['error'] = "Semua data pengiriman wajib diisi";
            header('Location: checkout.php');
            exit();
        }
    }

    // Hitung total pesanan
    $total_amount = 0;
    $order_items = [];
    foreach ($cart_items as $item) {
        $total_amount += $item->price * $item->quantity;
        $order_items[] = [
            'product_id' => $item->product_id,
            'name' => $item->name,
            'price' => $item->price,
            'quantity' => $item->quantity,
            'variant' => isset($item->variant) ? $item->variant : null
        ];
    }

    // Data pesanan
    $order_data = [
        'user_id' => $_SESSION['user']['id'],
        'items' => $order_items,
        'total_amount' => $total_amount,
        'status' => 'pending',
        'shipping_address' => [
            'name' => $_POST['name'],
            'phone' => $_POST['phone'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'postal_code' => $_POST['postal_code']
        ],
        'order_date' => new MongoDB\BSON\UTCDateTime(),
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // Simpan pesanan
    $result = $orders->insertOne($order_data);
    
    if ($result->getInsertedCount() > 0) {
        // Hapus keranjang
        $cart->deleteMany(['user_id' => $_SESSION['user']['id']]);

        // Redirect ke halaman pembayaran
        $_SESSION['success'] = "Pesanan berhasil dibuat";
        header('Location: payment.php?order_id=' . $result->getInsertedId());
        exit();
    } else {
        throw new Exception("Gagal membuat pesanan");
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
    header('Location: checkout.php');
    exit();
}
?> 