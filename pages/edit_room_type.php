<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$type_id = (int)($_GET['id'] ?? 0);
$success = $error = '';

if ($type_id == 0) {
    header("Location: room_types.php");
    exit();
}

// Fetch current type
$type = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM room_types WHERE id = $type_id"));
if (!$type) {
    $error = "Aina haipatikani.";
}

$upload_dir = '../uploads/room_types/';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = trim($_POST['type_name']);
    $price = (float)$_POST['price_per_night'];
    $max_adults = (int)$_POST['max_adults'];
    $max_children = (int)$_POST['max_children'];
    $description = trim($_POST['description'] ?? '');
    $image_path = $type['image']; // Keep old

    if (empty($type_name) || $price <= 0 || $max_adults < 1) {
        $error = "Jaza field zote za msingi vizuri.";
    } elseif (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM room_types WHERE type_name = '$type_name' AND id != $type_id")) > 0) {
        $error = "Jina hili tayari linatumika.";
    } else {
        // Main image update
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['main_image']['size'] <= 5*1024*1024) {
                $new_name = uniqid('main_') . '.' . $ext;
                $path = $upload_dir . $new_name;
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $path)) {
                    // Delete old main
                    if ($type['image'] && file_exists('../' . $type['image'])) unlink('../' . $type['image']);
                    $image_path = 'uploads/room_types/' . $new_name;
                }
            }
        }

        // Gallery upload
        $first_new_gallery = '';
        if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
            $files = $_FILES['gallery'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] == 0) {
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $files['size'][$i] <= 5*1024*1024) {
                        $gal_name = uniqid('gal_') . '.' . $ext;
                        $gal_path = $upload_dir . $gal_name;
                        if (move_uploaded_file($files['tmp_name'][$i], $gal_path)) {
                            $gal_db = 'uploads/room_types/' . $gal_name;
                            mysqli_query($conn, "INSERT INTO room_type_images (room_type_id, image_path, is_primary) VALUES ($type_id, '$gal_db', 0)");
                            if (empty($first_new_gallery)) $first_new_gallery = $gal_db;
                        }
                    }
                }
            }
        }

        // Auto set first new gallery as primary if no main image
        if (empty($image_path) && $first_new_gallery) {
            $image_path = $first_new_gallery;
            mysqli_query($conn, "UPDATE room_type_images SET is_primary = 1 WHERE image_path = '$first_new_gallery'");
        }

        // Update room type
        $stmt = mysqli_prepare($conn, "UPDATE room_types SET type_name = ?, price_per_night = ?, max_adults = ?, max_children = ?, description = ?, image = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sdiissi", $type_name, $price, $max_adults, $max_children, $description, $image_path, $type_id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Aina imehaririwa kikamilifu!";
            $type = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM room_types WHERE id = $type_id"));
        } else {
            $error = "Hitilafu wakati wa kuhifadhi.";
        }
    }
}

$page_title = "Hariri Aina - " . htmlspecialchars($type['type_name'] ?? '');
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Hariri Aina ya Chumba</h1>
            <a href="room_types.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Rudi
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

        <?php if ($type): ?>
        <div class="card shadow">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-primary">Maelezo ya Msingi</h5>
                            <div class="mb-3">
                                <label class="form-label">Jina la Aina</label>
                                <input type="text" name="type_name" class="form-control" value="<?= htmlspecialchars($type['type_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bei kwa Usiku</label>
                                <input type="number" name="price_per_night" class="form-control" value="<?= $type['price_per_night'] ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Watu Wazima Max</label>
                                    <input type="number" name="max_adults" class="form-control" value="<?= $type['max_adults'] ?>" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Watoto Max</label>
                                    <input type="number" name="max_children" class="form-control" value="<?= $type['max_children'] ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maelezo</label>
                                <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($type['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h5 class="mb-3 text-primary">Picha za Chumba</h5>
                            <div class="mb-4">
                                <label class="form-label">Picha Kuu ya Sasa</label>
                                <div class="text-center">
                                    <?php if ($type['image']): ?>
                                        <img src="../<?= $type['image'] ?>" class="img-fluid rounded shadow" style="max-height: 250px;">
                                    <?php else: ?>
                                        <p class="text-muted">Hakuna picha kuu</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Badilisha Picha Kuu</label>
                                <input type="file" name="main_image" class="form-control" accept="image/*">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ongeza Picha za Ziada (Gallery)</label>
                                <input type="file" name="gallery[]" class="form-control" multiple accept="image/*">
                                <small class="text-muted">Picha nyingi (max 10)</small>
                            </div>

                            <!-- Existing Gallery -->
                            <?php
                            $gallery = mysqli_query($conn, "SELECT id, image_path FROM room_type_images WHERE room_type_id = $type_id ORDER BY is_primary DESC, uploaded_at");
                            if (mysqli_num_rows($gallery) > 0): ?>
                            <div class="mt-4">
                                <label class="form-label">Picha Zilizopo (Gallery)</label>
                                <div class="row g-3">
                                    <?php while ($img = mysqli_fetch_assoc($gallery)): ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 position-relative">
                                        <img src="../<?= $img['image_path'] ?>" class="img-fluid rounded shadow" style="height: 130px; object-fit: cover;">
                                        <a href="delete_gallery_image.php?id=<?= $img['id'] ?>&redirect=edit_room_type.php?id=<?= $type_id ?>" 
                                           class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" 
                                           onclick="return confirm('Futa picha hii?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="submit" class="btn btn-warning btn-lg px-5 shadow">
                            <i class="fas fa-save me-2"></i> Hifadhi Mabadiliko
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>