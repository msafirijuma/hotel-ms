<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';

// Sanitize input function
function testData ($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle search input
$search = testData($_GET['search'] ?? '');

// Get total rooms
$total_rooms = get_single_value("SELECT COUNT(*) FROM rooms");

// Quick status update
if (isset($_POST['update_status'])) {
    $room_id = (int)$_POST['room_id'];
    $new_status = $_POST['status'];
    $allowed = ['available', 'occupied', 'dirty', 'maintenance'];
    if (in_array($new_status, $allowed)) {
        mysqli_query($conn, "UPDATE rooms SET status = '$new_status' WHERE id = $room_id");
        $success = "Room status has been updated";
    }
}

// Search & Fetch
$sqlRooms = "SELECT r.*, rt.type_name, rt.price_per_night 
        FROM rooms r 
        JOIN room_types rt ON r.type_id = rt.id";

if ($search) {
    $sqlRooms .= " WHERE status LIKE ?";
}
$sqlRooms .= " ORDER BY room_number";
$stmt = $conn->prepare($sqlRooms);

if ($search) {
    $like = "%$search%";
    $stmt->bind_param("s", $like);
}
$stmt->execute();
$rooms = $stmt->get_result();

$page_title = "List of rooms";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">List of rooms</h1>
            <a href="add_room.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add room
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Total rooms count -->
        <div class="mb-3">
            <strong>Total Rooms:</strong> <?= $total_rooms ?>
        </div>

        <!-- Search Rooms -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by room status..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <?php if ($search): ?>
                            <a href="rooms.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Room No.</th>
                                <th>Type</th>
                                <th>Price per night</th>
                                <th>Floor</th>
                                <th>Room Status</th>
                                <th>Update Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rooms->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No rooms found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($room = $rooms->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($room['type_name']) ?></td>
                                    <td>TZS <?= number_format($room['price_per_night'], 0) ?></td>
                                    <td><?= htmlspecialchars($room['floor'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $room['status'] == 'available' ? 'success' :
                                            ($room['status'] == 'occupied' ? 'primary' :
                                            ($room['status'] == 'dirty' ? 'danger' : 'warning'))
                                        ?>">
                                            <?= ucfirst($room['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                            <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                                <option value="available" <?= $room['status']=='available'?'selected':'' ?>>Available</option>
                                                <option value="occupied" <?= $room['status']=='occupied'?'selected':'' ?>>Occupied</option>
                                                <option value="dirty" <?= $room['status']=='dirty'?'selected':'' ?>>Dirty</option>
                                                <option value="maintenance" <?= $room['status']=='maintenance'?'selected':'' ?>>Maintenance</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_room.php?id=<?= $room['id'] ?>" class="btn btn-sm btn-warning" title="Edit Room">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete Room" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $room['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?= $room['id'] ?>">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        Do you want to delete this room <strong><?= htmlspecialchars($room['room_number']) ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Exit</button>
                                                        <a href="delete_room.php?id=<?= $room['id'] ?>" class="btn btn-danger">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>