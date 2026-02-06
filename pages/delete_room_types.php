<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$type_id = (int)($_GET['id'] ?? 0);
$success = $error = '';

if ($type_id == 0) {
    $error = "Hakuna aina iliyochaguliwa.";
} else {
    // Check if used by rooms
    $used = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM rooms WHERE type_id = $type_id"))['cnt'];
    if ($used > 0) {
        $error = "Huwezi kufuta aina hii – inatumika na vyumba $used.";
    } else {
        // Get image path
        $image = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM room_types WHERE id = $type_id"))['image'];

        // Delete from DB
        if (mysqli_query($conn, "DELETE FROM room_types WHERE id = $type_id")) {
            // Delete image file
            if ($image && file_exists('../' . $image)) {
                unlink('../' . $image);
            }
            $success = "Aina ya chumba imefutwa kikamilifu!";
        } else {
            $error = "Hitilafu wakati wa kufuta.";
        }
    }
}

$page_title = "Futa Aina ya Chumba";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <?php if ($success): ?>
                            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                            <h3 class="text-success">Imefanikiwa!</h3>
                            <p class="lead"><?= $success ?></p>
                        <?php else: ?>
                            <i class="fas fa-times-circle fa-5x text-danger mb-4"></i>
                            <h3 class="text-danger">Hitilafu!</h3>
                            <p class="lead"><?= $error ?></p>
                        <?php endif; ?>
                        <a href="room_types.php" class="btn btn-primary btn-lg mt-4">
                            <i class="fas fa-arrow-left"></i> Rudi Orodha
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>