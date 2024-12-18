<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $db = new Database();
    $orders = $db->getDatabase()->orders;

    try {
        $order_id = new MongoDB\BSON\ObjectId($_GET['id']);
        
        // Hapus pesanan
        $result = $orders->deleteOne(['_id' => $order_id]);
        
        if ($result->getDeletedCount() > 0) {
            $_SESSION['success'] = "Pesanan berhasil dihapus";
        } else {
            $_SESSION['error'] = "Pesanan tidak ditemukan";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menghapus pesanan";
    }
}

header('Location: orders.php');
exit();
?>