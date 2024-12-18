<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $database = $db->getDatabase();
    
    // Cek apakah collection payments sudah ada
    $collections = $database->listCollections();
    $collectionExists = false;
    
    foreach ($collections as $collection) {
        if ($collection->getName() === 'payments') {
            $collectionExists = true;
            break;
        }
    }
    
    // Jika belum ada, buat collection payments
    if (!$collectionExists) {
        $database->createCollection('payments');
        echo "Collection 'payments' berhasil dibuat.\n";
        
        // Buat index
        $database->payments->createIndex(['order_id' => 1]);
        $database->payments->createIndex(['user_id' => 1]);
        $database->payments->createIndex(['status' => 1]);
        $database->payments->createIndex(['created_at' => -1]);
        echo "Index berhasil dibuat.\n";
        
        // Ambil order yang sudah ada untuk referensi
        $existingOrder = $database->orders->findOne([]);
        
        if ($existingOrder) {
            // Insert data contoh dengan order_id yang valid
            $database->payments->insertMany([
                [
                    'order_id' => $existingOrder->_id,
                    'user_id' => $existingOrder->user_id,
                    'amount' => 5000000,
                    'sender_name' => 'John Doe',
                    'sender_bank' => 'BCA',
                    'proof_image' => 'uploads/payments/payment_1234567890.jpg',
                    'status' => 'pending',
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ],
                [
                    'order_id' => $existingOrder->_id,
                    'user_id' => $existingOrder->user_id,
                    'amount' => 7500000,
                    'sender_name' => 'Jane Smith',
                    'sender_bank' => 'Mandiri',
                    'proof_image' => 'uploads/payments/payment_9876543210.jpg',
                    'status' => 'completed',
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ]);
            echo "Data contoh berhasil ditambahkan.\n";
        } else {
            echo "Tidak ada order yang tersedia untuk referensi.\n";
            // Insert data contoh tanpa referensi order
            $database->payments->insertOne([
                'order_id' => new MongoDB\BSON\ObjectId('65a123456789abcdef123456'), // ObjectId dummy
                'user_id' => 'user123',
                'amount' => 5000000,
                'sender_name' => 'John Doe',
                'sender_bank' => 'BCA',
                'proof_image' => 'uploads/payments/payment_1234567890.jpg',
                'status' => 'pending',
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);
            echo "Data contoh tanpa referensi order berhasil ditambahkan.\n";
        }
    } else {
        echo "Collection 'payments' sudah ada.\n";
    }
    
    echo "Setup collection payments selesai.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 