<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['item_id']) || !isset($_POST['new_quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = new Database();
$cart = $db->getDatabase()->cart;

try {
    $itemId = $_POST['item_id'];
    $newQuantity = (int)$_POST['new_quantity'];
    
    if ($newQuantity < 1) {
        $newQuantity = 1;
    }
    
    $result = $cart->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($itemId)],
        ['$set' => ['quantity' => $newQuantity]]
    );
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
} 