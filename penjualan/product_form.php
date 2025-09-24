<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil kategori untuk dropdown
$categories = [];
$sql_cat = "SELECT * FROM categories ORDER BY nama";
$result_cat = $conn->query($sql_cat);
if ($result_cat->num_rows > 0) {
    while ($row = $result_cat->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Mode edit atau tambah
$edit_mode = false;
$product = [
    'id' => '',
    'sku' => '',
    'nama' => '',
    'category_id' => '',
    'deskripsi' => '',
    'harga_jual' => '',
    'harga_beli' => '',
    'stok' => '',
    'gambar_path' => ''
];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_mode = true;
    $product_id = $_GET['id'];
    
    // Ambil data produk
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan";
        header("Location: products.php");
        exit();
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Produk' : 'Tambah Produk'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            display: none;
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
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><?php echo $edit_mode ? 'Edit Produk' : 'Tambah Produk'; ?></h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form action="process_product.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" value="<?php echo $product['sku']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo $product['nama']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo $category['nama']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo $product['deskripsi']; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="harga_jual" class="form-label">Harga Jual (Rp)</label>
                                        <input type="number" class="form-control" id="harga_jual" name="harga_jual" value="<?php echo $product['harga_jual']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="harga_beli" class="form-label">Harga Beli (Rp)</label>
                                        <input type="number" class="form-control" id="harga_beli" name="harga_beli" value="<?php echo $product['harga_beli']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="stok" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stok" name="stok" value="<?php echo $product['stok']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gambar" class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/jpeg, image/png">
                                <div class="form-text">Format: JPG/PNG, maksimal 2MB</div>
                            </div>
                            
                            <?php if (!empty($product['gambar_path'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Gambar Saat Ini</label>
                                    <div>
                                        <img src="download_image.php?path=<?php echo urlencode($product['gambar_path']); ?>" class="preview-image" alt="Current Product Image" style="display: inline-block;">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <a href="products.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Update' : 'Simpan'; ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('gambar').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var preview = document.querySelector('.preview-image');
                    preview.src = e.target.result;
                    preview.style.display = 'inline-block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>