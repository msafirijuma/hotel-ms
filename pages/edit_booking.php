<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$booking_id = (int)($_GET['id'] ?? 0);
$success = $error = '';

if ($booking_id == 0) {
    header("Location: bookings.php");
    exit();
}

// Fetch existing booking
$booking_query = mysqli_query($conn, "
    SELECT b.*, g.name as guest_name, g.phone, g.email, g.id_number, g.address
    FROM bookings b
    LEFT JOIN guests g ON b.guest_id = g.id
    WHERE b.id = $booking_id
");

if (mysqli_num_rows($booking_query) == 0) {
    $error = "Booking haipatikani.";
} else {
    $booking = mysqli_fetch_assoc($booking_query);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $room_id = (int)$_POST['room_id'];
    $guest_name = trim($_POST['guest_name']);
    $guest_phone = trim($_POST['guest_phone']);
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_id_number = trim($_POST['guest_id_number'] ?? '');
    $guest_address = trim($_POST['guest_address'] ?? '');
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];
    $notes = trim($_POST['notes'] ?? '');

    if (empty($check_in) || empty($check_out) || $room_id == 0 || empty($guest_name)) {
        $error = "Please fill all required fields.";
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $error = "Check-in should be before check-out Tarehe ya check-in iwe kabla ya check-out.";
    } else {
        // Check availability (exclude current booking)
        $conflict = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id FROM bookings 
            WHERE room_id = $room_id 
            AND id != $booking_id
            AND booking_status NOT IN ('checked_out', 'cancelled')
            AND (
                (check_in <= '$check_out' AND check_out >= '$check_in')
            )
        "));

        if ($conflict) {
            $error = "Chumba hiki kimeshakwa booked katika tarehe hizi.";
        } else {
            // Update guest
            if ($booking['guest_id']) {
                $stmt = mysqli_prepare($conn, "UPDATE guests SET name = ?, phone = ?, email = ?, id_number = ?, address = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sssssi", $guest_name, $guest_phone, $guest_email, $guest_id_number, $guest_address, $booking['guest_id']);
                mysqli_stmt_execute($stmt);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO guests (name, phone, email, id_number, address) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssss", $guest_name, $guest_phone, $guest_email, $guest_id_number, $guest_address);
                mysqli_stmt_execute($stmt);
                $guest_id = mysqli_insert_id($conn);
            }

            // Calculate new total
            $nights = (strtotime($check_out) - strtotime($check_in)) / 86400;
            $price_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_per_night FROM room_types rt JOIN rooms r ON rt.id = r.type_id WHERE r.id = $room_id"));
            $price = $price_result['price_per_night'] ?? 0;
            $total = $nights * $price;

            // Update old room status if room changed
            if ($booking['room_id'] != $room_id) {
                mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE id = {$booking['room_id']}");
            }

            // Update booking
            $stmt = mysqli_prepare($conn, "UPDATE bookings SET room_id = ?, check_in = ?, check_out = ?, adults = ?, children = ?, total_amount = ?, notes = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "issiiisi", $room_id, $check_in, $check_out, $adults, $children, $total, $notes, $booking_id);

            if (mysqli_stmt_execute($stmt)) {
                // Update new room status if necessary
                $current_date = date('Y-m-d');
                if ($current_date >= $check_in && $current_date < $check_out) {
                    mysqli_query($conn, "UPDATE rooms SET status = 'occupied' WHERE id = $room_id");
                }

                $success = "Booking updated successfully!";
            } else {
                $error = "Error while saving changes.";
            }
        }
    }
}

$page_title = "Edit Booking #" . ($booking['booking_code'] ?? '');
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Edit Booking - <?= $booking['booking_code'] ?? '' ?></h1>
            <a href="bookings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Booking
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

        <?php if ($booking): ?>
        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5 class="mb-3">Staying Period</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-in</label>
                                    <input type="date" name="check_in" class="form-control" value="<?= $booking['check_in'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-out</label>
                                    <input type="date" name="check_out" class="form-control" value="<?= $booking['check_out'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-select" required>
                                    <option value="">-- Choose Room --</option>
                                    <?php 
                                    $all_rooms = mysqli_query($conn, "SELECT r.id, r.room_number, rt.type_name, rt.price_per_night FROM rooms r JOIN room_types rt ON r.type_id = rt.id ORDER BY r.room_number");
                                    while ($r = mysqli_fetch_assoc($all_rooms)): ?>
                                        <option value="<?= $r['id'] ?>" <?= $r['id'] == $booking['room_id'] ? 'selected' : '' ?>>
                                            <?= $r['room_number'] ?> - <?= $r['type_name'] ?> (TZS <?= number_format($r['price_per_night'], 0) ?>/usiku)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Adult</label>
                                    <input type="number" name="adults" class="form-control" min="1" value="<?= $booking['adults'] ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Children</label>
                                    <input type="number" name="children" class="form-control" min="0" value="<?= $booking['children'] ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h5 class="mb-3">Guest's Details</h5>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="guest_name" class="form-control" value="<?= htmlspecialchars($booking['guest_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="guest_phone" class="form-control" value="<?= htmlspecialchars($booking['phone'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="guest_email" class="form-control" value="<?= htmlspecialchars($booking['email'] ?? '') ?>">
                            </div>
                            <!-- Check later if necessary to include in the future project -->
                            <!-- <div class="mb-3">
                                <label class="form-label">Namba ya Kitambulisho</label>
                                <input type="text" name="guest_id_number" class="form-control" value="<?= htmlspecialchars($booking['id_number'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Anuani</label>
                                <textarea name="guest_address" class="form-control" rows="3"><?= htmlspecialchars($booking['address'] ?? '') ?></textarea>
                            </div> -->
                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($booking['notes'] ?? '') ?></textarea>
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

<?php include '../includes/footer.php'; ?>