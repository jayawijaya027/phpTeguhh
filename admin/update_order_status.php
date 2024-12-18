<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $orders = $db->getDatabase()->orders;

    try {
        $order_id = new MongoDB\BSON\ObjectId($_POST['order_id']);
        $new_status = $_POST['status'];

        $orders->updateOne(
            ['_id' => $order_id],
            ['$set' => ['status' => $new_status]]
        );

        $_SESSION['success'] = "Status pesanan berhasil diperbarui";
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal memperbarui status pesanan";
    }
}

header('Location: orders.php');
exit();