<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';

// Calculate current shift
$current_hour = (int)date('H');
$current_shift = ($current_hour >= 6 && $current_hour < 14 ? 'morning' :
                 ($current_hour >= 14 && $current_hour < 22 ? 'afternoon' : 'night'));

// Handle manual assign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $room_id = (int)$_POST['room_id'];
    $assigned_to = (int)$_POST['assigned_to'];
    $notes = trim($_POST['notes'] ?? '');

    if ($room_id <= 0 || $assigned_to <= 0) {
        $error = "Choose a correct room and housekeeper.";
    } else {
        $stmt = $conn->prepare("INSERT INTO housekeeping_tasks (room_id, assigned_to, notes, assigned_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $room_id, $assigned_to, $notes, $_SESSION['user_id']);
        if ($stmt->execute()) {
            mysqli_query($conn, "UPDATE rooms SET status = 'under_cleaning' WHERE id = $room_id");
            $success = "Task assigned successfully!";
        } else {
            $error = "Error during assignment.";
        }
        $stmt->close();
    }
}

// Handle auto-assign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign'])) {
    $room_id = (int)$_POST['room_id'];

    // Find least loaded (prioritize current shift)
    $least_loaded = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT u.id, u.name, u.shift,
               COUNT(ht.id) as total_tasks
        FROM users u
        LEFT JOIN housekeeping_tasks ht ON u.id = ht.assigned_to 
            AND ht.status IN ('pending', 'in_progress')
            AND DATE(ht.assigned_at) = CURDATE()
        WHERE u.role = 'housekeeping'
        GROUP BY u.id
        ORDER BY 
            CASE WHEN u.shift = '$current_shift' THEN 1 ELSE 2 END ASC,
            total_tasks ASC,
            u.name ASC
        LIMIT 1
    "));

    if ($least_loaded && $least_loaded['id']) {
        $assigned_to = $least_loaded['id'];
        $notes = "Auto-assigned (least loaded, " . ucfirst($least_loaded['shift']) . " shift)";

        $stmt = $conn->prepare("INSERT INTO housekeeping_tasks (room_id, assigned_to, notes, assigned_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $room_id, $assigned_to, $notes, $_SESSION['user_id']);
        if ($stmt->execute()) {
            mysqli_query($conn, "UPDATE rooms SET status = 'under_cleaning' WHERE id = $room_id");
            $success = "Task auto assigned to {$least_loaded['name']} ({$least_loaded['total_tasks']} tasks today)!";
        } else {
            $error = "Error during auto-assign.";
        }
        $stmt->close();
    } else {
        $error = "No housekeeper found.";
    }
}

// Fetch dirty rooms
$dirty_rooms = mysqli_query($conn, "
    SELECT r.id, r.room_number, rt.type_name 
    FROM rooms r 
    JOIN room_types rt ON r.type_id = rt.id 
    WHERE r.status = 'dirty' 
    ORDER BY r.room_number
");

// Fetch housekeepers with load & shift
$housekeepers = mysqli_query($conn, "
    SELECT * FROM (
        SELECT u.id, u.name, u.shift,
               COUNT(CASE WHEN ht.status = 'pending' THEN 1 END) as pending_tasks,
               COUNT(CASE WHEN ht.status = 'in_progress' THEN 1 END) as in_progress,
               (COUNT(CASE WHEN ht.status = 'pending' THEN 1 END) + 
                COUNT(CASE WHEN ht.status = 'in_progress' THEN 1 END)) as total_load
        FROM users u
        LEFT JOIN housekeeping_tasks ht ON u.id = ht.assigned_to 
            AND ht.status IN ('pending', 'in_progress')
            AND DATE(ht.assigned_at) = CURDATE()
        WHERE u.role = 'housekeeping'
        GROUP BY u.id
    ) AS sub
    ORDER BY 
        CASE WHEN sub.shift = '$current_shift' THEN 1 ELSE 2 END ASC,
        sub.total_load ASC,
        sub.name ASC
");

$page_title = "Assign Housekeeping Task";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <h1 class="h3 mb-4 text-gray-800">Assign Housekeeping Task</h1>

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
            <div class="card-body">
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Choose Dirty Room</label>
                        <select name="room_id" class="form-control searchable-dropdown" required>
                            <option value="">Choose a dirty room ...</option>
                            <?php while ($room = mysqli_fetch_assoc($dirty_rooms)): ?>
                                <option value="<?= $room['id'] ?>">
                                    <?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['type_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Choose Housekeeper</label>
                        <p class="small text-muted mb-2">
                            <span class="badge bg-success me-2">Current Shift (<?= ucfirst($current_shift) ?>)</span>
                            Sorted by current shift first, then least load
                        </p>
                        <select name="assigned_to" class="form-control searchable-dropdown" required>
                            <option value="">Choose housekeeper...</option>
                            <?php while ($staff = mysqli_fetch_assoc($housekeepers)): ?>
                                <option value="<?= $staff['id'] ?>">
                                    <?= htmlspecialchars($staff['name']) ?> 
                                    <span class="badge bg-<?= $staff['shift'] === $current_shift ? 'success' : 'secondary' ?> ms-2">
                                        <?= ucfirst($staff['shift']) ?>
                                    </span>
                                    - Pending: <?= $staff['pending_tasks'] ?>, In Progress: <?= $staff['in_progress'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Additional Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Example: VIP - balcony very clean"></textarea>
                    </div>

                    <div class="d-flex gap-3 justify-content-end">
                        <button type="submit" name="assign" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-check me-2"></i> Assign Manually
                        </button>
                        <button type="submit" name="auto_assign" class="btn btn-info btn-lg px-5">
                            <i class="fas fa-robot me-2"></i> Auto-Assign (Least Load)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Select2 + Highlight Current Shift -->
<script>
$(document).ready(function() {
    $('.searchable-dropdown').select2({
        placeholder: "Tafuta hapa...",
        allowClear: true,
        theme: "bootstrap5",
        width: '100%',
        minimumInputLength: 0,
        templateResult: function(data) {
            if (!data.id) return data.text;
            let $option = $('<span>' + data.text + '</span>');
            if ($(data.element).data('current') === 'true') {
                $option.css('font-weight', 'bold').css('color', '#28a745');
            }
            return $option;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>