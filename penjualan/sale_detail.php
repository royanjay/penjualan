<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get sale ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID penjualan tidak valid";
    header("Location: sales_list.php");
    exit();
}

$sale_id = $_GET['id'];

// Get sale data
$stmt = $conn->prepare("SELECT s.*, c.nama as customer_nama, c.email, c.telepon, c.alamat 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Data penjualan tidak ditemukan";
    header("Location: sales_list.php");
    exit();
}

$sale = $result->fetch_assoc();
$stmt->close();

// Get sale items
$stmt = $conn->prepare("SELECT si.*, p.nama as product_nama, p.sku 
                        FROM sale_items si 
                        JOIN products p ON si.product_id = p.id 
                        WHERE si.sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .invoice-header {
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .invoice-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .invoice-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
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
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Detail Penjualan</h1>
                    <div>
                        <button class="btn btn-secondary me-2" onclick="window.print()">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                        <a href="sales_list.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Invoice Header -->
                <div class="invoice-header">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="invoice-title">INVOICE</div>
                            <div class="invoice-details">
                                <div>No. Invoice: <strong><?php echo $sale['invoice_no']; ?></strong></div>
                                <div>Tanggal: <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="invoice-title">Toko Sederhana</div>
                            <div class="invoice-details">
                                <div>Jl. Contoh No. 123</div>
                                <div>Kota, Provinsi 12345</div>
                                <div>Telp: (021) 1234567</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Informasi Pelanggan</h5>
                        <?php if (!empty($sale['customer_nama'])): ?>
                            <p>
                                <strong><?php echo $sale['customer_nama']; ?></strong><br>
                                <?php if (!empty($sale['email'])): ?>
                                    Email: <?php echo $sale['email']; ?><br>
                                <?php endif; ?>
                                <?php if (!empty($sale['telepon'])): ?>
                                    Telepon: <?php echo $sale['telepon']; ?><br>
                                <?php endif; ?>
                                <?php if (!empty($sale['alamat'])): ?>
                                    Alamat: <?php echo nl2br($sale['alamat']); ?>
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p>Umum</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5>Informasi Pembayaran</h5>
                        <p>
                            Metode Pembayaran: <strong><?php echo ucfirst($sale['pembayaran_method']); ?></strong><br>
                            Status: <span class="badge bg-success">Lunas</span>
                        </p>
                    </div>
                </div>

                <!-- Sale Items -->
                <h5>Item Pembelian</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $item['sku']; ?></td>
                                    <td><?php echo $item['product_nama']; ?></td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['qty']; ?></td>
                                    <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Total</strong></td>
                                <td><strong>Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer -->
                <div class="row">
                    <div class="col-md-6">
                        <p>Terima kasih atas pembelian Anda!</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p>
                            <small>Dicetak pada: <?php echo date('d/m/Y H:i'); ?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>