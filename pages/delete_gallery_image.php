<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$image_id = (int)($_GET['id'] ?? 0);
$redirect = $_GET['redirect'] ?? 'room_types.php';

if ($image_id == 0) {
    header("Location: $redirect");
    exit();
}

// Fetch image path
$image = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM room_type_images WHERE id = $image_id"));

if ($image) {
    // Delete from database
    mysqli_query($conn, "DELETE FROM room_type_images WHERE id = $image_id");

    // Delete file from server
    $file_path = '../' . $image['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

header("Location: $redirect");
exit();
?>