<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';
$upload_dir = '../uploads/room_types/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = trim($_POST['type_name']);
    $price = (float)$_POST['price_per_night'];
    $max_adults = (int)$_POST['max_adults'];
    $max_children = (int)$_POST['max_children'];
    $description = trim($_POST['description'] ?? '');
    $image_path = ''; // Main image

    // === Validation ===
    if (empty($type_name)) {
        $error = "Type name is required.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0.";
    } elseif ($max_adults < 1) {
        $error = "At least one adult is required.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM room_types WHERE type_name = '$type_name'")) > 0) {
        $error = "This room type already exists.";
    } else {
        // === Main Image Upload ===
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['main_image']['size'] <= 5*1024*1024) {
                $main_name = uniqid('main_') . '.' . $ext;
                $main_path = $upload_dir . $main_name;
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $main_path)) {
                    $image_path = 'uploads/room_types/' . $main_name;
                }
            } else {
                $error = "Picha kuu si format sahihi au kubwa sana.";
            }
        }

        if (empty($error)) {
            // === Insert Room Type ===
            $stmt = mysqli_prepare($conn, "INSERT INTO room_types (type_name, price_per_night, max_adults, max_children, description, image) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sdiiss", $type_name, $price, $max_adults, $max_children, $description, $image_path);
            mysqli_stmt_execute($stmt);
            $new_type_id = mysqli_insert_id($conn);

            // === Gallery Images Upload ===
            if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
                $files = $_FILES['gallery'];
                $first_gallery_path = '';
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] == 0) {
                        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $files['size'][$i] <= 5*1024*1024) {
                            $gal_name = uniqid('gal_') . '.' . $ext;
                            $gal_path = $upload_dir . $gal_name;
                            if (move_uploaded_file($files['tmp_name'][$i], $gal_path)) {
                                $gal_db_path = 'uploads/room_types/' . $gal_name;
                                $is_primary = empty($image_path) && empty($first_gallery_path) ? 1 : 0; // First gallery becomes primary if no main
                                mysqli_query($conn, "INSERT INTO room_type_images (room_type_id, image_path, is_primary) VALUES ($new_type_id, '$gal_db_path', $is_primary)");
                                if ($is_primary) $first_gallery_path = $gal_db_path;
                            }
                        }
                    }
                }

                // === Auto Set First Gallery as Primary if no main image ===
                if (empty($image_path) && $first_gallery_path) {
                    mysqli_query($conn, "UPDATE room_types SET image = '$first_gallery_path' WHERE id = $new_type_id");
                    mysqli_query($conn, "UPDATE room_type_images SET is_primary = 1 WHERE image_path = '$first_gallery_path'");
                }
            }

            $success = "New room type added successfully.";
            $_POST = [];
        }
    }
}

$page_title = "Add New Room Type";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Add New Room Type</h1>
            <a href="room_types.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
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

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-primary">Basic Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Type Name <span class="text-danger">*</span></label>
                                <input type="text" name="type_name" class="form-control" value="<?= $_POST['type_name'] ?? '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price Per Night (TZS) <span class="text-danger">*</span></label>
                                <input type="number" name="price_per_night" class="form-control" min="1000" step="1000" value="<?= $_POST['price_per_night'] ?? '' ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Max Adults <span class="text-danger">*</span></label>
                                    <input type="number" name="max_adults" class="form-control" min="1" value="<?= $_POST['max_adults'] ?? '2' ?>" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Max Children</label>
                                    <input type="number" name="max_children" class="form-control" min="0" value="<?= $_POST['max_children'] ?? '0' ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="5"><?= $_POST['description'] ?? '' ?></textarea>
                            </div>
                        </div>

                        <!-- Right Column - Images -->
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-primary">Room Images</h5>
                            <div class="mb-4">
                                <label class="form-label">Main Image (Primary)</label>
                                <input type="file" name="main_image" class="form-control" accept="image/*">
                                <small class="text-muted">Appears in main list • JPG, PNG, WebP • Max 5MB</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Additional Image (Gallery)</label>
                                <input type="file" name="gallery[]" class="form-control" multiple accept="image/*">
                                <small class="text-muted">Additional images for gallery view • JPG, PNG, WebP • Max 5MB each</small>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="submit" class="btn btn-success btn-lg px-5 shadow">
                            <i class="fas fa-save me-2"></i> Save Room Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>