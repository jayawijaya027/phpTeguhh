<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$products = $db->getDatabase()->products;
$orders = $db->getDatabase()->orders;
$users = $db->getDatabase()->users;

// Hitung statistik
$stats = [
    'products' => [
        'total' => $products->countDocuments(),
        'low_stock' => $products->countDocuments(['stock' => ['$gt' => 0, '$lte' => 5]]),
        'out_of_stock' => $products->countDocuments(['stock' => 0])
    ],
    'orders' => [
        'total' => $orders->countDocuments(),
        'pending' => $orders->countDocuments(['status' => 'pending']),
        'processing' => $orders->countDocuments(['status' => 'processing']),
        'completed' => $orders->countDocuments(['status' => 'delivered'])
    ],
    'users' => [
        'total' => $users->countDocuments(['role' => 'customer']),
        'active' => $users->countDocuments(['role' => 'customer', 'status' => 'active']),
        'new_today' => $users->countDocuments([
            'role' => 'customer',
            'created_at' => ['$gte' => new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000)]
        ])
    ],
    'revenue' => [
        'total' => $orders->aggregate([
            ['$match' => ['status' => 'delivered']],
            ['$group' => ['_id' => null, 'total' => ['$sum' => '$total_amount']]]
        ])->toArray()
    ]
];

// Ambil pesanan terbaru
$recent_orders = $orders->find(
    [], 
    [
        'sort' => ['created_at' => -1],
        'limit' => 5
    ]
);

// Ambil produk dengan stok menipis
$low_stock_products = $products->find(
    ['stock' => ['$gt' => 0, '$lte' => 5]],
    ['sort' => ['stock' => 1], 'limit' => 5]
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* General Styles */
        body {
            background: #f8f9fa;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            padding: 15px;
            color: #2c3e50;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #3498db;
            border-color: #3498db;
        }

        .btn-primary:hover {
            background: #2980b9;
            border-color: #2980b9;
        }

        .btn-success {
            background: #2ecc71;
            border-color: #2ecc71;
        }

        .btn-success:hover {
            background: #27ae60;
            border-color: #27ae60;
        }

        .btn-danger {
            background: #e74c3c;
            border-color: #e74c3c;
        }

        .btn-danger:hover {
            background: #c0392b;
            border-color: #c0392b;
        }

        /* Badge Styles */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Custom Colors */
        .bg-primary {
            background: #3498db !important;
        }

        .bg-success {
            background: #2ecc71 !important;
        }

        .bg-warning {
            background: #f1c40f !important;
        }

        .bg-danger {
            background: #e74c3c !important;
        }

        .bg-info {
            background: #3498db !important;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .stat-card .stat-icon {
                font-size: 2rem;
            }

            .btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div class="text-muted">
                        <i class="bi bi-clock"></i> 
                        <?php echo date('l, d F Y'); ?>
                    </div>
                </div>

                <!-- Statistik Utama -->
                <div class="row stats-row g-4">
                    <!-- Total Produk -->
                    <div class="col-md-3">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Produk</h6>
                                        <h5 class="mb-0"><?php echo $stats['products']['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Pesanan -->
                    <div class="col-md-3">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Pesanan</h6>
                                        <h5 class="mb-0"><?php echo $stats['orders']['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-cart-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Pengguna -->
                    <div class="col-md-3">
                        <div class="card bg-info text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Pengguna</h6>
                                        <h5 class="mb-0"><?php echo $stats['users']['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-people"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Pendapatan -->
                    <div class="col-md-3">
                        <div class="card bg-warning text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Pendapatan</h6>
                                        <h5 class="mb-0">Rp <?php echo number_format($stats['revenue']['total'][0]->total ?? 0, 0, ',', '.'); ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Detail -->
                <div class="row">
                    <!-- Pesanan Terbaru -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Pesanan Terbaru</h5>
                                    <a href="orders.php" class="btn btn-sm btn-primary">
                                        Lihat Semua
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Status</th>
                                                <th>Total</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo substr($order->_id, -8); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($order->status) {
                                                            'pending' => 'warning',
                                                            'processing' => 'info',
                                                            'shipped' => 'primary',
                                                            'delivered' => 'success',
                                                            'cancelled' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php 
                                                        echo match($order->status) {
                                                            'pending' => 'Menunggu',
                                                            'processing' => 'Diproses',
                                                            'shipped' => 'Dikirim',
                                                            'delivered' => 'Selesai',
                                                            'cancelled' => 'Dibatalkan',
                                                            default => 'Unknown'
                                                        };
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></td>
                                                <td><?php echo date('d/m/Y', $order->created_at->toDateTime()->getTimestamp()); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Produk Stok Menipis -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Stok Menipis</h5>
                                    <a href="products.php" class="btn btn-sm btn-primary">
                                        Kelola Produk
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Stok</th>
                                                <th>Harga</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_products as $product): ?>
                                            <tr>
                                                <td><?php echo $product->brand . ' ' . $product->model; ?></td>
                                                <td><?php echo $product->stock; ?></td>
                                                <td>Rp <?php echo number_format($product->price, 0, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge bg-warning">Stok Menipis</span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 