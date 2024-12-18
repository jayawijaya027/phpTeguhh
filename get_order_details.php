<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user']) || !isset($_GET['order_id'])) {
    http_response_code(403);
    exit('Akses ditolak');
}

$db = new Database();
$orders = $db->getDatabase()->orders;
$products = $db->getDatabase()->products;

try {
    $order_id = new MongoDB\BSON\ObjectId($_GET['order_id']);
    $order = $orders->findOne([
        '_id' => $order_id,
        'user_id' => $_SESSION['user']['id']
    ]);

    if (!$order) {
        http_response_code(404);
        exit('Pesanan tidak ditemukan');
    }

    // Update bagian pengambilan detail produk
    foreach ($order->items as &$item) {
        try {
            if (isset($item->product_id)) {
                $product = $products->findOne(['_id' => new MongoDB\BSON\ObjectId($item->product_id)]);
                if ($product) {
                    // Set nama produk dari database dengan fallback ke model
                    $item->name = isset($product->name) ? $product->name : $product->model;
                    $item->model = $product->model;
                    $item->brand = isset($product->brand) ? $product->brand : '';
                } else {
                    // Fallback jika produk tidak ditemukan
                    $item->name = isset($item->model) ? $item->model : 'Produk tidak tersedia';
                }
            } else {
                // Fallback jika tidak ada product_id
                $item->name = isset($item->model) ? $item->model : 'Produk tidak tersedia';
            }
        } catch (Exception $e) {
            error_log("Error getting product details: " . $e->getMessage());
            $item->name = isset($item->model) ? $item->model : 'Produk tidak tersedia';
        }
    }

    // Format status untuk tampilan
    $status_badges = [
        'pending' => 'warning',
        'processing' => 'info',
        'confirmed' => 'primary',
        'shipping' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'rejected' => 'danger'
    ];

    $status_labels = [
        'pending' => 'Menunggu Pembayaran',
        'processing' => 'Verifikasi Pembayaran',
        'confirmed' => 'Pembayaran Diterima',
        'shipping' => 'Sedang Dikirim',
        'delivered' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'rejected' => 'Pembayaran Ditolak'
    ];

    // Helper function untuk format tanggal
    function formatOrderDate($order) {
        if (isset($order->order_date)) {
            return $order->order_date->toDateTime()->format('d M Y H:i');
        } else if (isset($order->created_at)) {
            return $order->created_at->toDateTime()->format('d M Y H:i');
        }
        return date('d M Y H:i');
    }

    // Helper function untuk mengambil nilai aman dari objek
    function safeGet($obj, $prop) {
        return isset($obj->$prop) ? htmlspecialchars($obj->$prop) : '-';
    }

    // Format tanggal
    $order_date = formatOrderDate($order);

    // Sebelum mengirim data ke view
    ?>

    <div class="row">
        <div class="col-md-6">
            <p class="mb-1">
                <strong>Order ID:</strong> #<?php echo substr($order->_id, -8); ?><br>
                <strong>Tanggal:</strong> <?php echo $order_date; ?><br>
                <strong>Status:</strong> 
                <span class="badge bg-<?php echo $status_badges[$order->status]; ?>">
                    <?php echo $status_labels[$order->status]; ?>
                </span>
            </p>
        </div>
        <div class="col-md-6">
            <p class="mb-1">
                <strong>Total Pembayaran:</strong> Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?>
            </p>
            <div class="mt-3">
                <h6>Informasi Pembayaran:</h6>
                <?php if (isset($order->payment)): ?>
                    <?php if ($order->payment->payment_method === 'DANA' || $order->payment->payment_method === 'OVO' || $order->payment->payment_method === 'GoPay'): ?>
                        Metode: <?php echo $order->payment->payment_method; ?><br>
                        Pengirim: <?php echo isset($order->payment->ewallet_name) ? $order->payment->ewallet_name : '-'; ?><br>
                        Bank: -<br>
                        Tanggal Transfer: <?php echo isset($order->payment->payment_date) ? $order->payment->payment_date : '-'; ?><br>
                        Waktu: <?php echo isset($order->payment->payment_time) ? $order->payment->payment_time : '-'; ?>
                    <?php else: ?>
                        Metode: <?php echo $order->payment->payment_method; ?><br>
                        Pengirim: <?php echo isset($order->payment->sender_name) ? $order->payment->sender_name : '-'; ?><br>
                        Bank: <?php echo isset($order->payment->sender_bank) ? $order->payment->sender_bank : '-'; ?><br>
                        Tanggal Transfer: <?php echo isset($order->payment->transfer_date) ? $order->payment->transfer_date : '-'; ?><br>
                        Waktu: <?php echo isset($order->payment->transfer_time) ? $order->payment->transfer_time : '-'; ?>
                    <?php endif; ?>
                <?php else: ?>
                    Metode: -<br>
                    Pengirim: -<br>
                    Bank: -<br>
                    Tanggal Transfer: -<br>
                    Waktu: -
                <?php endif; ?>
            </div>
        </div>
    </div>

    <hr>

    <h6>Detail Produk:</h6>
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
                <?php foreach ($order->items as $item): ?>
                <tr>
                    <td>
                        <?php 
                            // Tampilkan nama produk dengan format yang lebih lengkap
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
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td class="text-end"><strong>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-6">
            <h6>Informasi Pengiriman:</h6>
            <p class="mb-1">
                <?php if (isset($order->shipping_address)): ?>
                    Nama: <?php echo safeGet($order->shipping_address, 'name'); ?><br>
                    No. Telepon: <?php echo safeGet($order->shipping_address, 'phone'); ?><br>
                    Alamat: <?php echo safeGet($order->shipping_address, 'address'); ?><br>
                    Kota: <?php echo safeGet($order->shipping_address, 'city'); ?><br>
                    Kode Pos: <?php echo safeGet($order->shipping_address, 'postal_code'); ?>
                <?php else: ?>
                    <span class="text-muted">Data pengiriman tidak tersedia</span>
                <?php endif; ?>
            </p>
        </div>
        <?php if (isset($order->payment)): ?>
        <div class="col-md-6">
            <h6>Informasi Pembayaran:</h6>
            <?php if ($order->payment->payment_method === 'DANA' || $order->payment->payment_method === 'OVO' || $order->payment->payment_method === 'GoPay'): ?>
                Metode: <?php echo $order->payment->payment_method; ?><br>
                Pengirim: <?php echo isset($order->payment->ewallet_name) ? $order->payment->ewallet_name : '-'; ?><br>
                Bank: -<br>
                Tanggal Transfer: <?php echo isset($order->payment->payment_date) ? $order->payment->payment_date : '-'; ?><br>
                Waktu: <?php echo isset($order->payment->payment_time) ? $order->payment->payment_time : '-'; ?>
            <?php else: ?>
                Metode: <?php echo $order->payment->payment_method; ?><br>
                Pengirim: <?php echo isset($order->payment->sender_name) ? $order->payment->sender_name : '-'; ?><br>
                Bank: <?php echo isset($order->payment->sender_bank) ? $order->payment->sender_bank : '-'; ?><br>
                Tanggal Transfer: <?php echo isset($order->payment->transfer_date) ? $order->payment->transfer_date : '-'; ?><br>
                Waktu: <?php echo isset($order->payment->transfer_time) ? $order->payment->transfer_time : '-'; ?>
            <?php endif; ?>
            <?php if (isset($order->payment->proof_image)): ?>
            <br><br>
            <a href="<?php echo $order->payment->proof_image; ?>" target="_blank" class="btn btn-sm btn-info">
                <i class="bi bi-image"></i> Lihat Bukti Transfer
            </a>
            <?php endif; ?>
            <?php if ($order->status === 'rejected'): ?>
            <div class="alert alert-danger mt-3">
                <strong>Alasan Penolakan:</strong><br>
                <?php echo safeGet($order->payment, 'reject_reason'); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($order->status === 'shipping'): ?>
    <hr>
    <div class="alert alert-info">
        <h6 class="alert-heading">Informasi Pengiriman:</h6>
        <p class="mb-1">
            <?php if (isset($order->shipping)): ?>
                <strong>Kurir:</strong> <?php echo safeGet($order->shipping, 'courier'); ?><br>
                <strong>No. Resi:</strong> <?php echo safeGet($order->shipping, 'tracking_number'); ?><br>
                <?php if (isset($order->shipping->estimated_delivery)): ?>
                    <strong>Estimasi Tiba:</strong> <?php echo safeGet($order->shipping, 'estimated_delivery'); ?>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-muted">Informasi pengiriman belum tersedia</span>
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

<?php
} catch (Exception $e) {
    http_response_code(500);
    exit('Terjadi kesalahan saat memuat detail pesanan');
}
?> 