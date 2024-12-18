<?php
// Hitung jumlah item di keranjang
$cart_count = 0;
if (isset($_SESSION['user'])) {
    try {
        $cart_count = $db->getDatabase()->cart->countDocuments([
            'user_id' => $_SESSION['user']['id']
        ]);
    } catch (Exception $e) {
        $cart_count = 0;
    }
}

// Ambil data user
$user = null;
if (isset($_SESSION['user'])) {
    try {
        $user = $db->getDatabase()->users->findOne([
            '_id' => new MongoDB\BSON\ObjectId($_SESSION['user']['id'])
        ]);
    } catch (Exception $e) {
        // Handle error
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-phone me-2"></i>
            Toko Gadget
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house me-1"></i>
                        Beranda
                    </a>
                </li>
            </ul>

            <?php if (isset($_SESSION['user'])): ?>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="bi bi-cart3 me-1"></i>
                            Keranjang
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count > 99 ? '99+' : $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-box me-1"></i>
                            Pesanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <?php if ($user && isset($user->photo)): ?>
                                <img src="<?php echo htmlspecialchars($user->photo); ?>" 
                                     alt="Profile" 
                                     class="rounded-circle me-1"
                                     style="width: 24px; height: 24px; object-fit: cover;">
                            <?php else: ?>
                                <i class="bi bi-person-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo $user ? htmlspecialchars($user->name) : 'Akun'; ?>
                        </a>
                    </li>
                    <?php if ($user && isset($user->role) && $user->role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>
                                Admin
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            Keluar
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <div class="navbar-nav">
                    <a class="nav-link" href="login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Masuk
                    </a>
                    <a class="nav-link" href="register.php">
                        <i class="bi bi-person-plus me-1"></i>
                        Daftar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
/* Style untuk navbar */
.navbar {
    padding: 0.75rem 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.navbar-brand {
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.navbar-brand i {
    margin-right: 0.5rem;
}

/* Style untuk menu navigasi */
.nav-link {
    padding: 0.5rem 1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 0.25rem;
}

.nav-link i {
    font-size: 1.1rem;
}

/* Style untuk dropdown */
.dropdown-menu {
    padding: 0.5rem 0;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item i {
    width: 1.25rem;
    text-align: center;
}

/* Style untuk badge keranjang */
.nav-link .badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(50%, -50%);
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Style untuk foto profil */
.nav-profile-img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .navbar-nav {
        padding: 0.5rem 0;
    }
    
    .nav-link {
        padding: 0.5rem 0;
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: none;
        padding: 0;
        margin: 0;
    }
    
    .dropdown-item {
        padding: 0.5rem 0;
    }
}
</style> 