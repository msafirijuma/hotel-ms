<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';

$types = mysqli_query($conn, "SELECT id, type_name, price_per_night FROM room_types ORDER BY type_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = trim(strtoupper($_POST['room_number']));
    $type_id = (int)$_POST['type_id'];
    $floor = trim($_POST['floor'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($room_number) || $type_id == 0) {
        $error = "Add room number and choose room type.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM rooms WHERE room_number = '$room_number'")) > 0) {
        $error = "This room number already exists.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO rooms (room_number, type_id, floor, notes, status) VALUES (?, ?, ?, ?, 'available')");
        mysqli_stmt_bind_param($stmt, "siss", $room_number, $type_id, $floor, $notes);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Room $room_number added!";
            $_POST = [];
        } else {
            $error = "Error saving room.";
        }
    }
}

$page_title = "Add Room";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Add New Room</h1>
            <a href="rooms.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" class="room-form">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Room Number <span class="text-danger">*</span></label>
                                <input type="text" name="room_number" class="form-control text-uppercase" value="<?= $_POST['room_number'] ?? '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select name="type_id" class="form-select" required>
                                    <option value="">-- Choose --</option>
                                    <?php while ($t = mysqli_fetch_assoc($types)): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['type_name']) ?> (TZS <?= number_format($t['price_per_night'], 0) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Floor</label>
                                <input type="text" name="floor" class="form-control" value="<?= $_POST['floor'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="5"><?= $_POST['notes'] ?? '' ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-success btn-lg room-btn">
                            <i class="fas fa-save"></i> Save Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>