<?php
session_start();
require_once '../config/database.php';

// Cek autentikasi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$users = $db->getDatabase()->users;

// Set status default 'active' untuk user yang belum memiliki status
$users->updateMany(
    ['status' => ['$exists' => false]], 
    ['$set' => ['status' => 'active']]
);

// Filter pengguna
$filter = ['role' => 'customer']; // Hanya tampilkan customer

// Ambil daftar pengguna
$userList = $users->find($filter, ['sort' => ['created_at' => -1]]);

// Hitung statistik pengguna
$stats = [
    'total' => $users->countDocuments(['role' => 'customer']),
    'active' => $users->countDocuments(['role' => 'customer', 'status' => 'active']),
    'inactive' => $users->countDocuments(['role' => 'customer', 'status' => 'inactive'])
];

// Handle aksi blokir/aktifkan pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $user_id = new MongoDB\BSON\ObjectId($_POST['user_id']);
        $current_status = isset($_POST['status']) ? $_POST['status'] : 'active';
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        $users->updateOne(
            ['_id' => $user_id],
            ['$set' => ['status' => $new_status]]
        );
        
        header('Location: users.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Panel</title>
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
                <h2 class="mb-4">Kelola Pengguna</h2>

                <!-- Statistik Pengguna -->
                <div class="row stats-row g-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-3">Total Pengguna</h6>
                                        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-people"></i>
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
                                        <h6 class="card-title mb-3">Pengguna Aktif</h6>
                                        <h5 class="mb-0"><?php echo $stats['active']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-person-check"></i>
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
                                        <h6 class="card-title mb-3">Pengguna Diblokir</h6>
                                        <h5 class="mb-0"><?php echo $stats['inactive']; ?></h5>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-person-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Pengguna -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No. Telepon</th>
                                        <th>Status</th>
                                        <th>Terdaftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userList as $user): 
                                        $status = isset($user->status) ? $user->status : 'active'; // Set default status
                                    ?>
                                    <tr>
                                        <td><?php echo $user->name; ?></td>
                                        <td><?php echo $user->email; ?></td>
                                        <td><?php echo isset($user->phone) ? $user->phone : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $status === 'active' ? 'Aktif' : 'Diblokir'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', $user->created_at->toDateTime()->getTimestamp()); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user->_id; ?>">
                                                <input type="hidden" name="status" value="<?php echo $status; ?>">
                                                <button type="submit" 
                                                        name="toggle_status" 
                                                        class="btn btn-sm btn-<?php echo $status === 'active' ? 'danger' : 'success'; ?>"
                                                        onclick="return confirm('Yakin ingin <?php echo $status === 'active' ? 'memblokir' : 'mengaktifkan'; ?> pengguna ini?')">
                                                    <?php echo $status === 'active' ? 'Blokir' : 'Aktifkan'; ?>
                                                </button>
                                            </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 