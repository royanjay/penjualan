<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "penjualan_barang";

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Start session
session_start();
?>