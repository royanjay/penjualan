<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get today's sales summary
$today = date('Y-m-d');
$sql_today = "SELECT COUNT(*) as total_sales, SUM(total_amount) as total_revenue 
              FROM sales 
              WHERE DATE(created_at) = ?";
$stmt_today = $conn->prepare($sql_today);
$stmt_today->bind_param("s", $today);
$stmt_today->execute();
$result_today = $stmt_today->get_result();
$today_summary = $result_today->fetch_assoc();
$stmt_today->close();

// Get low stock products
$sql_low_stock = "SELECT id, nama, stok FROM products WHERE stok < 10 ORDER BY stok ASC LIMIT 5";
$result_low_stock = $conn->query($sql_low_stock);

// Get recent sales
$sql_recent_sales = "SELECT s.invoice_no, s.total_amount, s.created_at, c.nama as customer_nama 
                    FROM sales s 
                    LEFT JOIN customers c ON s.customer_id = c.id 
                    ORDER BY s.created_at DESC LIMIT 5";
$result_recent_sales = $conn->query($sql_recent_sales);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-card .card-body {
            padding: 20px;
        }
        .dashboard-card h5 {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .dashboard-card h3 {
            font-size: 1.75rem;
            margin-bottom: 0;
        }
        .navbar-brand {
            font-weight: bold;
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
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
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
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1>Dashboard</h1>
                <p class="text-muted">Selamat datang, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Penjualan Hari Ini</h5>
                        <h3><?php echo $today_summary['total_sales'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body">
                        <h5>Pendapatan Hari Ini</h5>
                        <h3>Rp <?php echo number_format($today_summary['total_revenue'] ?? 0, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card bg-warning text-white">
                    <div class="card-body">
                        <h5>Produk Stok Rendah</h5>
                        <h3><?php echo $result_low_stock->num_rows; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Low Stock Products -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">Produk Stok Rendah</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_low_stock->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($product = $result_low_stock->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $product['nama']; ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $product['stok']; ?></span>
                                                </td>
                                                <td>
                                                    <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada produk dengan stok rendah.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Penjualan Terakhir</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result_recent_sales->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Pelanggan</th>
                                            <th>Total</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($sale = $result_recent_sales->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $sale['invoice_no']; ?></td>
                                                <td><?php echo $sale['customer_nama'] ?? 'Umum'; ?></td>
                                                <td>Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-2">
                                <a href="sales_list.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Belum ada transaksi penjualan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>