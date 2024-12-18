<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Cek parameter yang diperlukan
if (!isset($_GET['id']) || !isset($_GET['image'])) {
    header('Location: products.php');
    exit();
}

try {
    $db = new Database();
    $products = $db->getDatabase()->products;

    $product_id = new MongoDB\BSON\ObjectId($_GET['id']);
    $image_path = urldecode($_GET['image']);

    // Ambil data produk
    $product = $products->findOne(['_id' => $product_id]);
    
    if ($product && isset($product->images)) {
        // Konversi BSONArray ke PHP array
        $current_images = $product->images->getArrayCopy();
        
        // Filter array untuk menghapus gambar yang dipilih
        $updated_images = array_values(array_filter($current_images, function($img) use ($image_path) {
            return $img !== $image_path;
        }));

        // Update produk di database
        $products->updateOne(
            ['_id' => $product_id],
            ['$set' => ['images' => $updated_images]]
        );

        // Hapus file fisik jika ada
        $file_path = '../' . $image_path;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $_SESSION['success'] = "Gambar berhasil dihapus";
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Gagal menghapus gambar";
}

// Kembali ke halaman edit produk
header('Location: edit_product.php?id=' . $_GET['id']);
exit();