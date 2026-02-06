<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';
$search = trim($_GET['search'] ?? '');

// Handle Delete Room Type
if (isset($_GET['delete'])) {
    $type_id = (int)$_GET['delete'];
    $used = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM rooms WHERE type_id = $type_id"))['cnt'];
    if ($used > 0) {
        $error = "You cannot delete this type – is being used by rooms $used.";
    } else {
        $image = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM room_types WHERE id = $type_id"))['image'];
        if (mysqli_query($conn, "DELETE FROM room_types WHERE id = $type_id")) {
            if ($image && file_exists('../' . $image)) {
                unlink('../' . $image);
            }
            $success = "Room type has been deleted successfully!";
        } else {
            $error = "Error while delete room type.";
        }
    }
}

// Search & Fetch
$sql = "SELECT * FROM room_types";
if ($search) {
    $sql .= " WHERE type_name LIKE ? OR description LIKE ?";
}
$sql .= " ORDER BY type_name";
$stmt = $conn->prepare($sql);
if ($search) {
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
}
$stmt->execute();
$types = $stmt->get_result();

$page_title = "Room Types";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Room Types (<?= $types->num_rows ?>)</h1>
            <a href="add_room_type.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Type
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

        <!-- Search -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or details..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <?php if ($search): ?>
                            <a href="room_types.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr class="mb-1">
                                <th>#</th>
                                <th>Main Image</th>
                                <th>Type</th>
                                <th>Price per night</th>
                                <th>Adult / Children</th>
                                <th>Details</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($types->num_rows > 0): $no = 1; ?>
                                <?php while ($type = $types->fetch_assoc()): ?>
                                <tr class="mb-2">
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <?php if ($type['image']): ?>
                                            <img src="../<?= $type['image'] ?>" 
                                                 class="img-thumbnail rounded shadow main-room-image room-image" 
                                                 style="width: 140px; height: 90px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" 
                                                 style="width: 140px; height: 90px;">
                                                <small>No image</small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($type['type_name']) ?></strong></td>
                                    <td>TZS <?= number_format($type['price_per_night'], 0) ?></td>
                                    <td><?= $type['max_adults'] ?> / <?= $type['max_children'] ?></td>
                                    <td><?= htmlspecialchars(substr($type['description'] ?? '', 0, 80)) ?>...</td>
                                    <td class="text-center">
                                        <a href="edit_room_type.php?id=<?= $type['id'] ?>" title="Edit Room Type" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete Room Type" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $type['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- GALLERY WITH SET PRIMARY -->
                                <?php
                                $gallery = mysqli_query($conn, "SELECT id, image_path, is_primary FROM room_type_images WHERE room_type_id = {$type['id']} ORDER BY is_primary DESC, uploaded_at");
                                if (mysqli_num_rows($gallery) > 0): ?>
                                <tr>
                                    <td colspan="7" class="bg-light border-0 p-0">
                                        <div class="p-3 gallery-row">
                                            <small class="text-muted fw-bold d-block mb-2">Gallery Images:</small>
                                            <div class="row g-3">
                                                <?php while ($img = mysqli_fetch_assoc($gallery)): ?>
                                                <div class="col-lg-2 col-md-3 col-sm-4 col-6 position-relative">
                                                    <img src="../<?= $img['image_path'] ?>" 
                                                         class="img-fluid rounded shadow room-image" 
                                                         style="height: 130px; width: 100%; object-fit: cover;">
                                                    
                                                    <!-- Delete -->
                                                    <a href="delete_gallery_image.php?id=<?= $img['id'] ?>&redirect=room_types.php" 
                                                       class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 shadow" 
                                                       onclick="return confirm('Delete this image?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>

                                                    <!-- Set Primary -->
                                                    <?php if (!$img['is_primary']): ?>
                                                    <a href="set_primary_image.php?id=<?= $img['id'] ?>&redirect=room_types.php" 
                                                       class="btn btn-success btn-sm position-absolute bottom-0 end-0 m-2 shadow" 
                                                       title="Set as a primary image">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="badge bg-success position-absolute top-0 start-0 m-2">Primary</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?= $type['id'] ?>">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                Are you sure you want to delete room type <strong><?= htmlspecialchars($type['type_name']) ?></strong>?
                                            </div>
                                            <div class="modal-footer justify-content-center">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="?delete=<?= $type['id'] ?>" class="btn btn-danger">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No room types found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hover Effect -->
<style>
.gallery-row .room-image {
    transition: all 0.3s ease;
}
.gallery-row .room-image:hover {
    /* transform: translateY(-8px); */
    transform: translate3d(4px, 10px, 40px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}
</style>

<?php include '../includes/footer.php'; ?>