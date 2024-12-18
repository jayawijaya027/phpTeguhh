<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$orders = $db->getDatabase()->orders;

try {
    $user_orders = $orders->find(['user_id' => $_SESSION['user']['id']], [
        'sort' => ['order_date' => -1]
    ])->toArray();
} catch (Exception $e) {
    $user_orders = [];
}

// Helper function untuk format status
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Menunggu Pembayaran</span>',
        'processing' => '<span class="badge bg-info">Verifikasi Pembayaran</span>',
        'confirmed' => '<span class="badge bg-primary">Pembayaran Diterima</span>',
        'shipping' => '<span class="badge bg-info">Sedang Dikirim</span>',
        'delivered' => '<span class="badge bg-success">Selesai</span>',
        'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>',
        'rejected' => '<span class="badge bg-danger">Pembayaran Ditolak</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

// Helper function untuk format tanggal
function formatDate($date) {
    if ($date instanceof MongoDB\BSON\UTCDateTime) {
        return $date->toDateTime()->format('d M Y H:i');
    } else if (isset($date)) {
        // Jika date adalah timestamp biasa
        return date('d M Y H:i', $date);
    }
    return '-';
}

// Helper function untuk mendapatkan tanggal pesanan
function getOrderDate($order) {
    if (isset($order->order_date)) {
        return $order->order_date;
    } else if (isset($order->created_at)) {
        return $order->created_at;
    } else {
        // Jika tidak ada tanggal, gunakan waktu sekarang
        return new MongoDB\BSON\UTCDateTime();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pesanan Saya</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($user_orders)): ?>
                    <div class="text-center p-4">
                        <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                        <p class="mt-3">Anda belum memiliki pesanan</p>
                        <a href="index.php" class="btn btn-primary">Mulai Belanja</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo substr($order->_id, -8); ?></td>
                                    <td><?php echo formatDate(getOrderDate($order)); ?></td>
                                    <td>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></td>
                                    <td><?php echo getStatusBadge($order->status); ?></td>
                                    <td>
                                        <?php if (isset($order->payment)): ?>
                                            <?php echo htmlspecialchars($order->payment->payment_method); ?>
                                            <?php if ($order->status === 'processing'): ?>
                                                <br><small class="text-muted">Menunggu verifikasi</small>
                                            <?php elseif ($order->status === 'rejected'): ?>
                                                <br><small class="text-danger">
                                                    <?php echo isset($order->payment->reject_reason) ? htmlspecialchars($order->payment->reject_reason) : 'Pembayaran ditolak'; ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="showOrderDetails('<?php echo $order->_id; ?>')">
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                            <?php if ($order->status === 'pending'): ?>
                                                <a href="payment.php?order_id=<?php echo $order->_id; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-credit-card"></i> Bayar
                                                </a>
                                            <?php elseif ($order->status === 'rejected'): ?>
                                                <a href="payment.php?order_id=<?php echo $order->_id; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-arrow-repeat"></i> Upload Ulang
                                                </a>
                                            <?php elseif ($order->status === 'shipping'): ?>
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="confirmDelivery('<?php echo $order->_id; ?>')">
                                                    <i class="bi bi-check-circle"></i> Terima
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pesanan -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded dynamically -->
                    <div id="orderDetailContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showOrderDetails(orderId) {
        const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
        const contentDiv = document.getElementById('orderDetailContent');
        
        // Show loading
        contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
        modal.show();

        // Fetch order details
        fetch('get_order_details.php?order_id=' + orderId)
            .then(response => response.text())
            .then(html => {
                contentDiv.innerHTML = html;
            })
            .catch(error => {
                contentDiv.innerHTML = '<div class="alert alert-danger">Gagal memuat detail pesanan</div>';
            });
    }

    function confirmDelivery(orderId) {
        if (confirm('Apakah Anda sudah menerima pesanan ini?')) {
            fetch('confirm_delivery.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Gagal mengonfirmasi penerimaan pesanan');
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan saat mengonfirmasi penerimaan pesanan');
            });
        }
    }
    </script>
</body>
</html> 