<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Item ID tidak ditemukan']);
    exit();
}

try {
    $db = new Database();
    $cart = $db->getDatabase()->cart;
    
    $itemId = new MongoDB\BSON\ObjectId($_POST['item_id']);
    $userId = $_SESSION['user']['id'];
    
    $result = $cart->deleteOne([
        '_id' => $itemId,
        'user_id' => $userId
    ]);
    
    if ($result->getDeletedCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 