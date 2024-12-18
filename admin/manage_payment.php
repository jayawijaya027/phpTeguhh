<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$payment_methods = $db->getDatabase()->payment_methods;

// Proses tambah/update metode pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'update') {
            $paymentData = [
                'type' => $_POST['type'],
                'name' => $_POST['name'],
                'account_number' => $_POST['account_number'],
                'account_name' => $_POST['account_name'],
                'status' => $_POST['status'],
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            if ($_POST['action'] === 'add') {
                $paymentData['created_at'] = new MongoDB\BSON\UTCDateTime();
                $payment_methods->insertOne($paymentData);
                $_SESSION['success'] = "Metode pembayaran berhasil ditambahkan.";
            } else {
                $methodId = new MongoDB\BSON\ObjectId($_POST['method_id']);
                $payment_methods->updateOne(
                    ['_id' => $methodId],
                    ['$set' => $paymentData]
                );
                $_SESSION['success'] = "Metode pembayaran berhasil diperbarui.";
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['method_id'])) {
            $methodId = new MongoDB\BSON\ObjectId($_POST['method_id']);
            $payment_methods->deleteOne(['_id' => $methodId]);
            $_SESSION['success'] = "Metode pembayaran berhasil dihapus.";
        }
        header('Location: manage_payment.php');
        exit();
    }
}

// Ambil daftar metode pembayaran
$methodList = $payment_methods->find([], ['sort' => ['type' => 1, 'name' => 1]]);

// Hitung statistik
$stats = [
    'total' => $payment_methods->countDocuments(),
    'active' => $payment_methods->countDocuments(['status' => 'active']),
    'inactive' => $payment_methods->countDocuments(['status' => 'inactive'])
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembayaran - Admin Panel</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Kelola Metode Pembayaran</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="bi bi-plus-lg"></i> Tambah Metode
                    </button>
                </div>

                <!-- Statistik -->
                <div class="row stats-row g-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Metode</h6>
                                        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-success text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Metode Aktif</h6>
                                        <h5 class="mb-0"><?php echo $stats['active']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-danger text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Metode Nonaktif</h6>
                                        <h5 class="mb-0"><?php echo $stats['inactive']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Metode Pembayaran -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Jenis</th>
                                        <th>Nama</th>
                                        <th>Nomor Rekening/Akun</th>
                                        <th>Atas Nama</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($methodList as $method): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $method->type === 'bank' ? 'primary' : 'info'; ?>">
                                                <?php echo $method->type === 'bank' ? 'Bank' : 'E-Wallet'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $method->name; ?></td>
                                        <td><?php echo $method->account_number; ?></td>
                                        <td><?php echo $method->account_name; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $method->status === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $method->status === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editPaymentModal"
                                                        data-method='<?php echo json_encode($method); ?>'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="deleteMethod('<?php echo $method->_id; ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Modal Tambah Metode Pembayaran -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Metode Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Jenis Pembayaran</label>
                            <select name="type" class="form-select" required>
                                <option value="bank">Bank Transfer</option>
                                <option value="ewallet">E-Wallet</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Bank/E-Wallet</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Rekening/Akun</label>
                            <input type="text" name="account_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Atas Nama</label>
                            <input type="text" name="account_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Metode Pembayaran -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Metode Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="method_id" id="edit_method_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Jenis Pembayaran</label>
                            <select name="type" class="form-select" id="edit_type" required>
                                <option value="bank">Bank Transfer</option>
                                <option value="ewallet">E-Wallet</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Bank/E-Wallet</label>
                            <input type="text" name="name" class="form-control" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Rekening/Akun</label>
                            <input type="text" name="account_number" class="form-control" id="edit_account_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Atas Nama</label>
                            <input type="text" name="account_name" class="form-control" id="edit_account_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" id="edit_status" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Fungsi untuk mengisi form edit
    document.getElementById('editPaymentModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const method = JSON.parse(button.getAttribute('data-method'));
        
        document.getElementById('edit_method_id').value = method._id.$oid;
        document.getElementById('edit_type').value = method.type;
        document.getElementById('edit_name').value = method.name;
        document.getElementById('edit_account_number').value = method.account_number;
        document.getElementById('edit_account_name').value = method.account_name;
        document.getElementById('edit_status').value = method.status;
    });

    // Fungsi untuk menghapus metode pembayaran
    function deleteMethod(methodId) {
        if (confirm('Apakah Anda yakin ingin menghapus metode pembayaran ini?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="method_id" value="${methodId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html> 