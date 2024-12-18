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

if ($product_id) {
    try {
        // Hapus produk dari database
        $result = $products->deleteOne(['_id' => $product_id]);
        
        if ($result->getDeletedCount() > 0) {
            $_SESSION['success'] = "Produk berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Produk tidak ditemukan!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menghapus produk: " . $e->getMessage();
    }
}

// Redirect kembali ke dashboard
header('Location: dashboard.php');
exit();
?> 