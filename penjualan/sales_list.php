<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle date filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Build query with date filter
$sql = "SELECT s.*, c.nama as customer_nama 
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        WHERE DATE(s.created_at) BETWEEN ? AND ?
        ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_' . date('Ymd') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'Invoice No',
        'Customer',
        'Total Items',
        'Total Amount',
        'Payment Method',
        'Date'
    ]);
    
    // Add data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['invoice_no'],
            $row['customer_nama'] ?? 'Umum',
            $row['total_items'],
            $row['total_amount'],
            $row['pembayaran_method'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="sales_list.php">Penjualan</a>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h1>Daftar Penjualan</h1>
                    <div>
                        <a href="pos.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus-circle"></i> Transaksi Baru
                        </a>
                        <a href="?export=csv&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>
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

        <!-- Sales Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Total Item</th>
                                <th>Total Harga</th>
                                <th>Metode Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($sale = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $sale['invoice_no']; ?></td>
                                        <td><?php echo $sale['customer_nama'] ?? 'Umum'; ?></td>
                                        <td><?php echo $sale['total_items']; ?></td>
                                        <td>Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
                                        <td><?php echo ucfirst($sale['pembayaran_method']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                                        <td>
                                            <a href="sale_detail.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data penjualan untuk periode yang dipilih.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>