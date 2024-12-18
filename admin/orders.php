<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$orders = $db->getDatabase()->orders;
$users = $db->getDatabase()->users;
$products = $db->getDatabase()->products;

// Filter pesanan
$filter = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filter['status'] = $_GET['status'];
}

// Ambil daftar pesanan dengan join ke users
$orderList = iterator_to_array($orders->find($filter, ['sort' => ['created_at' => -1]]));

// Tambahkan setelah mengambil data order
foreach ($orderList as $order) {
    foreach ($order->items as &$item) {
        try {
            if (isset($item->product_id)) {
                $product = $products->findOne(['_id' => new MongoDB\BSON\ObjectId($item->product_id)]);
                if ($product) {
                    // Set nama produk dari database dengan fallback ke model
                    $item->brand = isset($product->brand) ? $product->brand : '';
                    $item->model = isset($product->model) ? $product->model : '';
                    $item->variant = isset($product->variant) ? $product->variant : null;
                }
            }
        } catch (Exception $e) {
            error_log("Error getting product details: " . $e->getMessage());
        }
    }
}

// Hitung statistik pesanan
$stats = [
    'total' => $orders->countDocuments(),
    'pending' => $orders->countDocuments(['status' => 'pending']),
    'processing' => $orders->countDocuments(['status' => 'processing']),
    'completed' => $orders->countDocuments(['status' => 'delivered']),
    'cancelled' => $orders->countDocuments(['status' => 'cancelled'])
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-3">
                <h2 class="mb-4">Kelola Pesanan</h2>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistik Pesanan -->
                <div class="row stats-row g-4 mb-4">
                    <div class="col">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Pesanan</h6>
                                        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-cart"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-warning text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Menunggu</h6>
                                        <h5 class="mb-0"><?php echo $stats['pending']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-info text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Diproses</h6>
                                        <h5 class="mb-0"><?php echo $stats['processing']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-gear"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Selesai</h6>
                                        <h5 class="mb-0"><?php echo $stats['completed']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-danger text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Dibatalkan</h6>
                                        <h2 class="mb-0"><?php echo $stats['cancelled']; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Pesanan -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderList as $order): 
                                        $user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($order->user_id)]);
                                    ?>
                                    <tr>
                                        <td>#<?php echo substr($order->_id, -8); ?></td>
                                        <td><?php echo $user ? $user->name : 'User tidak ditemukan'; ?></td>
                                        <td>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></td>
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
                                        <td><?php echo date('d/m/Y H:i', $order->created_at->toDateTime()->getTimestamp()); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#orderModal<?php echo $order->_id; ?>">
                                                Detail
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="deleteOrder('<?php echo $order->_id; ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <!-- Modal Detail Pesanan -->
    <?php foreach ($orderList as $order): 
        $user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($order->user_id)]);
    ?>
    <div class="modal fade" id="orderModal<?php echo $order->_id; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesanan #<?php echo substr($order->_id, -8); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Informasi Pelanggan -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Informasi Pelanggan</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Nama:</strong> <?php echo $user->name; ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo $user->email; ?></p>
                                <p class="mb-1"><strong>Telepon:</strong> <?php echo isset($order->shipping_address->phone) ? $order->shipping_address->phone : '-'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Alamat:</strong></p>
                                <p class="text-muted">
                                    <?php 
                                    if (isset($order->shipping_address)) {
                                        echo $order->shipping_address->address . "<br>";
                                        echo $order->shipping_address->city . "<br>";
                                        echo $order->shipping_address->postal_code;
                                    } else {
                                        echo "-";
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Produk -->
                    <h6 class="border-bottom pb-2">Detail Produk</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($order->items)): ?>
                                <?php foreach ($order->items as $item): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            // Format nama produk
                                            $productName = [];
                                            
                                            // Tambahkan brand jika ada
                                            if (!empty($item->brand)) {
                                                $productName[] = htmlspecialchars($item->brand);
                                            }
                                            
                                            // Tambahkan model
                                            if (!empty($item->model)) {
                                                $productName[] = htmlspecialchars($item->model);
                                            }
                                            
                                            // Tampilkan nama produk
                                            echo implode(' ', $productName);
                                            
                                            // Tampilkan variant jika ada
                                            if (isset($item->variant)) {
                                                echo '<br><small class="text-muted">' . htmlspecialchars($item->variant) . '</small>';
                                            }
                                        ?>
                                    </td>
                                    <td class="text-end">Rp <?php echo number_format($item->price, 0, ',', '.'); ?></td>
                                    <td class="text-center"><?php echo $item->quantity; ?></td>
                                    <td class="text-end">Rp <?php echo number_format($item->price * $item->quantity, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada item</td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Informasi Pembayaran -->
                    <?php if (isset($order->payment)): ?>
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">Informasi Pembayaran</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Metode:</strong> <?php echo $order->payment->payment_method; ?><br>
                                    <?php if ($order->payment->payment_method === 'DANA' || $order->payment->payment_method === 'OVO' || $order->payment->payment_method === 'GoPay'): ?>
                                        <strong>Pengirim:</strong> <?php echo isset($order->payment->ewallet_name) ? $order->payment->ewallet_name : '-'; ?><br>
                                        <strong>Tanggal:</strong> <?php echo isset($order->payment->payment_date) ? $order->payment->payment_date : '-'; ?><br>
                                        <strong>Waktu:</strong> <?php echo isset($order->payment->payment_time) ? $order->payment->payment_time : '-'; ?>
                                    <?php else: ?>
                                        <strong>Pengirim:</strong> <?php echo isset($order->payment->sender_name) ? $order->payment->sender_name : '-'; ?><br>
                                        <strong>Bank:</strong> <?php echo isset($order->payment->sender_bank) ? $order->payment->sender_bank : '-'; ?><br>
                                        <strong>Tanggal Transfer:</strong> <?php echo isset($order->payment->transfer_date) ? $order->payment->transfer_date : '-'; ?><br>
                                        <strong>Waktu:</strong> <?php echo isset($order->payment->transfer_time) ? $order->payment->transfer_time : '-'; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if (isset($order->payment->proof_image)): ?>
                                    <p class="mb-2"><strong>Bukti Pembayaran:</strong></p>
                                    <div class="text-center">
                                        <img src="../<?php echo $order->payment->proof_image; ?>" alt="Bukti Transfer" class="img-fluid mb-2" style="max-height: 200px;">
                                        <br>
                                        <a href="../<?php echo $order->payment->proof_image; ?>" target="_blank" class="btn btn-info btn-sm">
                                            <i class="bi bi-image"></i> Lihat Bukti Transfer
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status Pesanan -->
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">Status Pesanan</h6>
                        <form action="update_order_status.php" method="POST" class="d-flex gap-2">
                            <input type="hidden" name="order_id" value="<?php echo $order->_id; ?>">
                            <select name="status" class="form-select">
                                <option value="pending" <?php echo $order->status === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="processing" <?php echo $order->status === 'processing' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="shipped" <?php echo $order->status === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="delivered" <?php echo $order->status === 'delivered' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="cancelled" <?php echo $order->status === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteOrder(orderId) {
        if (confirm('Apakah Anda yakin ingin menghapus pesanan ini?')) {
            window.location.href = 'delete_order.php?id=' + orderId;
        }
    }
    </script>
</body>
</html> 