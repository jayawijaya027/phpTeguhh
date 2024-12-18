<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$cart = $db->getDatabase()->cart;
$products = $db->getDatabase()->products;

try {
    // Ambil item keranjang dan gabungkan dengan data produk
    $cart_items = [];
    $cursor = iterator_to_array($cart->find(['user_id' => $_SESSION['user']['id']]));
    
    foreach ($cursor as $item) {
        $product = $products->findOne(['_id' => $item->product_id]);
        if ($product) {
            $cart_item = [
                '_id' => $item->_id,
                'product_id' => $product->_id,
                'quantity' => $item->quantity,
                'name' => trim(sprintf('%s %s', 
                    isset($product->brand) ? $product->brand : '',
                    isset($product->model) ? $product->model : ''
                )),
                'model' => isset($product->model) ? $product->model : '',
                'brand' => isset($product->brand) ? $product->brand : '',
                'price' => $product->price,
                'image' => isset($product->images[0]) ? $product->images[0] : 'placeholder.jpg',
                'variant' => isset($product->variant) ? $product->variant : null
            ];
            $cart_items[] = (object)$cart_item;
        }
    }
} catch (Exception $e) {
    $cart_items = [];
}

// Hitung total
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item->price * $item->quantity;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Handphone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
    .cart-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
    }

    .quantity-input {
        width: 60px;
        text-align: center;
        -webkit-appearance: none;
        -moz-appearance: textfield;
    }

    .quantity-input::-webkit-outer-spin-button,
    .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .btn-quantity {
        padding: 0.25rem 0.5rem;
    }

    .product-title {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .product-variant {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .cart-summary {
        position: sticky;
        top: 1rem;
    }

    @media (max-width: 768px) {
        .cart-img {
            width: 60px;
            height: 60px;
        }

        .quantity-input {
            width: 50px;
        }

        .btn-quantity {
            padding: 0.2rem 0.4rem;
        }
    }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Keranjang Belanja</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($cart_items)): ?>
                            <div class="text-center p-4">
                                <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                                <p class="mt-3">Keranjang belanja Anda kosong</p>
                                <a href="index.php" class="btn btn-primary">Mulai Belanja</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-end">Harga</th>
                                            <th class="text-center">Jumlah</th>
                                            <th class="text-end">Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item->image); ?>" 
                                                         alt="<?php echo htmlspecialchars($item->name); ?>" 
                                                         class="cart-img me-3">
                                                    <div>
                                                        <div class="product-title">
                                                            <?php 
                                                                $productName = [];
                                                                if (!empty($item->brand)) {
                                                                    $productName[] = htmlspecialchars($item->brand);
                                                                }
                                                                if (!empty($item->model)) {
                                                                    $productName[] = htmlspecialchars($item->model);
                                                                }
                                                                echo implode(' ', $productName);
                                                            ?>
                                                        </div>
                                                        <?php if (isset($item->variant)): ?>
                                                            <div class="product-variant">
                                                                <?php echo htmlspecialchars($item->variant); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                Rp <?php echo number_format($item->price, 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-secondary btn-quantity"
                                                            onclick="updateQuantity(this, '<?php echo $item->_id; ?>', 'decrease')">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <input type="number" 
                                                           class="form-control form-control-sm mx-2 quantity-input"
                                                           value="<?php echo $item->quantity; ?>"
                                                           min="1"
                                                           style="-webkit-appearance: none; -moz-appearance: textfield;"
                                                           onchange="updateQuantityInput(this, '<?php echo $item->_id; ?>')">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-secondary btn-quantity"
                                                            onclick="updateQuantity(this, '<?php echo $item->_id; ?>', 'increase')">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                Rp <?php echo number_format($item->price * $item->quantity, 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        onclick="removeItem('<?php echo $item->_id; ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
            <div class="col-md-4">
                <div class="card cart-summary">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Belanja</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total Harga</span>
                            <strong>Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></strong>
                        </div>
                        <div class="d-grid gap-2">
                            <?php if (!empty($cart_items)): ?>
                                <a href="checkout.php" class="btn btn-primary">
                                    <i class="bi bi-cart-check me-2"></i>
                                    Checkout
                                </a>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Lanjut Belanja
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateQuantity(button, itemId, action) {
        const container = button.closest('.d-flex');
        const input = container.querySelector('.quantity-input');
        let currentValue = parseInt(input.value);
        
        if (action === 'increase') {
            input.value = currentValue + 1;
        } else if (action === 'decrease' && currentValue > 1) {
            input.value = currentValue - 1;
        }
        
        updateCart(itemId, input.value);
    }

    function updateQuantityInput(input, itemId) {
        let value = parseInt(input.value);
        
        if (value < 1) {
            value = 1;
            input.value = 1;
        }
        
        updateCart(itemId, value);
    }

    function updateCart(itemId, newQuantity) {
        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}&new_quantity=${newQuantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Gagal mengubah jumlah barang');
            }
        });
    }

    function removeItem(itemId) {
        if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
            fetch('remove_cart_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Gagal menghapus item');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal menghapus item: ' + error.message);
            });
        }
    }
    </script>
</body>
</html> 