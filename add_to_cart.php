<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $db = new Database();
    $cart = $db->getDatabase()->cart;
    $products = $db->getDatabase()->products;

    try {
        $product_id = new MongoDB\BSON\ObjectId($_POST['product_id']);
        $quantity = (int)$_POST['quantity'];

        // Cek stok produk
        $product = $products->findOne(['_id' => $product_id]);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            exit();
        }

        if ($product->stock < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
            exit();
        }

        // Cek apakah produk sudah ada di keranjang
        $existing_item = $cart->findOne([
            'user_id' => $_SESSION['user']['id'],
            'product_id' => $product_id
        ]);

        if ($existing_item) {
            // Update quantity jika produk sudah ada
            $new_quantity = $existing_item->quantity + $quantity;
            if ($new_quantity > $product->stock) {
                echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
                exit();
            }

            $cart->updateOne(
                ['_id' => $existing_item->_id],
                ['$inc' => ['quantity' => $quantity]]
            );
        } else {
            // Tambah produk baru ke keranjang
            $cart->insertOne([
                'user_id' => $_SESSION['user']['id'],
                'product_id' => $product_id,
                'quantity' => $quantity,
                'added_at' => new MongoDB\BSON\UTCDateTime()
            ]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();