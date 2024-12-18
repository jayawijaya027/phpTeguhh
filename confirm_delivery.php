<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$db = new Database();
$orders = $db->getDatabase()->orders;

try {
    $order_id = new MongoDB\BSON\ObjectId($_POST['order_id']);
    $order = $orders->findOne([
        '_id' => $order_id,
        'user_id' => $_SESSION['user']['id'],
        'status' => 'shipping'
    ]);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau tidak dapat dikonfirmasi']);
        exit();
    }

    // Update status pesanan
    $result = $orders->updateOne(
        ['_id' => $order_id],
        [
            '$set' => [
                'status' => 'delivered',
                'delivered_date' => new MongoDB\BSON\UTCDateTime(),
                'delivered_by' => $_SESSION['user']['id']
            ]
        ]
    );

    if ($result->getModifiedCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dikonfirmasi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengonfirmasi pesanan']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat memproses konfirmasi']);
}
?> 