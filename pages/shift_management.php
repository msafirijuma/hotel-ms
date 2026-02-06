<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

// testData function to sanitize input
function testData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$success = $error = '';

// Add new shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shift']) ) {
    $name = testData($_POST['name']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO shifts (name, start_time, end_time) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $start_time, $end_time);
    if ($stmt->execute()) {
        $success = "New shift added.";
    } else {
        $error = "Error adding new shift.";
    }
    $stmt->close();
}

// Edit shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_shift'])) {
    $id = (int)$_POST['id'];
    $name = testData($_POST['name']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("UPDATE shifts SET name = ?, start_time = ?, end_time = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $start_time, $end_time, $id);
    if ($stmt->execute()) {
        $success = "Shift updated successfully.";
    } else {
        $error = "Error updating shift.";
    }
    $stmt->close();
}

// Delete shift
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM shifts WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Shift deleted successfully.";
    } else {
        $error = "Error deleting shift.";
    }
    $stmt->close();
}

// Fetch shifts
$shifts = mysqli_query($conn, "SELECT * FROM shifts ORDER BY start_time");

$page_title = "Shift Management";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <h1 class="h3 mb-4 text-gray-800">Shift Management</h1>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
             <div class="alert alert-danger alert-dismissible fade show">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add New Shift Form -->
        <div class="card shadow mb-4">
            <div class="card-header">Add New Shift</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Shift Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="Morning">
                        </div>
                        <div class="col-md-4">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="submit" name="add_shift" class="btn btn-primary" title="Add New Shift">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- List of Shifts -->
        <div class="card shadow">
            <div class="card-header">Available Shift</div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($shifts) > 0): ?>
                            <?php while ($shift = mysqli_fetch_assoc($shifts)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($shift['name']) ?></td>
                                    <td><?= $shift['start_time'] ?></td>
                                    <td><?= $shift['end_time'] ?></td>
                                    <td>
                                        <a href="?edit=<?= $shift['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="?delete=<?= $shift['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('This action can\'t be undone. Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No shift yet. Add new shift at the top.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>