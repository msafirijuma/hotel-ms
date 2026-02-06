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

// Get room_type_id and image_path
$img = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_type_id, image_path FROM room_type_images WHERE id = $image_id"));

if (!$img) {
    header("Location: $redirect");
    exit();
}

$room_type_id = $img['room_type_id'];

// Reset all to not primary for this room type
mysqli_query($conn, "UPDATE room_type_images SET is_primary = 0 WHERE room_type_id = $room_type_id");

// Set this one as primary
mysqli_query($conn, "UPDATE room_type_images SET is_primary = 1 WHERE id = $image_id");

// Update main room_types.image
mysqli_query($conn, "UPDATE room_types SET image = '{$img['image_path']}' WHERE id = $room_type_id");

header("Location: $redirect");
exit();
?>