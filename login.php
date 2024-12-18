<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $users = $db->getDatabase()->users;
    
    $email = $_POST['email'];
    $password = $_POST['password']; // Password tanpa hashing
    $role = $_POST['role'];
    
    // Cari user berdasarkan email, password, dan role
    $user = $users->findOne([
        'email' => $email, 
        'password' => $password, // Password tanpa hashing
        'role' => $role
    ]);
    
    if ($user) {
        $_SESSION['user'] = [
            'id' => (string)$user->_id,
            'email' => $user->email,
            'role' => $user->role,
            'name' => $user->name
        ];
        
        if ($user->role === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: auto;
            padding: 15px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 20px 20px 0;
        }
        .login-option {
            cursor: pointer;
            padding: 15px;
            margin: 8px 0;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .login-option:hover {
            border-color: #667eea;
            background-color: #f8f9fa;
        }
        .login-option.active {
            border-color: #667eea;
            background-color: #f8f9fa;
        }
        .login-form {
            display: none;
        }
        .login-form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-control, .input-group-text {
            padding: 10px;
        }
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        .back-to-home:hover {
            color: #eee;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
    </a>

    <div class="login-container">
        <div class="card">
            <div class="card-header text-center">
                <h5 class="mb-1">Selamat Datang</h5>
                <small class="text-muted">Silakan login untuk melanjutkan</small>
            </div>
            <div class="card-body pt-2">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <div class="login-option" data-role="customer">
                        <h6 class="mb-0">
                            <i class="bi bi-person-circle me-2"></i>
                            Login sebagai Pembeli
                        </h6>
                    </div>
                    <div class="login-option" data-role="admin">
                        <h6 class="mb-0">
                            <i class="bi bi-shield-lock me-2"></i>
                            Login sebagai Admin
                        </h6>
                    </div>
                </div>
                
                <form method="POST" class="login-form">
                    <input type="hidden" name="role" id="roleInput" value="">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Masukkan email" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                    <p class="text-center mb-0">
                        Belum punya akun? <a href="register.php" class="text-decoration-none">Daftar sekarang</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginOptions = document.querySelectorAll('.login-option');
            const loginForm = document.querySelector('.login-form');
            const roleInput = document.getElementById('roleInput');

            // Fungsi untuk mengaktifkan form login
            function activateLoginForm(role) {
                // Hapus kelas active dari semua opsi
                loginOptions.forEach(opt => opt.classList.remove('active'));
                
                // Tambahkan kelas active ke opsi yang dipilih
                const selectedOption = document.querySelector(`.login-option[data-role="${role}"]`);
                if (selectedOption) {
                    selectedOption.classList.add('active');
                }
                
                // Set nilai role dan tampilkan form
                roleInput.value = role;
                loginForm.classList.remove('active');
                void loginForm.offsetWidth; // Trigger reflow
                loginForm.classList.add('active');

                // Update placeholder dan title sesuai role
                const title = role === 'admin' ? 'Admin' : 'Pembeli';
                document.querySelector('.card-header h5').textContent = `Login sebagai ${title}`;
            }

            // Event listener untuk setiap opsi login
            loginOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const role = this.dataset.role;
                    activateLoginForm(role);
                });
            });

            // Aktifkan opsi pembeli secara default
            activateLoginForm('customer');
        });
    </script>
</body>
</html> 