<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$room_id = (int)($_GET['id'] ?? 0);
$success = $error = '';

if ($room_id == 0) {
    header("Location: rooms.php");
    exit();
}

// Fetch current room data
$room_query = mysqli_query($conn, "
    SELECT r.*, rt.type_name, rt.price_per_night, rt.image as type_image 
    FROM rooms r 
    JOIN room_types rt ON r.type_id = rt.id 
    WHERE r.id = $room_id
");

if (mysqli_num_rows($room_query) == 0) {
    $error = "Room not found.";
} else {
    $room = mysqli_fetch_assoc($room_query);
}

// Fetch all room types for dropdown
$types = mysqli_query($conn, "SELECT id, type_name, price_per_night, image FROM room_types ORDER BY type_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_number = trim(strtoupper($_POST['room_number']));
    $type_id = (int)$_POST['type_id'];
    $floor = trim($_POST['floor'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($room_number) || $type_id == 0) {
        $error = "Please fill both room type and room number.";
    } else {
        // Check if room number already exists (except current room)
        $duplicate = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM rooms WHERE room_number = '$room_number' AND id != $room_id"));
        if ($duplicate > 0) {
            $error = "This room number already exists.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_number = ?, type_id = ?, floor = ?, notes = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sissi", $room_number, $type_id, $floor, $notes, $room_id);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Room updated successfully!";
                // Refresh room data after update
                $room_query = mysqli_query($conn, "SELECT r.*, rt.type_name, rt.price_per_night, rt.image as type_image FROM rooms r JOIN room_types rt ON r.type_id = rt.id WHERE r.id = $room_id");
                $room = mysqli_fetch_assoc($room_query);
            } else {
                $error = "Error while saving changes.";
            }
        }
    }
}

$page_title = "Edit Room " . ($room['room_number'] ?? '');
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit Room <?= htmlspecialchars($room['room_number'] ?? '') ?></h1>
            <a href="rooms.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Room List
            </a>
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

        <?php if ($room): ?>
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Room Number <span class="text-danger">*</span></label>
                                <input type="text" name="room_number" class="form-control text-uppercase" 
                                       value="<?= htmlspecialchars($room['room_number']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                <select name="type_id" id="type_id" class="form-select" required onchange="previewImage()">
                                    <option value="">-- Choose Type --</option>
                                    <?php mysqli_data_seek($types, 0); while ($t = mysqli_fetch_assoc($types)): ?>
                                        <option value="<?= $t['id'] ?>" 
                                                data-image="<?= $t['image'] ?? '' ?>"
                                                <?= $t['id'] == $room['type_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['type_name']) ?> (TZS <?= number_format($t['price_per_night'], 0) ?>/usiku)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Floor</label>
                                <input type="text" name="floor" class="form-control" 
                                       value="<?= htmlspecialchars($room['floor'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Preview Room Type</label>
                                <div class="border rounded p-3 bg-light text-center" style="min-height: 220px;">
                                    <img id="preview_img" 
                                         src="../<?= $room['type_image'] ?? 'assets/img/no-image.png' ?>" 
                                         class="img-fluid rounded shadow-sm" 
                                         style="max-height: 200px; object-fit: cover;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="5"><?= htmlspecialchars($room['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-warning btn-lg px-5">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function previewImage() {
    const select = document.getElementById('type_id');
    const img = document.getElementById('preview_img');
    const selected = select.options[select.selectedIndex];
    const imagePath = selected.getAttribute('data-image');

    if (imagePath) {
        img.src = '../' + imagePath;
    } else {
        img.src = '../assets/img/no-image.png';
    }
}

// Run on page load to show current image
previewImage();
</script>

<?php include '../includes/footer.php'; ?>