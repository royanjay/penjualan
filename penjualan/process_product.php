<?php
require_once 'koneksi.php';

// Cek session admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Cek apakah form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $sku = $_POST['sku'];
    $nama = $_POST['nama'];
    $category_id = $_POST['category_id'] ?: null;
    $deskripsi = $_POST['deskripsi'];
    $harga_jual = $_POST['harga_jual'];
    $harga_beli = $_POST['harga_beli'] ?: null;
    $stok = $_POST['stok'];
    
    // Validasi input
    $errors = [];
    
    if (empty($sku)) {
        $errors[] = "SKU harus diisi";
    }
    
    if (empty($nama)) {
        $errors[] = "Nama produk harus diisi";
    }
    
    if (empty($harga_jual) || $harga_jual <= 0) {
        $errors[] = "Harga jual harus diisi dan lebih dari 0";
    }
    
    if (empty($stok) || $stok < 0) {
        $errors[] = "Stok harus diisi dan tidak boleh negatif";
    }
    
    // Cek SKU unik (kecuali saat edit)
    $stmt = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
    $stmt->bind_param("si", $sku, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "SKU sudah digunakan";
    }
    
    $stmt->close();
    
    // Jika ada error, kembali ke form
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: product_form.php" . (empty($id) ? "" : "?id=" . $id));
        exit();
    }
    
    // Proses upload gambar
    $gambar_path = '';
    
    if (!empty($_FILES['gambar']['name'])) {
        $target_dir = "uploads/products/";
        
        // Buat folder jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES["gambar"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Cek format gambar
        if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png") {
            $_SESSION['error'] = "Hanya file JPG, JPEG, PNG yang diperbolehkan";
            header("Location: product_form.php" . (empty($id) ? "" : "?id=" . $id));
            exit();
        }
        
        // Cek ukuran file (maks 2MB)
        if ($_FILES["gambar"]["size"] > 2000000) {
            $_SESSION['error'] = "Ukuran file maksimal 2MB";
            header("Location: product_form.php" . (empty($id) ? "" : "?id=" . $id));
            exit();
        }
        
        // Upload file
        if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
            $gambar_path = $target_file;
            
            // Hapus gambar lama jika ada (mode edit)
            if (!empty($id)) {
                $stmt = $conn->prepare("SELECT gambar_path FROM products WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    
                    if (!empty($product['gambar_path']) && file_exists($product['gambar_path'])) {
                        unlink($product['gambar_path']);
                    }
                }
                
                $stmt->close();
            }
        } else {
            $_SESSION['error'] = "Gagal mengupload gambar";
            header("Location: product_form.php" . (empty($id) ? "" : "?id=" . $id));
            exit();
        }
    }
    
    // Mode edit atau tambah
    if (empty($id)) {
        // Tambah produk baru
        $sql = "INSERT INTO products (sku, nama, category_id, deskripsi, harga_jual, harga_beli, stok, gambar_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisddis", $sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok, $gambar_path);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Produk berhasil ditambahkan";
            header("Location: products.php");
            exit();
        } else {
            $_SESSION['error'] = "Gagal menambah produk: " . $conn->error;
            header("Location: product_form.php");
            exit();
        }
        
        $stmt->close();
    } else {
        // Update produk
        if (!empty($gambar_path)) {
            $sql = "UPDATE products SET 
                    sku = ?, 
                    nama = ?, 
                    category_id = ?, 
                    deskripsi = ?, 
                    harga_jual = ?, 
                    harga_beli = ?, 
                    stok = ?, 
                    gambar_path = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisddisi", $sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok, $gambar_path, $id);
        } else {
            $sql = "UPDATE products SET 
                    sku = ?, 
                    nama = ?, 
                    category_id = ?, 
                    deskripsi = ?, 
                    harga_jual = ?, 
                    harga_beli = ?, 
                    stok = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisddii", $sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok, $id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Produk berhasil diupdate";
            header("Location: products.php");
            exit();
        } else {
            $_SESSION['error'] = "Gagal mengupdate produk: " . $conn->error;
            header("Location: product_form.php?id=" . $id);
            exit();
        }
        
        $stmt->close();
    }
} else {
    // Bukan dari form submit
    header("Location: products.php");
    exit();
}
?>