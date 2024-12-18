<?php
?>

<style>
    .sidebar {
        background: linear-gradient(to bottom, #2c3e50, #34495e);
        min-height: 100vh;
        width: 250px;
        padding: 15px 0;
        transition: all 0.3s ease;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .sidebar .nav-link {
        color: rgba(255,255,255,0.8);
        padding: 8px 16px;
        margin: 2px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
    }

    .sidebar .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        background: #3498db;
        color: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .sidebar .nav-link i {
        width: 20px;
        font-size: 1rem;
        margin-right: 8px;
    }

    .sidebar hr {
        margin: 12px 16px;
        border-color: rgba(255,255,255,0.1);
    }

    .sidebar .brand-section {
        padding: 0 20px 10px 20px;
        margin-bottom: 5px;
    }

    .sidebar .brand-link {
        color: white;
        font-size: 1.2rem;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 8px 0;
    }

    .sidebar .brand-link i {
        margin-right: 8px;
        font-size: 1.4rem;
    }

    .sidebar .user-section {
        padding: 12px 16px;
        background: rgba(0,0,0,0.1);
        margin-top: auto;
    }

    .sidebar .user-section .dropdown-toggle {
        padding: 8px 12px;
        border-radius: 8px;
        width: 100%;
        text-align: left;
        background: rgba(255,255,255,0.1);
        border: none;
        font-size: 0.9rem;
    }

    .sidebar .user-section .dropdown-menu {
        width: 100%;
        margin-top: 10px;
        background: #34495e;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .sidebar .dropdown-item {
        color: rgba(255,255,255,0.8);
        padding: 8px 20px;
        transition: all 0.3s ease;
    }

    .sidebar .dropdown-item:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
        }
        
        .sidebar .nav-link {
            padding: 8px 12px;
            margin: 2px 8px;
            font-size: 0.85rem;
        }
        
        .sidebar .brand-link {
            font-size: 1.1rem;
        }
    }
</style>

<div class="sidebar">
    <div class="d-flex flex-column h-100">
        <!-- Brand Section -->
        <div class="brand-section">
            <a href="dashboard.php" class="brand-link">
                <i class="bi bi-phone"></i>
                <span>Admin Panel</span>
            </a>
        </div>
        
        <hr>
        
        <!-- Navigation Links -->
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
                    <i class="bi bi-box"></i>
                    Produk
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
                    <i class="bi bi-cart"></i>
                    Pesanan
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    Pengguna
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_banners.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_banners.php' ? 'active' : ''; ?>">
                    <i class="bi bi-images"></i>
                    Kelola Banner
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_payment.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_payment.php' ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card"></i>
                    Kelola Pembayaran
                </a>
            </li>
        </ul>
        
        <hr>
        
        <!-- User Section -->
        <div class="user-section">
            <div class="dropdown">
                <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="dropdownUser1" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2"></i>
                    <span class="me-auto"><?php echo $_SESSION['user']['name']; ?></span>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div> 