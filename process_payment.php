<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit();
}

$db = new Database();
$orders = $db->getDatabase()->orders;

try {
    $order_id = new MongoDB\BSON\ObjectId($_POST['order_id']);
    $order = $orders->findOne([
        '_id' => $order_id,
        'user_id' => $_SESSION['user']['id'],
        'status' => 'pending'
    ]);

    if (!$order) {
        $_SESSION['error'] = "Pesanan tidak ditemukan atau sudah dibayar";
        header('Location: orders.php');
        exit();
    }

    // Validasi file bukti pembayaran
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== 0) {
        $_SESSION['error'] = "Bukti pembayaran wajib diupload";
        header('Location: payment.php?order_id=' . $_POST['order_id']);
        exit();
    }

    // Cek ukuran file (maksimal 2MB)
    if ($_FILES['payment_proof']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 2MB";
        header('Location: payment.php?order_id=' . $_POST['order_id']);
        exit();
    }

    // Cek tipe file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES['payment_proof']['type'], $allowed_types)) {
        $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG atau PNG";
        header('Location: payment.php?order_id=' . $_POST['order_id']);
        exit();
    }

    // Buat direktori jika belum ada
    $upload_dir = 'uploads/payments';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate nama file unik
    $file_ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
    $file_name = 'payment_' . $order_id . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . '/' . $file_name;

    // Upload file
    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_path)) {
        $_SESSION['error'] = "Gagal mengupload bukti pembayaran";
        header('Location: payment.php?order_id=' . $_POST['order_id']);
        exit();
    }

    // Siapkan data pembayaran
    $paymentData = [
        'payment_method' => $_POST['payment_method'],
        'sender_name' => $_POST['sender_name'] ?? null,
        'sender_bank' => $_POST['sender_bank'] ?? null,
        'transfer_date' => $_POST['transfer_date'] ?? null,
        'transfer_time' => $_POST['transfer_time'] ?? null,
        'ewallet_name' => $_POST['ewallet_name'] ?? null,
        'ewallet_number' => $_POST['ewallet_number'] ?? null,
        'payment_date' => $_POST['payment_date'] ?? null,
        'payment_time' => $_POST['payment_time'] ?? null,
        'proof_image' => $upload_path,
        'status' => 'pending',
        'submitted_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // Update status pesanan
    $orders->updateOne(
        ['_id' => $order_id],
        [
            '$set' => [
                'status' => 'processing',
                'payment' => $paymentData,
                'payment_date' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    $_SESSION['success'] = "Pembayaran berhasil dikonfirmasi. Tim kami akan segera memverifikasi pembayaran Anda.";
    header('Location: orders.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = "Terjadi kesalahan saat memproses pembayaran";
    header('Location: payment.php?order_id=' . $_POST['order_id']);
    exit();
}
?> 