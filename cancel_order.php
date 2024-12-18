<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if (isset($_POST['order_id'])) {
    $db = new Database();
    $orders = $db->getDatabase()->orders;
    $products = $db->getDatabase()->products;
    
    try {
        $order_id = new MongoDB\BSON\ObjectId($_POST['order_id']);
        
        // Cek pesanan
        $order = $orders->findOne([
            '_id' => $order_id,
            'user_id' => $_SESSION['user']['id'],
            'status' => 'pending'
        ]);

        if ($order) {
            // Kembalikan stok produk
            foreach ($order->items as $item) {
                $products->updateOne(
                    ['_id' => $item->product_id],
                    ['$inc' => ['stock' => $item->quantity]]
                );
            }

            // Update status pesanan
            $orders->updateOne(
                ['_id' => $order_id],
                ['$set' => ['status' => 'cancelled']]
            );
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau tidak dapat dibatalkan']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit(); 