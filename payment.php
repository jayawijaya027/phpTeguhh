<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user']) || !isset($_GET['order_id'])) {
    header('Location: orders.php');
    exit();
}

$db = new Database();
$orders = $db->getDatabase()->orders;
$payment_methods = $db->getDatabase()->payment_methods;

try {
    $order_id = new MongoDB\BSON\ObjectId($_GET['order_id']);
    $order = $orders->findOne([
        '_id' => $order_id,
        'user_id' => $_SESSION['user']['id'],
        'status' => 'pending'
    ]);

    if (!$order) {
        $_SESSION['error'] = "Pesanan tidak ditemukan atau sudah dibayar";
        header('Location: orders.php');
        exit();
    }

    // Ambil metode pembayaran yang aktif
    $bank_methods = $payment_methods->find(['type' => 'bank', 'status' => 'active'])->toArray();
    $ewallet_methods = $payment_methods->find(['type' => 'ewallet', 'status' => 'active'])->toArray();

} catch (Exception $e) {
    header('Location: orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Toko Handphone</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="orders.php">Kembali ke Pesanan</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Pembayaran Pesanan #<?php echo substr($order->_id, -8); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Detail Pesanan:</h6>
                                <p class="mb-1">Total Pembayaran: <strong>Rp <?php echo number_format($order->total_amount, 0, ',', '.'); ?></strong></p>
                                <p class="mb-1">Status: <span class="badge bg-warning">Menunggu Pembayaran</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Pilih Metode Pembayaran:</h6>
                                <div class="accordion" id="paymentMethods">
                                    <!-- Transfer Bank -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#bankTransfer">
                                                <i class="bi bi-bank me-2"></i> Transfer Bank
                                            </button>
                                        </h2>
                                        <div id="bankTransfer" class="accordion-collapse collapse show" data-bs-parent="#paymentMethods">
                                            <div class="accordion-body p-0">
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($bank_methods as $bank): ?>
                                                    <label class="list-group-item list-group-item-action">
                                                        <input class="form-check-input me-2" type="radio" name="payment_method" value="<?php echo htmlspecialchars($bank->name); ?>" <?php echo $bank === $bank_methods[0] ? 'checked' : ''; ?>>
                                                        <div class="d-flex justify-content-between align-items-center flex-grow-1 ms-2">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($bank->name); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($bank->account_number); ?> (<?php echo htmlspecialchars($bank->account_name); ?>)</small>
                                                            </div>
                                                            
                                                        </div>
                                                    </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- E-Wallet -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eWallet">
                                                <i class="bi bi-wallet2 me-2"></i> E-Wallet
                                            </button>
                                        </h2>
                                        <div id="eWallet" class="accordion-collapse collapse" data-bs-parent="#paymentMethods">
                                            <div class="accordion-body p-0">
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($ewallet_methods as $ewallet): ?>
                                                    <label class="list-group-item list-group-item-action">
                                                        <input class="form-check-input me-2" type="radio" name="payment_method" value="<?php echo htmlspecialchars($ewallet->name); ?>">
                                                        <div class="d-flex justify-content-between align-items-center flex-grow-1 ms-2">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($ewallet->name); ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($ewallet->account_number); ?> (<?php echo htmlspecialchars($ewallet->account_name); ?>)</small>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tambahkan div untuk menampilkan QR code dan instruksi -->
                        <div class="alert alert-info payment-instructions" style="display: none;">
                            <!-- Konten instruksi akan diisi melalui JavaScript -->
                        </div>

                        <!-- Form Konfirmasi Pembayaran -->
                        <form id="paymentForm" action="process_payment.php" method="POST" enctype="multipart/form-data" class="mt-4">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order->_id); ?>">
                            <input type="hidden" name="payment_method" id="selectedPaymentMethod">
                            
                            <!-- Form untuk Transfer Bank -->
                            <div id="bankTransferForm" class="payment-form">
                                <div class="mb-3">
                                    <label class="form-label">Nama Pengirim</label>
                                    <input type="text" name="sender_name" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bank Pengirim</label>
                                    <input type="text" name="sender_bank" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tanggal Transfer</label>
                                    <input type="date" name="transfer_date" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Waktu Transfer</label>
                                    <input type="time" name="transfer_time" class="form-control" required>
                                </div>
                            </div>

                            <!-- Form untuk E-Wallet -->
                            <div id="eWalletForm" class="payment-form" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Nama Akun E-Wallet</label>
                                    <input type="text" name="ewallet_name" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nomor E-Wallet</label>
                                    <input type="text" name="ewallet_number" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pembayaran</label>
                                    <input type="date" name="payment_date" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Waktu Pembayaran</label>
                                    <input type="time" name="payment_time" class="form-control">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bukti Pembayaran <span class="text-danger">*</span></label>
                                <input type="file" name="payment_proof" class="form-control" accept="image/*" required>
                                <small class="text-muted">
                                    Upload bukti pembayaran dalam format gambar (JPG, PNG). Maksimal ukuran file 2MB.<br>
                                    Pastikan bukti transfer/pembayaran menampilkan:<br>
                                    - Tanggal dan waktu transaksi<br>
                                    - Nominal transfer<br>
                                    - Nama penerima<br>
                                    - Status berhasil
                                </small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Konfirmasi Pembayaran
                                </button>
                                <a href="orders.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>
                                    Kembali ke Pesanan
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .list-group-item-action:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .list-group-item-action:active {
        background-color: #e9ecef;
    }

    .list-group-item input[type="radio"]:checked + div {
        background-color: #e9ecef;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentForm = document.getElementById('paymentForm');
        const selectedPaymentMethod = document.getElementById('selectedPaymentMethod');
        const bankTransferForm = document.getElementById('bankTransferForm');
        const eWalletForm = document.getElementById('eWalletForm');
        const paymentInstructions = document.querySelector('.payment-instructions');

        // Set metode pembayaran default
        const defaultMethod = document.querySelector('input[name="payment_method"]:checked');
        if (defaultMethod) {
            selectedPaymentMethod.value = defaultMethod.value;
            showPaymentForm(defaultMethod.value);
        }

        // Event listener untuk radio button metode pembayaran
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                selectedPaymentMethod.value = this.value;
                showPaymentForm(this.value);
                updatePaymentInstructions(this.value);
            });
        });

        function showPaymentForm(method) {
            const isBank = <?php echo json_encode(array_map(function($bank) { return $bank->name; }, $bank_methods)); ?>.includes(method);
            
            bankTransferForm.style.display = isBank ? 'block' : 'none';
            eWalletForm.style.display = isBank ? 'none' : 'block';

            // Reset form validations
            const bankInputs = bankTransferForm.querySelectorAll('input[required]');
            const ewalletInputs = eWalletForm.querySelectorAll('input');

            bankInputs.forEach(input => input.required = isBank);
            ewalletInputs.forEach(input => input.required = !isBank);
        }

        function updatePaymentInstructions(method) {
            const totalAmount = '<?php echo number_format($order->total_amount, 0, ',', '.'); ?>';
            const orderId = '<?php echo substr($order->_id, -8); ?>';
            
            <?php
            $all_methods = array_merge($bank_methods, $ewallet_methods);
            echo "const paymentMethods = " . json_encode($all_methods) . ";\n";
            ?>
            
            const selectedMethod = paymentMethods.find(m => m.name === method);
            if (!selectedMethod) return;

            let instructions = '';
            if (selectedMethod.type === 'bank') {
                instructions = `
                    <h6 class="alert-heading">Instruksi Transfer ${selectedMethod.name}:</h6>
                    <ol class="mb-0">
                        <li>Buka aplikasi ${selectedMethod.name} Mobile</li>
                        <li>Pilih menu Transfer</li>
                        <li>Masukkan nomor rekening: ${selectedMethod.account_number}</li>
                        <li>Masukkan nominal: Rp ${totalAmount}</li>
                        <li>Masukkan berita transfer: Order #${orderId}</li>
                        <li>Periksa kembali detail transfer</li>
                        <li>Konfirmasi transaksi</li>
                    </ol>`;
            } else {
                instructions = `
                    <div class="text-center mb-3">
                        <img src="assets/payment/${selectedMethod.name.toLowerCase()}.png" alt="${selectedMethod.name} QR Code" class="img-fluid" style="max-width: 200px;">
                    </div>
                    <h6 class="alert-heading">Instruksi Pembayaran ${selectedMethod.name}:</h6>
                    <ol class="mb-0">
                        <li>Buka aplikasi ${selectedMethod.name}</li>
                        <li>Pilih menu Kirim/Transfer</li>
                        <li>Masukkan nomor: ${selectedMethod.account_number}</li>
                        <li>Masukkan nominal: Rp ${totalAmount}</li>
                        <li>Tambahkan catatan: Order #${orderId}</li>
                        <li>Periksa detail pembayaran</li>
                        <li>Konfirmasi pembayaran</li>
                    </ol>`;
            }
            
            paymentInstructions.style.display = 'block';
            paymentInstructions.innerHTML = instructions;
        }

        // Form validation
        paymentForm.addEventListener('submit', function(e) {
            if (!selectedPaymentMethod.value) {
                e.preventDefault();
                alert('Silakan pilih metode pembayaran terlebih dahulu');
                return false;
            }
            return true;
        });

        // Trigger event untuk metode pembayaran default
        if (defaultMethod) {
            defaultMethod.dispatchEvent(new Event('change'));
        }
    });
    </script>
</body>
</html> 