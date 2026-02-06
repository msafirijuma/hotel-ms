<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

// Fetch user's current shift info
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get today's shift
$shift_info = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT sh.name as shift_name, sh.start_time, sh.end_time, ss.notes, ss.shift_date
    FROM staff_schedules ss
    JOIN shifts sh ON ss.id = sh.id
    WHERE ss.user_id = $user_id 
      AND ss.shift_date = '$today'
    LIMIT 1
"));

// Get tomorrow's shift for preview
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$tomorrow_shift = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT sh.name as shift_name, sh.start_time, sh.end_time
    FROM staff_schedules ss
    JOIN shifts sh ON ss.id = sh.id
    WHERE ss.user_id = $user_id 
      AND ss.shift_date = '$tomorrow'
    LIMIT 1
"));

// Page Header
$page_title = "My Shift";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <h1 class="h3 mb-4 text-gray-800">My Shift</h1>

        <!-- Today's Shift -->
        <div class="card shadow mb-4 border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0">My shift (Today) - (<?= date('d/m/Y') ?>)</h5>
            </div>
            <div class="card-body text-center py-5">
                <?php if ($shift_info): ?>
                    <h2 class="text-primary mb-3"><?= htmlspecialchars($shift_info['shift_name']) ?></h2>
                    <h4 class="mb-2"><?= $shift_info['start_time'] ?> – <?= $shift_info['end_time'] ?></h4>
                    <?php if ($shift_info['notes']): ?>
                        <p class="lead text-muted mb-4">
                            <strong>Notes:</strong> <?= htmlspecialchars($shift_info['notes']) ?>
                        </p>
                    <?php endif; ?>
                    <div class="mt-4">
                        <span class="badge bg-success fs-5 px-4 py-2">Shift assigned today</span>
                    </div>
                <?php else: ?>
                    <i class="fas fa-calendar-times fa-5x text-warning mb-3"></i>
                    <h4 class="text-warning">No shift assigned today</h4>
                    <p class="lead text-muted">Contact your manager or wait until tomorrow.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tomorrow's Shift (Preview) -->
        <div class="card shadow border-0">
            <div class="card-header bg-info text-white py-3">
                <h5 class="mb-0">Tomorrow's shift (<?= date('d/m/Y', strtotime('+1 day')) ?>)</h5>
            </div>
            <div class="card-body text-center py-4">
                <?php if ($tomorrow_shift): ?>
                    <h3 class="text-info mb-3"><?= htmlspecialchars($tomorrow_shift['shift_name']) ?></h3>
                    <h5><?= $tomorrow_shift['start_time'] ?> – <?= $tomorrow_shift['end_time'] ?></h5>
                    <div class="mt-3">
                        <span class="badge bg-info fs-6 px-3 py-2">You are here tomorrow</span>
                    </div>
                <?php else: ?>
                    <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                    <p class="lead text-muted mb-0">No shift assigned tomorrow.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="text-center mt-5">
            <a href="housekeeping_tasks.php" class="btn btn-outline-primary btn-lg px-5 me-3">
                <i class="fas fa-broom me-2"></i> Back to Housekeeping Task
            </a>
            <a href="task_history.php" class="btn btn-outline-secondary btn-lg px-5">
                <i class="fas fa-history me-2"></i> Task History
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>