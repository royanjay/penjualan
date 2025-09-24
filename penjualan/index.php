<?php
require_once 'koneksi.php';

// Ambil data produk dari database
$sql = "SELECT p.*, c.nama as category_nama 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);

// Ambil kategori untuk filter
$categories = [];
$sql_cat = "SELECT * FROM categories ORDER BY nama";
$result_cat = $conn->query($sql_cat);
if ($result_cat->num_rows > 0) {
    while ($row = $result_cat->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Sederhana - Katalog Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-card {
            height: 100%;
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Toko Sederhana</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">Kasir</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h1 class="mb-4">Katalog Produk</h1>
        
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" action="index.php">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Cari produk..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">Cari</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form method="GET" action="index.php">
                    <div class="input-group">
                        <select class="form-select" name="category">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['nama']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Product Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card product-card h-100">
                            <?php if (!empty($product['gambar_path'])): ?>
                                <img src="download_image.php?path=<?php echo urlencode($product['gambar_path']); ?>" class="card-img-top product-image" alt="<?php echo $product['nama']; ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top product-image" alt="<?php echo $product['nama']; ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $product['nama']; ?></h5>
                                <p class="card-text">
                                    <small class="text-muted"><?php echo $product['category_nama']; ?></small>
                                </p>
                                <p class="card-text">Rp <?php echo number_format($product['harga_jual'], 0, ',', '.'); ?></p>
                                <p class="card-text">Stok: <?php echo $product['stok']; ?></p>
                            </div>
                            <div class="card-footer">
                                <a href="pos.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary w-100">Beli</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Tidak ada produk yang ditemukan.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>