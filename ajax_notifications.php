<?php
session_start();
require 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

if ($action === 'count') {
    $count = get_unread_count($conn, $user_id);
    echo json_encode(['count' => $count]);
} elseif ($action === 'list') {
    $notifs = get_recent_notifications($conn, $user_id, 10);
    echo json_encode(['notifications' => $notifs]);
} elseif ($action === 'mark_read') {
    mark_notifications_read($conn, $user_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>