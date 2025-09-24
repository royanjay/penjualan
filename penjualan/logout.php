<?php
require_once 'koneksi.php';

// Hancurkan session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: admin_login.php");
exit();
?>