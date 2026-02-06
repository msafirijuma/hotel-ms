<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';
// require function.php
include '../includes/functions.php';

$success = $error = '';
$available_rooms = [];

// Fetch all room types for price reference
$room_types = mysqli_query($conn, "SELECT id, type_name, price_per_night FROM room_types");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $check_in = ($_POST['check_in']);
    $check_out = ($_POST['check_out']);
    $room_id = (int)testData($_POST['room_id']);
    $guest_name = testData($_POST['guest_name']);
    $guest_phone = testData($_POST['guest_phone']);
    $guest_email = testData($_POST['guest_email'] ?? '');
    $adults = (int)testData($_POST['adults']);
    $children = (int)testData($_POST['children']);
    $notes = testData($_POST['notes'] ?? '');

    // Validation
    if (empty($check_in) || empty($check_out) || $room_id == 0 || empty($guest_name)) {
        $error = "Please fill all required fields.";
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $error = "Check-in date must be before check-out date.";
    } else {
        // Check if room is available in that period
        $roomAvailability = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id FROM bookings 
            WHERE room_id = $room_id 
            AND booking_status NOT IN ('checked_out', 'cancelled')
            AND (
                (check_in <= '$check_out' AND check_out >= '$check_in')
            )
        "));

        if ($roomAvailability) {
            $error = "This room is already booked during these dates.";
        } else {
            // Create or find guest
            $guest_id = null;
            if (!empty($guest_phone)) {
                $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM guests WHERE phone = '$guest_phone'"));
                if ($g) {
                    $guest_id = $g['id'];
                }
            }

            if (!$guest_id) {
                mysqli_query($conn, "INSERT INTO guests (name, phone, email) VALUES ('$guest_name', '$guest_phone', '$guest_email')");
                $guest_id = mysqli_insert_id($conn);
            } else {
                mysqli_query($conn, "UPDATE guests SET name = '$guest_name', email = '$guest_email' WHERE id = $guest_id");
            }

            // Calculate total
            $nights = (strtotime($check_out) - strtotime($check_in)) / (60*60*24);
            $price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_per_night FROM room_types rt JOIN rooms r ON rt.id = r.type_id WHERE r.id = $room_id"))['price_per_night'];
            $total = $nights * $price;

            // Generate unique booking code
            $booking_code = 'BK' . strtoupper(substr(md5(time() . $room_id), 0, 6));

            // Save booking
            $stmt = mysqli_prepare($conn, "INSERT INTO bookings (booking_code, guest_id, room_id, check_in, check_out, adults, children, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "siissiiisi", $booking_code, $guest_id, $room_id, $check_in, $check_out, $adults, $children, $total, $notes, $_SESSION['user_id']);
            
            // LOG ACTIVITY
            log_booking_activity($conn, 'Created Booking', $booking_code, "Guest: {$guest_name}, Room: {$room_id}, Dates: {$check_in} to {$check_out}");
            
            if (mysqli_stmt_execute($stmt)) {
                // Update room status to occupied if immediate
                if (date('Y-m-d') >= $check_in) {
                    mysqli_query($conn, "UPDATE rooms SET status = 'occupied' WHERE id = $room_id");
                }

                // Success message 
                $success = "Booking successfully created! Code: <strong>$booking_code</strong>";

                // Send booking confirmation email to guest
                $html = "
                <h2>Your Booking is Confirmed!</h2>
                <p>Welcome Las Hotel, {$guest_name}!</p>
                <p><strong>Code:</strong> {$booking_code}<br>
                <strong>Room:</strong> {$room_id}<br>
                <strong>Dates:</strong> {$check_in} to {$check_out}<br>
                <strong>Price:</strong> TZS " . number_format($total, 0) . "</p>
                <p>We are excited to welcome you!</p>
                <p>Las Hotel Team</p>";

                // Send email to guest
                send_guest_email($conn, $guest_email, "Booking Confirmation - Las Hotel", $html);

                // Add notification to reception and admin users
                $reception_users = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('admin', 'reception')");
                while ($u = mysqli_fetch_assoc($reception_users)) {
                    if ($u['id'] != $_SESSION['user_id']) { // Don't notify to this user 
                        add_notification($conn, $u['id'], "New Booking", "Booking #$booking_code imeongezwa na {$_SESSION['name']}", 'booking', $booking_code);
                    }
                }
                $_POST = [];
            } else {
                $error = "Error saving booking. Please try again.";
            }
        }
    }
}

// Default dates
$default_in = date('Y-m-d', strtotime('+1 day'));
$default_out = date('Y-m-d', strtotime('+3 days'));

$page_title = "Add New Booking";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content pb-4">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Add New Booking</h1>
            <a href="bookings.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bookings
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
                <form method="POST" class="booking-form">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5 class="mb-3">Check-in and Check-out Dates</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-in</label>
                                    <input type="date" name="check_in" class="form-control" value="<?= $_POST['check_in'] ?? $default_in ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-out</label>
                                    <input type="date" name="check_out" class="form-control" value="<?= $_POST['check_out'] ?? $default_out ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-select" required onchange="calculateTotal()">
                                    <option value="">-- Select Room --</option>
                                    <?php 
                                    $all_rooms = mysqli_query($conn, "SELECT r.id, r.room_number, rt.type_name, rt.price_per_night FROM rooms r JOIN room_types rt ON r.type_id = rt.id WHERE r.status = 'available' ORDER BY r.room_number");
                                    while ($r = mysqli_fetch_assoc($all_rooms)): ?>
                                        <option value="<?= $r['id'] ?>" data-price="<?= $r['price_per_night'] ?>">
                                            <?= $r['room_number'] ?> - <?= $r['type_name'] ?> (TZS <?= number_format($r['price_per_night'], 0) ?>/night)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Adults</label>
                                    <input type="number" name="adults" class="form-control" min="1" value="<?= $_POST['adults'] ?? 1 ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Children</label>
                                    <input type="number" name="children" class="form-control" min="0" value="<?= $_POST['children'] ?? 0 ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Total (TZS)</label>
                                <input type="text" id="total_amount" class="form-control fw-bold text-primary fs-5" readonly value="0">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h5 class="mb-3">Guest Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="guest_name" class="form-control" value="<?= $_POST['guest_name'] ?? '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="guest_phone" class="form-control" value="<?= $_POST['guest_phone'] ?? '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email (optional)</label>
                                <input type="email" name="guest_email" class="form-control" value="<?= $_POST['guest_email'] ?? '' ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="4"><?= $_POST['notes'] ?? '' ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg px-5 booking-btn">
                            <i class="fas fa-save"></i> Add Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- END MAIN CONTENT WRAPPER -->

<script>
function calculateTotal() {
    const roomSelect = document.querySelector('[name="room_id"]');
    const checkIn = new Date(document.querySelector('[name="check_in"]').value);
    const checkOut = new Date(document.querySelector('[name="check_out"]').value);
    const totalField = document.getElementById('total_amount');

    if (roomSelect.value && checkIn && checkOut && checkOut > checkIn) {
        const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        const price = parseFloat(roomSelect.selectedOptions[0].dataset.price);
        const total = nights * price;
        totalField.value = total.toLocaleString() + ' TZS';
    } else {
        totalField.value = '0 TZS';
    }
}

// Run on load if dates are filled
calculateTotal();

// Run when dates change
document.querySelector('[name="check_in"]').addEventListener('change', calculateTotal);
document.querySelector('[name="check_out"]').addEventListener('change', calculateTotal);
document.querySelector('[name="room_id"]').addEventListener('change', calculateTotal);
</script>

<?php include '../includes/footer.php'; ?>