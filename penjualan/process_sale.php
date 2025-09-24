<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $invoice_no = $_POST['invoice_no'];
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $payment_method = $_POST['payment_method'];
    $cart_items = json_decode($_POST['cart_items'], true);
    
    // Validasi input
    $errors = [];
    
    if (empty($invoice_no)) {
        $errors[] = "Nomor invoice tidak valid";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Metode pembayaran harus dipilih";
    }
    
    if (empty($cart_items) || !is_array($cart_items)) {
        $errors[] = "Keranjang belanja tidak boleh kosong";
    }
    
    // Jika ada error, kembali ke form
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: pos.php");
        exit();
    }
    
    // Hitung total
    $total_amount = 0;
    $total_items = 0;
    
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
        $total_items += $item['quantity'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert sales record
        $stmt = $conn->prepare("INSERT INTO sales (invoice_no, customer_id, total_amount, total_items, pembayaran_method) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sidis", $invoice_no, $customer_id, $total_amount, $total_items, $payment_method);
        $stmt->execute();
        $sale_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert sale items and update product stock
        foreach ($cart_items as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $subtotal = $price * $quantity;
            
            // Insert sale item
            $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, subtotal) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $sale_id, $product_id, $quantity, $price, $subtotal);
            $stmt->execute();
            $stmt->close();
            
            // Update product stock
            $stmt = $conn->prepare("UPDATE products SET stok = stok - ? WHERE id = ? AND stok >= ?");
            $stmt->bind_param("iii", $quantity, $product_id, $quantity);
            $stmt->execute();
            
            // Check if stock update was successful
            if ($stmt->affected_rows === 0) {
                throw new Exception("Stok tidak mencukupi untuk produk ID: " . $product_id);
            }
            
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success'] = "Transaksi berhasil! Invoice: " . $invoice_no;
        
        // Redirect to sales list
        header("Location: sales_list.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Set error message
        $_SESSION['error'] = "Gagal memproses transaksi: " . $e->getMessage();
        
        // Redirect back to POS
        header("Location: pos.php");
        exit();
    }
} else {
    // Bukan dari form submit
    header("Location: pos.php");
    exit();
}
?>