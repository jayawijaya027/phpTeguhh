<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['cart_id'])) {
    $db = new Database();
    $cart = $db->getDatabase()->cart;
    
    $cart_id = new MongoDB\BSON\ObjectId($_POST['cart_id']);
    
    $cart->deleteOne([
        '_id' => $cart_id,
        'user_id' => $_SESSION['user']['id']
    ]);
    
    echo json_encode(['success' => true]);
    exit();
} 