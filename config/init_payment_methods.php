<?php
require_once 'database.php';

$db = new Database();
$payment_methods = $db->getDatabase()->payment_methods;

// Hapus data lama jika ada
$payment_methods->deleteMany([]);

// Data bank
$banks = [
    [
        'type' => 'bank',
        'name' => 'BCA',
        'account_number' => '1234567890',
        'account_name' => 'Toko Handphone',
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'type' => 'bank',
        'name' => 'Mandiri',
        'account_number' => '0987654321',
        'account_name' => 'Toko Handphone',
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'type' => 'bank',
        'name' => 'BNI',
        'account_number' => '1122334455',
        'account_name' => 'Toko Handphone',
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ]
];

// Data e-wallet
$ewallets = [
    [
        'type' => 'ewallet',
        'name' => 'DANA',
        'account_number' => '0895323449220',
        'account_name' => 'Toko Handphone',
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'type' => 'ewallet',
        'name' => 'OVO',
        'account_number' => '081234567890',
        'account_name' => 'Toko Handphone',
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ],
    [
        'type' => 'ewallet',
        'name' => 'GoPay',
        'account_number' => '0895323449220',
        'account_name' => 'Toko Handphone',
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ]
];

// Masukkan data ke database
$payment_methods->insertMany($banks);
$payment_methods->insertMany($ewallets);

echo "Data metode pembayaran berhasil diinisialisasi!\n";
?> 