<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $products = $db->getDatabase()->products;

    // Data produk untuk berbagai merek
    $newProducts = [
        [
            'brand' => 'Vivo',
            'model' => 'V27 5G',
            'price' => 5999000,
            'description' => 'Vivo V27 5G dengan RAM 8GB, ROM 256GB, Kamera 50MP',
            'stock' => 15,
            'image' => 'uploads/products/vivo-v27.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'OPPO',
            'model' => 'Reno8 T',
            'price' => 4499000,
            'description' => 'OPPO Reno8 T dengan RAM 8GB, ROM 128GB, Kamera 108MP',
            'stock' => 20,
            'image' => 'uploads/products/oppo-reno8t.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'Infinix',
            'model' => 'Note 30',
            'price' => 2799000,
            'description' => 'Infinix Note 30 dengan RAM 8GB, ROM 256GB, Baterai 5000mAh',
            'stock' => 25,
            'image' => 'uploads/products/infinix-note30.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'Realme',
            'model' => '10 Pro 5G',
            'price' => 3999000,
            'description' => 'Realme 10 Pro 5G dengan RAM 8GB, ROM 256GB, Layar 120Hz',
            'stock' => 18,
            'image' => 'uploads/products/realme-10pro.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'POCO',
            'model' => 'X5 Pro 5G',
            'price' => 3799000,
            'description' => 'POCO X5 Pro 5G dengan RAM 8GB, ROM 256GB, Snapdragon 778G',
            'stock' => 22,
            'image' => 'uploads/products/poco-x5pro.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        // Tambahan produk untuk setiap merek
        [
            'brand' => 'Vivo',
            'model' => 'Y36',
            'price' => 2999000,
            'description' => 'Vivo Y36 dengan RAM 8GB, ROM 128GB, Baterai 5000mAh',
            'stock' => 30,
            'image' => 'uploads/products/vivo-y36.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'OPPO',
            'model' => 'A78',
            'price' => 3299000,
            'description' => 'OPPO A78 dengan RAM 8GB, ROM 128GB, Fast Charging 67W',
            'stock' => 25,
            'image' => 'uploads/products/oppo-a78.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'Infinix',
            'model' => 'Hot 30i',
            'price' => 1599000,
            'description' => 'Infinix Hot 30i dengan RAM 8GB, ROM 128GB, Kamera 50MP',
            'stock' => 40,
            'image' => 'uploads/products/infinix-hot30i.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'Realme',
            'model' => 'C55',
            'price' => 2499000,
            'description' => 'Realme C55 dengan RAM 6GB, ROM 128GB, Fast Charging 33W',
            'stock' => 35,
            'image' => 'uploads/products/realme-c55.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'brand' => 'POCO',
            'model' => 'M5s',
            'price' => 2199000,
            'description' => 'POCO M5s dengan RAM 6GB, ROM 128GB, MediaTek Helio G95',
            'stock' => 28,
            'image' => 'uploads/products/poco-m5s.jpg',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];

    // Masukkan data produk ke database
    $result = $products->insertMany($newProducts);
    
    echo "Berhasil menambahkan " . $result->getInsertedCount() . " produk baru.\n";
    echo "Merek yang ditambahkan: Vivo, OPPO, Infinix, Realme, POCO\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 