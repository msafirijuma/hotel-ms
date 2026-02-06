<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'housekeeping') {
    header("Location: ../login.php");
    exit();
}
require 'config/db_connect.php';

$success = $error = '';
$current_user_id = $_SESSION['user_id'];

// Handle Task Actions
if (isset($_POST['action'])) {
    $task_id = (int)$_POST['task_id'];
    $action = $_POST['action'];

    if ($action == 'start') {
        $stmt = mysqli_prepare($conn, "UPDATE housekeeping_tasks SET status = 'in_progress' WHERE id = ? AND assigned_to = ?");
        mysqli_stmt_bind_param($stmt, "ii", $task_id, $current_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = "Task started successfully!";
    } elseif ($action == 'complete') {
        $stmt = mysqli_prepare($conn, "UPDATE housekeeping_tasks SET status = 'completed', completed_at = NOW() WHERE id = ? AND assigned_to = ?");
        mysqli_stmt_bind_param($stmt, "ii", $task_id, $current_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Update room status to available
        $stmt = mysqli_prepare($conn, "SELECT room_id FROM housekeeping_tasks WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $task_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $room_id = mysqli_fetch_assoc($result)['room_id'];
        mysqli_stmt_close($stmt);

        mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE id = $room_id");
        $success = "Room cleaned successfully!";
    }
}

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// Fetch tasks from this housekeeping staff
$tasks = mysqli_query($conn, "
    SELECT ht.*, r.room_number, rt.type_name
    FROM housekeeping_tasks ht
    JOIN rooms r ON ht.room_id = r.id
    JOIN room_types rt ON r.type_id = rt.id
    WHERE ht.assigned_to = $current_user_id
    AND ht.status IN ('pending', 'in_progress')
    ORDER BY ht.assigned_at DESC
");

// Total tasks and completed today
$total_tasks = mysqli_num_rows($tasks);
$completed_today = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as cnt FROM housekeeping_tasks 
    WHERE assigned_to = {$_SESSION['user_id']} 
    AND status = 'completed' 
    AND DATE(completed_at) = CURDATE()
"))['cnt'];


$page_title = "My Cleaning Tasks";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Cleaning Tasks</h1>
            <div class="text-end">
                <p class="mb-0">Welcome, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>
                <small class="text-muted"><?= date('l, d F Y') ?></small>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-left-warning shadow h-100 py-4">
                    <div class="card-body text-center">
                        <h1 class="display-4 fw-bold <?= $total_tasks > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= $total_tasks ?>
                        </h1>
                        <h4 class="mt-2">Pending / In progress Tasks</h4>
                        <p class="text-muted">
                            Completed today: <strong><?= $completed_today ?></strong> / <?= $total_tasks + $completed_today ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (mysqli_num_rows($tasks) > 0): ?>
        <div class="row">
            <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
                <?php 
                    $hours = $task['hours_since_checkout'] ?? 0;
                    $priority = 'Normal';
                    $badge_class = 'bg-secondary';

                    if (stripos($task['notes'], 'VIP') !== false || stripos($task['notes'], 'urgent') !== false) {
                        $priority = 'High Priority';
                        $badge_class = 'bg-danger';
                    } elseif ($hours > 2) {
                        $priority = 'High Priority';
                        $badge_class = 'bg-danger';
                    } elseif ($hours >= 1 && $hours <= 2) {
                        $priority = 'Medium Priority';
                        $badge_class = 'bg-warning';
                    }    
                ?>
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Room <?= htmlspecialchars($task['room_number']) ?></h6>
                        <span class="badge bg-<?= $task['status'] == 'in_progress' ? 'warning' : 'info' ?>">
                            <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p><strong>Type:</strong> <?= htmlspecialchars($task['type_name']) ?></p>
                        <p class="mb-2"><strong>Guest:</strong> <?= htmlspecialchars($task['guest_name'] ?? 'Hakuna') ?></p>
                        <p class="mb-2"><strong>Status:</strong> 
                            <span class="badge bg-<?= $task['status'] === 'in_progress' ? 'warning' : 'info' ?> fs-6">
                                <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                            </span>
                        </p>
                        <p><strong>Assigned:</strong> <?= date('d/m/Y H:i', strtotime($task['assigned_at'])) ?></p>
                        <?php if ($task['notes']): ?>
                            <p><strong>Notes:</strong> <?= htmlspecialchars($task['notes']) ?></p>
                        <?php endif; ?>
                        <p class="mb-2"><strong>Hours since assigned:</strong> <?= $hours > 0 ? $hours . ' hour(s) ago' : 'New' ?></p>

                        <div class="mt-4 text-center">
                            <?php if ($task['status'] == 'pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="action" value="start">
                                    <button type="submit" class="btn btn-warning btn-lg px-5">
                                        <i class="fas fa-play me-2"></i> Start Cleaning
                                    </button>
                                </form>
                            <?php elseif ($task['status'] == 'in_progress'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-check me-2"></i> Finish Cleaning
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Progress Summary -->
        <div class="card shadow mt-4 pb-4 pt-2">
            <div class="card-body text-center py-4">
                <h5 class="mb-3">Progress Today</h5>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                        style="width: <?= $completed_today > 0 ? ($completed_today / ($total_tasks + $completed_today)) * 100 : 0 ?>%;" 
                        aria-valuenow="<?= $completed_today ?>" aria-valuemin="0" aria-valuemax="<?= $total_tasks + $completed_today ?>">
                        <?= $completed_today ?> / <?= $total_tasks + $completed_today ?> Completed
                    </div>
                </div>
                <p class="mt-3 text-muted">You are doing great! Your tasks are progressing well.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
            <h3 class="text-success">Congrats!</h3>
            <p class="lead text-muted">No pending tasks at the moment. Rooms are clean.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>