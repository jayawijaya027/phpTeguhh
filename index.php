<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$products = $db->getDatabase()->products;
$banners = $db->getDatabase()->banners;

// Ambil banner aktif dan urutkan berdasarkan field order
$activeBanners = $banners->find(
    ['status' => 'active'],
    ['sort' => ['order' => 1]]
)->toArray();

// Query untuk produk
$filter = [];
$options = ['sort' => ['created_at' => -1]]; // Default sort

// Filter pencarian
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $filter['$or'] = [
        ['brand' => ['$regex' => $search, '$options' => 'i']],
        ['model' => ['$regex' => $search, '$options' => 'i']],
        ['description' => ['$regex' => $search, '$options' => 'i']]
    ];
}

// Filter brand
if (isset($_GET['brand']) && !empty($_GET['brand'])) {
    $filter['brand'] = $_GET['brand'];
}

// Pengurutan
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $options['sort'] = ['price' => 1];
            break;
        case 'price_desc':
            $options['sort'] = ['price' => -1];
            break;
        case 'newest':
            $options['sort'] = ['created_at' => -1];
            break;
    }
}

// Ambil daftar produk dengan filter
$productList = $products->find($filter, $options);

// Ambil daftar merek unik
$brands = $products->distinct('brand');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Handphone - Jual Beli HP Terpercaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        /* Style untuk section */
        .section {
            padding: 30px 0;
            background-color: #fff;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
        }

        /* Style untuk carousel */
        .banner-section {
            background-color: #f8f9fa;
            padding: 20px 0;
        }

        .banner-container {
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .carousel {
            background-color: #fff;
            position: relative;
            width: 100%;
        }

        .banner-section .carousel-item {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            background-color: #fff;
        }

        .banner-section .carousel-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Kontrol carousel */
        .banner-section .carousel-control-prev,
        .banner-section .carousel-control-next {
            width: 5%;
            opacity: 0.8;
            background: linear-gradient(to right, rgba(0,0,0,0.4), transparent);
        }

        .banner-section .carousel-control-next {
            background: linear-gradient(to left, rgba(0,0,0,0.4), transparent);
        }

        .banner-section .carousel-control-prev-icon,
        .banner-section .carousel-control-next-icon {
            width: 2.5rem;
            height: 2.5rem;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            padding: 20px;
        }

        .banner-section .carousel-indicators {
            margin-bottom: 1rem;
        }

        .banner-section .carousel-indicators [data-bs-target] {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(0, 0, 0, 0.2);
            margin: 0 5px;
        }

        .banner-section .carousel-indicators .active {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        /* Responsive carousel */
        @media (max-width: 768px) {
            .banner-container {
                border-radius: 8px;
                margin: 0 10px;
            }

            .banner-section .carousel-control-prev-icon,
            .banner-section .carousel-control-next-icon {
                width: 2rem;
                height: 2rem;
                padding: 15px;
            }

            .banner-section .carousel-indicators [data-bs-target] {
                width: 8px;
                height: 8px;
                margin: 0 3px;
            }
        }

        /* Style untuk brand filter */
        .brand-section {
            background-color: #fff;
            padding: 30px 0;
        }

        .brand-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
            padding: 10px;
        }

        .brand-icon img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .brand-icon.active {
            background-color: #0d6efd;
        }

        .brand-icon.active img {
            filter: brightness(0) invert(1);
        }

        .brand-name {
            font-size: 0.9rem;
            margin-top: 8px;
            color: #333;
        }

        /* Style untuk product grid */
        .products-section {
            background-color: #f8f9fa;
            padding: 30px 0;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .product-card {
            height: 100%;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
            background-color: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            max-width: 280px;
            margin: 0 auto;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .card-img-container {
            height: 180px;
            background-color: #fff;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-info {
            padding: 12px;
            border-top: 1px solid #f1f1f1;
        }

        .product-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 6px;
            height: 32px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-price {
            font-size: 1.1rem;
            color: #0d6efd;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .product-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 8px;
            height: 20px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Style untuk search bar */
        .search-section {
            background-color: #fff;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .search-form {
            display: flex;
            gap: 8px;
        }

        .search-input {
            flex: 1;
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 0.95rem;
            height: 38px;
        }

        .search-btn {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
            height: 38px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .carousel-item {
                height: 300px;
            }

            .brand-icon {
                width: 50px;
                height: 50px;
            }

            .brand-name {
                font-size: 0.8rem;
            }

            .product-card {
                margin-bottom: 15px;
            }

            .search-input,
            .search-btn {
                padding: 6px 12px;
                font-size: 0.9rem;
                height: 34px;
            }
        }

        /* Style untuk modal produk */
        .modal-dialog {
            max-width: 500px; /* Mengurangi lebar maksimal modal */
            margin: 1.25rem auto;
        }

        .modal-product-image-container {
            position: relative;
            width: 100%;
            padding-top: 100%; /* Rasio 1:1 */
            background-color: #fff;
            overflow: hidden;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .modal-product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }

        .modal .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .modal .modal-header {
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }

        .modal .modal-body {
            padding: 15px;
        }

        .modal .modal-footer {
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
        }

        .modal .table {
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .modal .table td {
            padding: 6px 10px;
            vertical-align: middle;
        }

        .modal .table td:first-child {
            font-weight: 600;
            color: #495057;
            background: #f8f9fa;
            width: 100px;
            border-radius: 4px;
        }

        .modal-product-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .modal-product-brand {
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .modal-product-price {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .modal-product-description {
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .modal h4 {
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .modal h5 {
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .modal .btn {
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .modal .modal-dialog {
                margin: 8px;
                max-width: 95%;
            }

            .modal .modal-body {
                padding: 12px;
            }

            .modal .table td {
                padding: 5px 8px;
                font-size: 0.85rem;
            }

            .modal .table td:first-child {
                width: 90px;
            }

            .modal h4 {
                font-size: 0.95rem;
            }

            .modal-product-title {
                font-size: 0.95rem;
            }

            .modal-product-price {
                font-size: 1.1rem;
            }

            .modal .btn {
                padding: 5px 10px;
                font-size: 0.85rem;
            }
        }

        /* Style untuk banner carousel */
        .banner-section .carousel-item {
            padding-top: 42.85%; /* Rasio 21:9 */
        }

        @media (max-width: 768px) {
            .banner-section .carousel-item {
                padding-top: 56.25%; /* Rasio 16:9 untuk mobile */
            }
        }

        .brand-icon.active i {
            color: white;
        }

        .brand-icon i {
            color: #212529;
            transition: color 0.2s ease;
        }

        /* Mengatur grid layout */
        @media (min-width: 1200px) {
            .col-lg-3 {
                flex: 0 0 auto;
                width: 20%;
                padding: 8px;
            }
        }

        @media (min-width: 768px) and (max-width: 1199px) {
            .col-md-4 {
                padding: 8px;
            }
            .product-card {
                max-width: 240px;
            }
            .card-img-container {
                height: 160px;
            }
        }

        @media (max-width: 767px) {
            .col-6 {
                padding: 6px;
            }
            .product-card {
                max-width: 180px;
            }
            .card-img-container {
                height: 140px;
            }
            .product-title {
                font-size: 0.9rem;
                height: 30px;
            }
            .product-price {
                font-size: 1rem;
            }
            .product-description {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Search Section -->
    <section class="search-section">
        <div class="search-container">
            <form method="GET" class="search-form">
                <?php if (isset($_GET['brand'])): ?>
                    <input type="hidden" name="brand" value="<?php echo htmlspecialchars($_GET['brand']); ?>">
                <?php endif; ?>
                
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Cari produk..."
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                
                <button type="submit" class="btn btn-primary search-btn">
                    <i class="bi bi-search"></i>
                    Cari
                </button>
                
                <?php if (isset($_GET['search'])): ?>
                    <a href="?<?php echo isset($_GET['brand']) ? 'brand=' . urlencode($_GET['brand']) : ''; ?>" 
                       class="btn btn-outline-secondary search-btn">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Banner Section -->
    <section class="banner-section">
        <div class="banner-container">
            <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($activeBanners as $index => $banner): ?>
                        <button type="button" 
                                data-bs-target="#promoCarousel" 
                                data-bs-slide-to="<?php echo $index; ?>" 
                                class="<?php echo $index === 0 ? 'active' : ''; ?>"
                                aria-label="Slide <?php echo $index + 1; ?>">
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="carousel-inner">
                    <?php foreach ($activeBanners as $index => $banner): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" data-bs-interval="5000">
                            <img src="<?php echo htmlspecialchars($banner->image_path); ?>" 
                                 class="d-block" 
                                 alt="<?php echo htmlspecialchars($banner->title); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($activeBanners) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Brand Filter Section -->
    <section class="brand-section">
        <div class="brand-container">
            <h2 class="section-title">Pilih Merek</h2>
            <div class="d-flex flex-wrap gap-4 justify-content-center">
                <a href="index.php" class="text-decoration-none text-center">
                    <div class="brand-icon <?php echo !isset($_GET['brand']) ? 'active' : ''; ?>">
                        <i class="bi bi-grid-3x3-gap-fill" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="brand-name">Semua</div>
                </a>
                <?php foreach ($brands as $brand): ?>
                    <a href="?brand=<?php echo urlencode($brand); ?>" class="text-decoration-none text-center">
                        <div class="brand-icon <?php echo (isset($_GET['brand']) && $_GET['brand'] === $brand) ? 'active' : ''; ?>">
                            <?php
                            $brandLower = strtolower($brand);
                            $logoPath = "assets/brand-logos/{$brandLower}.png";
                            if (file_exists($logoPath)) {
                                echo "<img src='{$logoPath}' alt='{$brand}' style='width: 30px; height: auto;'>";
                            } else {
                                echo '<i class="bi bi-phone"></i>';
                            }
                            ?>
                        </div>
                        <div class="brand-name"><?php echo $brand; ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="products-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">
                    <?php 
                    if (isset($_GET['brand'])) {
                        echo "Produk " . htmlspecialchars($_GET['brand']);
                    } else {
                        echo "Semua Produk";
                    }
                    ?>
                </h2>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-down"></i>
                        <?php
                        $sort_text = "Urutkan";
                        if (isset($_GET['sort'])) {
                            switch ($_GET['sort']) {
                                case 'price_asc':
                                    $sort_text = "Harga Terendah";
                                    break;
                                case 'price_desc':
                                    $sort_text = "Harga Tertinggi";
                                    break;
                                case 'newest':
                                    $sort_text = "Terbaru";
                                    break;
                            }
                        }
                        echo $sort_text;
                        ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'active' : ''; ?>"
                               href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>">
                                <i class="bi bi-sort-numeric-down me-2"></i>Harga Terendah
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'active' : ''; ?>"
                               href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>">
                                <i class="bi bi-sort-numeric-up me-2"></i>Harga Tertinggi
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($productList as $product): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card">
                            <div class="card-img-container">
                                <?php if (isset($product->images) && !empty($product->images)): ?>
                                    <img src="<?php echo htmlspecialchars($product->images[0]); ?>" 
                                         class="product-image" 
                                         alt="<?php echo htmlspecialchars($product->model); ?>">
                                <?php else: ?>
                                    <img src="uploads/products/default.jpg" 
                                         class="product-image" 
                                         alt="No Image">
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">
                                    <?php echo htmlspecialchars($product->brand . ' ' . $product->model); ?>
                                </h5>
                                <div class="product-price">
                                    Rp <?php echo number_format($product->price, 0, ',', '.'); ?>
                                </div>
                                <p class="product-description">
                                    <?php echo htmlspecialchars($product->description); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#productModal_<?php echo $product->_id; ?>">
                                        <i class="bi bi-info-circle"></i> Detail
                                    </button>
                                    <?php if (isset($_SESSION['user'])): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-primary add-to-cart" 
                                                data-product-id="<?php echo $product->_id; ?>">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-sm btn-primary">
                                            <i class="bi bi-cart-plus"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for each product -->
                    <div class="modal fade" 
                         id="productModal_<?php echo $product->_id; ?>" 
                         tabindex="-1" 
                         aria-labelledby="modalLabel_<?php echo $product->_id; ?>" 
                         aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalLabel_<?php echo $product->_id; ?>">
                                        <?php echo htmlspecialchars($product->brand . ' ' . $product->model); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?php if (isset($product->images) && !empty($product->images)): ?>
                                                <div id="carousel_<?php echo $product->_id; ?>" class="carousel slide">
                                                    <div class="carousel-inner">
                                                        <?php foreach ($product->images as $index => $image): ?>
                                                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                                                     class="d-block w-100" 
                                                                     alt="<?php echo htmlspecialchars($product->model); ?>">
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php if (count($product->images) > 1): ?>
                                                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel_<?php echo $product->_id; ?>" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon"></span>
                                                            <span class="visually-hidden">Previous</span>
                                                        </button>
                                                        <button class="carousel-control-next" type="button" data-bs-target="#carousel_<?php echo $product->_id; ?>" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon"></span>
                                                            <span class="visually-hidden">Next</span>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="modal-product-image-container">
                                                    <img src="uploads/products/default.jpg" 
                                                         class="modal-product-image" 
                                                         alt="No Image">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h4 class="mb-3">Spesifikasi</h4>
                                            <table class="table">
                                                <tr>
                                                    <td width="120"><strong>Merek</strong></td>
                                                    <td><?php echo htmlspecialchars($product->brand); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Model</strong></td>
                                                    <td><?php echo htmlspecialchars($product->model); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Harga</strong></td>
                                                    <td class="text-primary fw-bold">
                                                        Rp <?php echo number_format($product->price, 0, ',', '.'); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Stok</strong></td>
                                                    <td><?php echo $product->stock; ?> unit</td>
                                                </tr>
                                                <?php if (isset($product->specs)): ?>
                                                    <?php foreach ($product->specs as $key => $value): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars(ucfirst($key)); ?></strong></td>
                                                            <td><?php echo htmlspecialchars($value); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </table>
                                            <div class="mt-3">
                                                <h5>Deskripsi</h5>
                                                <p><?php echo nl2br(htmlspecialchars($product->description)); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <?php if (isset($_SESSION['user'])): ?>
                                        <button type="button" 
                                                class="btn btn-primary add-to-cart" 
                                                data-product-id="<?php echo $product->_id; ?>">
                                            <i class="bi bi-cart-plus me-2"></i>
                                            Tambah ke Keranjang
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary">
                                            <i class="bi bi-cart-plus me-2"></i>
                                            Login untuk Membeli
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Produk berhasil ditambahkan ke keranjang!');
                    location.reload();
                } else {
                    alert(data.message || 'Gagal menambahkan ke keranjang');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan ke keranjang');
            });
        });
    });

    // Initialize all modals
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize carousel
        var mainCarousel = new bootstrap.Carousel(document.getElementById('promoCarousel'), {
            interval: 5000,    // Waktu perpindahan slide (5 detik)
            wrap: true,        // Carousel akan berputar terus
            keyboard: true,    // Kontrol dengan keyboard
            pause: 'hover',    // Pause saat hover
            touch: true        // Enable touch swipe
        });

        // Initialize product carousels
        document.querySelectorAll('.carousel').forEach(function(carousel) {
            new bootstrap.Carousel(carousel, {
                interval: false // Disable auto sliding for product images
            });
        });
    });
    </script>
</body>
</html> 