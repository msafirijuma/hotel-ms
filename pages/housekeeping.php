<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'housekeeping'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';
$current_user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

// Handle task actions
if (isset($_POST['action'])) {
    $task_id = (int)$_POST['task_id'];
    $action = $_POST['action'];

    if ($action == 'start') {
        mysqli_query($conn, "UPDATE housekeeping_tasks SET status = 'in_progress' WHERE id = $task_id");
        $success = "You have started this task!";
    } elseif ($action == 'complete') {
        mysqli_query($conn, "UPDATE housekeeping_tasks SET status = 'completed', completed_at = NOW() WHERE id = $task_id");
        // Auto update room status to available
        $room_id = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_id FROM housekeeping_tasks WHERE id = $task_id"))['room_id'];
        mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE id = $room_id");
        $success = "Room has been cleaned and is ready!";
    }
}

// Handle new task assignment (admin only)
if ($is_admin && isset($_POST['assign_task'])) {
    $room_id = (int)$_POST['room_id'];
    $assigned_to = (int)$_POST['assigned_to'];
    $notes = trim($_POST['notes'] ?? '');

    $stmt = mysqli_prepare($conn, "INSERT INTO housekeeping_tasks (room_id, assigned_to, assigned_by, notes, priority) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiiss", $room_id, $assigned_to, $current_user_id, $notes, $priority);
    $priority = $_POST['priority'] ?? 'medium';
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Task has been assigned successfully!";
    } else {
        $error = "Error assigning task.";
    }
}

// Fetch housekeeping staff for assignment dropdown
$hk_staff = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'housekeeping' ORDER BY name");

// Fetch tasks based on role
if ($is_admin) {
    // Admin see all tasks
    $tasks = mysqli_query($conn, "
        SELECT ht.*, r.room_number, rt.type_name, u1.name as assigned_to_name, u2.name as assigned_by_name
        FROM housekeeping_tasks ht
        JOIN rooms r ON ht.room_id = r.id
        JOIN room_types rt ON r.type_id = rt.id
        JOIN users u1 ON ht.assigned_to = u1.id
        JOIN users u2 ON ht.assigned_by = u2.id
        WHERE ht.status IN ('pending', 'in_progress')
        ORDER BY ht.assigned_at DESC
    ");
} else {
    // Housekeeping staff see all his/her tasks
    $tasks = mysqli_query($conn, "
        SELECT ht.*, r.room_number, rt.type_name
        FROM housekeeping_tasks ht
        JOIN rooms r ON ht.room_id = r.id
        JOIN room_types rt ON r.type_id = rt.id
        WHERE ht.assigned_to = $current_user_id
        AND ht.status IN ('pending', 'in_progress')
        ORDER BY ht.assigned_at DESC
    ");
}

// Fetch dirty rooms for assignment (admin only)
$dirty_rooms = mysqli_query($conn, "SELECT id, room_number FROM rooms WHERE status = 'dirty' ORDER BY room_number");

$page_title = "Housekeeping Tasks";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Housekeeping Tasks</h1>
            <?php if ($is_admin): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                <i class="fas fa-plus"></i> Assign New Task
            </button>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Your Task<?= $is_admin ? ' (All)' : '' ?></h6>
            </div>
            <div class="card-body">
                <?php if ($tasks->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Room</th>
                                <?php if ($is_admin): ?>
                                <th>Assigned To</th>
                                <th>Assigned By</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $tasks->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($task['room_number']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($task['type_name']) ?></small>
                                </td>
                                <?php if ($is_admin): ?>
                                <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                                <td><?= htmlspecialchars($task['assigned_by_name']) ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge bg-<?= $task['status'] == 'in_progress' ? 'warning' : 'info' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $task['priority'] == 'urgent' ? 'danger' :
                                        ($task['priority'] == 'high' ? 'warning' :
                                        ($task['priority'] == 'medium' ? 'primary' : 'secondary'))
                                    ?>">
                                        <?= ucfirst($task['priority']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($task['assigned_at'])) ?></td>
                                <td class="text-center">
                                    <?php if (!$is_admin || $task['assigned_to'] == $current_user_id): ?>
                                        <?php if ($task['status'] == 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                <input type="hidden" name="action" value="start">
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Start this job?')">
                                                    <i class="fas fa-play"></i> Start
                                                </button>
                                            </form>
                                        <?php elseif ($task['status'] == 'in_progress'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Confirm you have finished?')">
                                                    <i class="fas fa-check"></i> Finish
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h4 class="text-success">Nothing to work on today!</h4>
                    <p class="text-muted">No dirty room or already assigned!.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assign Task Modal (Admin Only) -->
        <?php if ($is_admin): ?>
        <div class="modal fade" id="assignTaskModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Assign New Task</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-select" required>
                                    <option value="">-- Choose room --</option>
                                    <?php while ($dr = mysqli_fetch_assoc($dirty_rooms)): ?>
                                        <option value="<?= $dr['id'] ?>"><?= $dr['room_number'] ?></option>
                                    <?php endwhile; mysqli_data_seek($dirty_rooms, 0); ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assign Kwa</label>
                                <select name="assigned_to" class="form-select" required>
                                    <option value="">-- Choose Staff --</option>
                                    <?php while ($staff = mysqli_fetch_assoc($hk_staff)): ?>
                                        <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kipaumbele</label>
                                <select name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Exit</button>
                            <button type="submit" name="assign_task" class="btn btn-primary">Assign Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- END MAIN CONTENT WRAPPER -->

<?php include '../includes/footer.php'; ?>