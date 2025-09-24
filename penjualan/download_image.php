<?php
require_once 'koneksi.php';

// Get image path from query parameter
if (isset($_GET['path']) && !empty($_GET['path'])) {
    $image_path = $_GET['path'];
    
    // Security: Check if the file exists and is within the allowed directory
    $allowed_dir = 'uploads/products/';
    
    // Make sure the path starts with the allowed directory
    if (strpos($image_path, $allowed_dir) !== 0) {
        header("HTTP/1.0 403 Forbidden");
        exit();
    }
    
    // Check if file exists
    if (file_exists($image_path)) {
        // Get file extension
        $extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        
        // Set appropriate content type
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            default:
                header('Content-Type: application/octet-stream');
                break;
        }
        
        // Set content disposition
        header('Content-Disposition: inline; filename="' . basename($image_path) . '"');
        header('Content-Length: ' . filesize($image_path));
        
        // Output the image
        readfile($image_path);
        exit();
    } else {
        // File not found
        header("HTTP/1.0 404 Not Found");
        
        // Output a placeholder image
        header('Content-Type: image/png');
        readfile('https://via.placeholder.com/300x200?text=Image+Not+Found');
        exit();
    }
} else {
    // No path provided
    header("HTTP/1.0 400 Bad Request");
    exit();
}
?>