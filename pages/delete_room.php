<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$room_id = (int)($_GET['id'] ?? 0);
$success = $error = '';

if ($room_id) {
    $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
    if ($room) {
        if (mysqli_query($conn, "DELETE FROM rooms WHERE id = $room_id")) {
            $success = "Chumba {$room['room_number']} kimefutwa!";
        } else {
            $error = "Hitilafu wakati wa kufuta.";
        }
    } else {
        $error = "Chumba haipatikani.";
    }
} else {
    $error = "Hakuna chumba kilichochaguliwa.";
}

$page_title = "Delete Room";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-header <?= $success ? 'bg-success' : 'bg-danger' ?> text-white text-center">
                        <h4 class="mb-0"><?= $success ? 'Imefanikiwa' : 'Hitilafu' ?></h4>
                    </div>
                    <div class="card-body text-center py-5">
                        <?php if ($success): ?>
                            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                            <h4 class="text-success"><?= $success ?></h4>
                        <?php else: ?>
                            <i class="fas fa-times-circle fa-5x text-danger mb-4"></i>
                            <h4 class="text-danger"><?= $error ?></h4>
                        <?php endif; ?>
                        <a href="rooms.php" class="btn btn-primary btn-lg mt-4">
                            <i class="fas fa-arrow-left"></i> Rudi Orodha ya Vyumba
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>