<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';
$upload_dir = '../uploads/hotel/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Fetch current settings
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hotel_settings WHERE id = 1"));

function testData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hotel_name = testData($_POST['hotel_name']);
    $tagline = testData($_POST['tagline'] ?? '');
    $address = testData($_POST['address'] ?? '');
    $phone = testData($_POST['phone'] ?? '');
    $email = testData($_POST['email'] ?? '');
    $website = testData($_POST['website'] ?? '');
    $tin = testData($_POST['tin'] ?? '');
    $footer_message = testData($_POST['footer_message'] ?? '');
    $logo_path = $settings['logo_path'];

    // Logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2*1024*1024) {
            $new_name = 'logo.' . $ext;
            $path = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $path)) {
                $logo_path = 'uploads/hotel/' . $new_name;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE hotel_settings SET hotel_name = ?, tagline = ?, address = ?, phone = ?, email = ?, website = ?, tin = ?, logo_path = ?, footer_message = ? WHERE id = 1");
    $stmt->bind_param("sssssssss", $hotel_name, $tagline, $address, $phone, $email, $website, $tin, $logo_path, $footer_message);
    if ($stmt->execute()) {
        $success = "Hotel's settings updated successfully!";
        $settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hotel_settings WHERE id = 1"));
    } else {
        $error = "Error saving settings.";
    }
}

$page_title = "Hotel Settings";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Hotel's Settings</h1>
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
                        <div class="col-lg-6">
                            <h5 class="mb-3 text-primary">Basic Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Hotel's Name<span class="text-danger">*</span></label>
                                <input type="text" name="hotel_name" class="form-control" value="<?= htmlspecialchars($settings['hotel_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tagline / Slogan</label>
                                <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($settings['tagline'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($settings['website'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TIN / Tax ID</label>
                                    <input type="text" name="tin" class="form-control" value="<?= htmlspecialchars($settings['tin'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Footer Message (Invoice/Receipt)</label>
                                <textarea name="footer_message" class="form-control" rows="3"><?= htmlspecialchars($settings['footer_message']) ?></textarea>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h5 class="mb-3 text-primary">Hotel's Logo</h5>
                            <div class="text-center mb-4">
                                <?php if ($settings['logo_path']): ?>
                                    <img src="../<?= $settings['logo_path'] ?>" class="img-fluid rounded shadow" style="max-height: 200px;">
                                <?php else: ?>
                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <p class="text-muted">No logo uploaded yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Change Logo (Max 2MB)</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, WebP • Will be seen on invoices and receipts</small>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                            <i class="fas fa-save me-2"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>