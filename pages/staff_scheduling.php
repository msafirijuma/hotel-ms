<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';

// Handle form submission (multi-date range)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_staff'])) {
    $user_id = (int)$_POST['user_id'];
    $shift_id = (int)$_POST['shift_id'];
    $start_date = testData($_POST['start_date']);
    $end_date = testData($_POST['end_date']);
    $notes = testData($_POST['notes'] ?? '');

    if ($user_id <= 0 || $shift_id <= 0 || empty($start_date) || empty($end_date) || $start_date > $end_date) {
        $error = "Choose a correct staff, shift, na date (start ≤ end).";
    } else {
        // Loop kwa kila siku katika range
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $stmt = $conn->prepare("
                INSERT INTO staff_schedules (user_id, id, shift_date, notes, created_by) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    id = VALUES(id), 
                    notes = VALUES(notes), 
                    created_by = VALUES(created_by)
            ");
            $stmt->bind_param("iissi", $user_id, $shift_id, $current_date, $notes, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            // Next day
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }
        $success = "Schedule ime-save kwa tarehe zote kutoka $start_date hadi $end_date!";
    }
}

// Fetch housekeepers
$housekeepers = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'housekeeping' ORDER BY name");

// Fetch shifts
$shifts = mysqli_query($conn, "SELECT * FROM shifts ORDER BY start_time");

// Fetch today's schedules (example)
$schedules = mysqli_query($conn, "
    SELECT ss.*, u.name as staff_name, sh.name as shift_name
    FROM staff_schedules ss
    JOIN users u ON ss.user_id = u.id
    JOIN shifts sh ON ss.id = sh.id
    WHERE DATE(ss.shift_date) = CURDATE()
    ORDER BY ss.shift_date DESC
");

$page_title = "Staff Scheduling";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <h1 class="h3 mb-4 text-gray-800">Staff Scheduling (Housekeeping)</h1>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card shadow mb-5">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Schedule Staff by Shift (Multi-Date Range)</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Choose Staff (Housekeeper)</label>
                            <select name="user_id" class="form-control searchable-dropdown" required>
                                <option value="">Search housekeeper...</option>
                                <?php while ($staff = mysqli_fetch_assoc($housekeepers)): ?>
                                    <option value="<?= $staff['id'] ?>">
                                        <?= htmlspecialchars($staff['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">choose Shift</label>
                            <select name="shift_id" class="form-control searchable-dropdown" required>
                                <option value="">Search shift...</option>
                                <?php while ($shift = mysqli_fetch_assoc($shifts)): ?>
                                    <option value="<?= $shift['id'] ?>">
                                        <?= htmlspecialchars($shift['name']) ?> 
                                        (<?= $shift['start_time'] ?> – <?= $shift['end_time'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Starting Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ending Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Notes (optional)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Example: Staff member will be covering extra shift this week"></textarea>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" name="schedule_staff" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-calendar-check me-2"></i> Save Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Schedules for Today -->
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Schedules za Leo (<?= date('d/m/Y') ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($schedules) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Shift</th>
                                    <th>Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sched = mysqli_fetch_assoc($schedules)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sched['staff_name']) ?></td>
                                        <td><?= htmlspecialchars($sched['shift_name']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($sched['shift_date'])) ?></td>
                                        <td><?= htmlspecialchars($sched['notes'] ?: '-') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-times fa-5x mb-3"></i>
                        <h5>No Schedule for Today</h5>
                        <p>Add a schedule above to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Select2 Initialization -->
<script>
$(document).ready(function() {
    $('.searchable-dropdown').select2({
        placeholder: "Tafuta hapa...",
        allowClear: true,
        theme: "bootstrap5",
        width: '100%',
        minimumInputLength: 0
    });
});
</script>

<?php include '../includes/footer.php'; ?>