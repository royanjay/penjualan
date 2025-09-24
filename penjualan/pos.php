<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil data produk dari database
$sql = "SELECT id, sku, nama, harga_jual, stok FROM products WHERE stok > 0 ORDER BY nama";
$result = $conn->query($sql);

// Ambil data pelanggan
$customers = [];
$sql_customers = "SELECT id, nama, email FROM customers ORDER BY nama";
$result_customers = $conn->query($sql_customers);
if ($result_customers->num_rows > 0) {
    while ($row = $result_customers->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Generate invoice number
function generateInvoiceNumber() {
    $prefix = 'INV-' . date('Ymd');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    return $prefix . '-' . $random;
}

$invoice_no = generateInvoiceNumber();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir / POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .product-search {
            position: relative;
        }
        .product-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #ced4da;
            border-top: none;
            border-radius: 0 0 0.25rem 0.25rem;
        }
        .product-search-result-item {
            padding: 0.5rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .product-search-result-item:hover {
            background-color: #f8f9fa;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 0.75rem 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .cart-summary {
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Dashboard Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales_list.php">Penjualan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pos.php">Kasir</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo $_SESSION['admin_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1>Kasir / POS</h1>
            </div>
        </div>

        <div class="row">
            <!-- Product Selection -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Pilih Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="product_search" class="form-label">Cari Produk</label>
                            <div class="product-search">
                                <input type="text" class="form-control" id="product_search" placeholder="Ketik nama atau SKU produk...">
                                <div class="product-search-results d-none" id="product_search_results"></div>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($product = $result->fetch_assoc()): ?>
                                    <div class="col">
                                        <div class="card h-100 product-card" data-id="<?php echo $product['id']; ?>" data-name="<?php echo $product['nama']; ?>" data-price="<?php echo $product['harga_jual']; ?>" data-stock="<?php echo $product['stok']; ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo $product['nama']; ?></h5>
                                                <p class="card-text">
                                                    <small class="text-muted">SKU: <?php echo $product['sku']; ?></small>
                                                </p>
                                                <p class="card-text">Rp <?php echo number_format($product['harga_jual'], 0, ',', '.'); ?></p>
                                                <p class="card-text">Stok: <?php echo $product['stok']; ?></p>
                                            </div>
                                            <div class="card-footer">
                                                <button class="btn btn-primary btn-sm add-to-cart" data-id="<?php echo $product['id']; ?>" data-name="<?php echo $product['nama']; ?>" data-price="<?php echo $product['harga_jual']; ?>" data-stock="<?php echo $product['stok']; ?>">
                                                    <i class="fas fa-cart-plus"></i> Tambah
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info">Tidak ada produk yang tersedia.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Keranjang Belanja</h5>
                    </div>
                    <div class="card-body">
                        <form id="sale_form" action="process_sale.php" method="POST">
                            <input type="hidden" id="invoice_no" name="invoice_no" value="<?php echo $invoice_no; ?>">
                            
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Pelanggan (Opsional)</label>
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">-- Umum --</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>"><?php echo $customer['nama']; ?> (<?php echo $customer['email']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Metode Pembayaran</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="cash">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="card">Kartu</option>
                                    <option value="e-wallet">E-Wallet</option>
                                </select>
                            </div>
                            
                            <div class="cart-items mb-3" id="cart_items">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                    <p>Keranjang belanja kosong</p>
                                </div>
                            </div>
                            
                            <div class="cart-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Item:</span>
                                    <span id="total_items">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Total Harga:</span>
                                    <span id="total_amount">Rp 0</span>
                                </div>
                                <button type="submit" class="btn btn-success w-100" id="checkout_btn" disabled>
                                    <i class="fas fa-check-circle"></i> Proses Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cart data
            let cart = [];
            
            // Product search
            const productSearch = document.getElementById('product_search');
            const productSearchResults = document.getElementById('product_search_results');
            
            productSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length < 2) {
                    productSearchResults.classList.add('d-none');
                    return;
                }
                
                // Filter products based on search term
                const products = [];
                document.querySelectorAll('.product-card').forEach(card => {
                    const name = card.getAttribute('data-name').toLowerCase();
                    const sku = card.querySelector('.card-text').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || sku.includes(searchTerm)) {
                        products.push({
                            id: card.getAttribute('data-id'),
                            name: card.getAttribute('data-name'),
                            price: card.getAttribute('data-price'),
                            stock: card.getAttribute('data-stock')
                        });
                    }
                });
                
                // Display search results
                if (products.length > 0) {
                    productSearchResults.innerHTML = '';
                    products.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'product-search-result-item';
                        item.innerHTML = `
                            <strong>${product.name}</strong> - Rp ${parseInt(product.price).toLocaleString('id-ID')}
                            <small class="text-muted d-block">Stok: ${product.stock}</small>
                        `;
                        item.addEventListener('click', function() {
                            addToCart(product);
                            productSearch.value = '';
                            productSearchResults.classList.add('d-none');
                        });
                        productSearchResults.appendChild(item);
                    });
                    productSearchResults.classList.remove('d-none');
                } else {
                    productSearchResults.innerHTML = '<div class="product-search-result-item">Tidak ada produk yang ditemukan</div>';
                    productSearchResults.classList.remove('d-none');
                }
            });
            
            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!productSearch.contains(e.target) && !productSearchResults.contains(e.target)) {
                    productSearchResults.classList.add('d-none');
                }
            });
            
            // Add to cart buttons
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const product = {
                        id: this.getAttribute('data-id'),
                        name: this.getAttribute('data-name'),
                        price: parseFloat(this.getAttribute('data-price')),
                        stock: parseInt(this.getAttribute('data-stock'))
                    };
                    addToCart(product);
                });
            });
            
            // Add product to cart
            function addToCart(product) {
                // Check if product already in cart
                const existingItem = cart.find(item => item.id === product.id);
                
                if (existingItem) {
                    // Increment quantity if stock allows
                    if (existingItem.quantity < product.stock) {
                        existingItem.quantity++;
                    } else {
                        alert('Stok tidak mencukupi!');
                        return;
                    }
                } else {
                    // Add new item to cart
                    cart.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        stock: product.stock,
                        quantity: 1
                    });
                }
                
                updateCartUI();
            }
            
            // Update cart UI
            function updateCartUI() {
                const cartItemsContainer = document.getElementById('cart_items');
                const totalItemsElement = document.getElementById('total_items');
                const totalAmountElement = document.getElementById('total_amount');
                const checkoutBtn = document.getElementById('checkout_btn');
                
                if (cart.length === 0) {
                    cartItemsContainer.innerHTML = `
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                            <p>Keranjang belanja kosong</p>
                        </div>
                    `;
                    checkoutBtn.disabled = true;
                } else {
                    cartItemsContainer.innerHTML = '';
                    
                    cart.forEach(item => {
                        const cartItem = document.createElement('div');
                        cartItem.className = 'cart-item';
                        cartItem.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-0">${item.name}</h6>
                                    <small class="text-muted">Rp ${item.price.toLocaleString('id-ID')} x ${item.quantity}</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-secondary decrease-quantity" data-id="${item.id}">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="mx-2">${item.quantity}</span>
                                    <button class="btn btn-sm btn-outline-secondary increase-quantity" data-id="${item.id}">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger ms-2 remove-item" data-id="${item.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <strong>Rp ${(item.price * item.quantity).toLocaleString('id-ID')}</strong>
                            </div>
                        `;
                        cartItemsContainer.appendChild(cartItem);
                    });
                    
                    checkoutBtn.disabled = false;
                    
                    // Add event listeners to buttons
                    document.querySelectorAll('.decrease-quantity').forEach(button => {
                        button.addEventListener('click', function() {
                            const productId = this.getAttribute('data-id');
                            const item = cart.find(item => item.id === productId);
                            
                            if (item.quantity > 1) {
                                item.quantity--;
                                updateCartUI();
                            }
                        });
                    });
                    
                    document.querySelectorAll('.increase-quantity').forEach(button => {
                        button.addEventListener('click', function() {
                            const productId = this.getAttribute('data-id');
                            const item = cart.find(item => item.id === productId);
                            
                            if (item.quantity < item.stock) {
                                item.quantity++;
                                updateCartUI();
                            } else {
                                alert('Stok tidak mencukupi!');
                            }
                        });
                    });
                    
                    document.querySelectorAll('.remove-item').forEach(button => {
                        button.addEventListener('click', function() {
                            const productId = this.getAttribute('data-id');
                            cart = cart.filter(item => item.id !== productId);
                            updateCartUI();
                        });
                    });
                }
                
                // Update totals
                const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                const totalAmount = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                
                totalItemsElement.textContent = totalItems;
                totalAmountElement.textContent = `Rp ${totalAmount.toLocaleString('id-ID')}`;
                
                // Update hidden form fields
                document.getElementById('total_items').value = totalItems;
                document.getElementById('total_amount').value = totalAmount;
            }
            
            // Handle form submission
            document.getElementById('sale_form').addEventListener('submit', function(e) {
                if (cart.length === 0) {
                    e.preventDefault();
                    alert('Keranjang belanja tidak boleh kosong!');
                    return;
                }
                
                // Add cart items to form
                const cartItemsInput = document.createElement('input');
                cartItemsInput.type = 'hidden';
                cartItemsInput.name = 'cart_items';
                cartItemsInput.value = JSON.stringify(cart);
                this.appendChild(cartItemsInput);
            });
        });
    </script>
</body>
</html>