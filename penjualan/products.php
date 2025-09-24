<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Hapus produk
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Cek apakah produk ada
    $stmt = $conn->prepare("SELECT gambar_path FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Hapus file gambar jika ada
        if (!empty($product['gambar_path']) && file_exists($product['gambar_path'])) {
            unlink($product['gambar_path']);
        }
        
        // Hapus produk dari database
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Produk berhasil dihapus";
        } else {
            $_SESSION['error'] = "Gagal menghapus produk";
        }
        
        $delete_stmt->close();
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan";
    }
    
    $stmt->close();
    header("Location: products.php");
    exit();
}

// Ambil data produk dari database
$sql = "SELECT p.*, c.nama as category_nama 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
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
                        <a class="nav-link active" href="products.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales_list.php">Penjualan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">Kasir</a>
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
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Manajemen Produk</h1>
                    <a href="product_form.php" class="btn btn-primary">Tambah Produk</a>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>SKU</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($product = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['gambar_path'])): ?>
                                                <img src="download_image.php?path=<?php echo urlencode($product['gambar_path']); ?>" class="product-image" alt="<?php echo $product['nama']; ?>">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/50x50?text=No+Image" class="product-image" alt="<?php echo $product['nama']; ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $product['sku']; ?></td>
                                        <td><?php echo $product['nama']; ?></td>
                                        <td><?php echo $product['category_nama'] ?? '-'; ?></td>
                                        <td>Rp <?php echo number_format($product['harga_jual'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($product['stok'] < 10): ?>
                                                <span class="badge bg-danger"><?php echo $product['stok']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $product['stok']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn btn-sm btn-danger">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada produk yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                window.location.href = 'products.php?delete=' + id;
            }
        }
    </script>
</body>
</html>