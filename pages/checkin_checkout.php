<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';
include '../includes/functions.php';
$success = $error = '';
$today = date('Y-m-d');

// Handle Check-in / Check-out 
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action']; // 'checkin' or 'checkout'

    if ($booking_id <= 0) {
        $error = "Booking ID is invalid.";
    } else {
        // Fetch booking details safely
        $stmt = $conn->prepare("
            SELECT b.*, r.id AS room_id, r.room_number, g.name AS guest_name
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            LEFT JOIN guests g ON b.guest_id = g.id 
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            $error = "Booking not found.";
        } else {
            if ($action === 'checkin') {
            // Fresh check from database
            $fresh_stmt = $conn->prepare("SELECT booking_status FROM bookings WHERE id = ?");
            $fresh_stmt->bind_param("i", $booking_id);
            $fresh_stmt->execute();
            $fresh_result = $fresh_stmt->get_result();
            $fresh_booking = $fresh_result->fetch_assoc();
            $fresh_stmt->close();

            if ($fresh_booking['booking_status'] === 'checked_in') {
                $error = "Booking already checked-in."; // Prevent re-check
            } elseif ($fresh_booking['booking_status'] !== 'confirmed') {
                $error = "Booking not yet confirmed - cannot check-in.";
            } else {
                // Proceed with check-in
                $stmt1 = $conn->prepare("UPDATE bookings SET booking_status = 'checked_in' WHERE id = ?");
                $stmt1->bind_param("i", $booking_id);
                $stmt1->execute();
                $stmt1->close();

                $stmt2 = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
                $stmt2->bind_param("i", $booking['room_id']);
                $stmt2->execute();
                $stmt2->close();

                $success = "Check-in successfully! " . htmlspecialchars($booking['guest_name']) . " now on room  " . htmlspecialchars($booking['room_number']) . ".";
                
                // Send check-in email to guest
                $html = "
                <h2>Welcome to Las Hotel!</h2>
                <p>You are checked-in to room {$booking['room_id']}.</p>
                <p>Code: {$booking['id']}</p>
                <p>Reception is here to assist you. Feel free to ask if you need anything.</p>
                <p>Las Hotel ❤️</p>";

                // Send check-out email to guest
                send_guest_email($conn, $guest_email, "Welcome! Checked-in Las Hotel", $html);

                // Log actiivity (This is after guest checked in successfully)
                log_booking_activity($conn, 'Check-in', $booking['booking_code'], "Guest: {$booking['guest_name']} checked into room {$booking['room_number']}");

                // Redirect to avoid resubmission
                header("Location: checkin_checkout.php");
                exit();
                }
            }  elseif ($action === 'checkout') {
                if ($booking['booking_status'] !== 'checked_in') {
                    $error = "Booking has not been checked-in.";
                } else {
                    $balance = $booking['total_amount'] - $booking['paid_amount'];

                    // Record final payment if balance exists
                    if ($balance > 0) {
                        $method = $_POST['final_payment_method'] ?? 'cash';
                        $allowed_methods = ['cash', 'mpesa', 'card', 'bank_transfer'];
                        $method = in_array($method, $allowed_methods) ? $method : 'cash';
                        $notes = "Final payment recorded during check-out";

                        // Insert payment record
                        $stmt_pay = $conn->prepare("
                            INSERT INTO payments (booking_id, amount, payment_method, notes, recorded_by) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt_pay->bind_param("idssi", $booking_id, $balance, $method, $notes, $_SESSION['user_id']);
                        $stmt_pay->execute();
                        $stmt_pay->close();

                        // Update booking paid amount and status
                        $stmt_update = $conn->prepare("
                            UPDATE bookings 
                            SET paid_amount = total_amount, 
                                payment_status = 'paid', 
                                payment_method = ? 
                            WHERE id = ?
                        ");
                        $stmt_update->bind_param("si", $method, $booking_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    }

                    // Update booking status to checked_out
                    $stmt_book = $conn->prepare("UPDATE bookings SET booking_status = 'checked_out' WHERE id = ?");
                    $stmt_book->bind_param("i", $booking_id);
                    $stmt_book->execute();
                    $stmt_book->close();

                    // Update room status to dirty
                    $stmt_room = $conn->prepare("UPDATE rooms SET status = 'dirty' WHERE id = ?");
                    $stmt_room->bind_param("i", $booking['room_id']);
                    $stmt_room->execute();
                    $stmt_room->close();

                    // LOG ACTIVITY
                    log_booking_activity($conn, 'Check-out', $booking['booking_code'], "Guest: {$booking['guest_name']} checked out of room {$booking['room_number']}");

                    // Check out message
                    $success = "Check-out successfully! " . htmlspecialchars($booking['guest_name']) . " has left room " . htmlspecialchars($booking['room_number']) . ".";
                    

                    // Preparing check-out email to guest
                    $html = "
                    <h2>Thank You for Staying With Us!</h2>
                    <p>We are excited to have you stay with us, {$guest_name}.</p>
                    <p>Code: {$booking_code}</p>
                    <p>Welcome again! ❤️</p>
                    <p>Las Hotel Team</p>";

                    // Send check-out email to guest
                    send_guest_email($conn, $guest_email, "Thank You for Staying With Us - Las Hotel", $html);

                    // Redirect to avoid resubmission
                    header("Location: checkin_checkout.php");
                    exit();
                }
            } else {
                $error = "Action is invalid.";
            }
        }
    }
}

// Fetch today's bookings safely
$sqlTodayBookings = "
    SELECT b.*, g.name AS guest_name, r.room_number, rt.type_name
    FROM bookings b
    LEFT JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.type_id = rt.id
    WHERE (b.check_in = ? OR b.check_out = ?)
      AND b.booking_status NOT IN ('checked_out', 'cancelled')
    ORDER BY b.check_in
";

// Prepared statement for today's bookings 
$stmt_today = $conn->prepare($sqlTodayBookings);
$stmt_today->bind_param("ss", $today, $today);
$stmt_today->execute();
$today_bookings = $stmt_today->get_result();
$stmt_today->close();

$page_title = "Check-in / Check-out Today";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Check-in / Check-out</h1>
            <a href="bookings.php" class="btn btn-secondary ms-4">
                <i class="fas fa-list"></i> Bookings List
            </a>
        </div>
        <div class="d-flex my-2">
            <small class="fw-bold">Today (<?= date('d/m/Y') ?>)</small>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <script>setTimeout(() => location.reload(), 3000);</script>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($today_bookings->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($b = $today_bookings->fetch_assoc()): 
                $balance = $b['total_amount'] - $b['paid_amount'];
                $is_checkin = ($b['check_in'] == $today);
                $is_checkout = ($b['check_out'] == $today);
            ?>
            <div class="col-lg-12 col-md-6 pb-4">
                <div class="card shadow h-100 <?= $is_checkin ? 'border-left-primary' : 'border-left-danger' ?>">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold <?= $is_checkin ? 'text-primary' : 'text-danger' ?>">
                            <?= $is_checkin ? 'Expected Check-in' : 'Expected Check-out' ?>
                        </h6>
                        <span class="badge bg-<?= 
                            $b['booking_status'] == 'checked_in' ? 'primary' :
                            ($b['booking_status'] == 'confirmed' ? 'success' : 'warning')
                        ?>">
                            <?= ucfirst(str_replace('_', ' ', $b['booking_status'])) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <strong>Code:</strong> <?= htmlspecialchars($b['booking_code']) ?><br>
                                <strong>Visitor:</strong> <?= htmlspecialchars($b['guest_name']) ?><br>
                                <strong>Room:</strong> <?= htmlspecialchars($b['room_number']) ?> - <?= htmlspecialchars($b['type_name']) ?>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <strong>Total:</strong> TZS <?= number_format($b['total_amount'], 0) ?><br>
                                <strong>Paid:</strong> TZS <?= number_format($b['paid_amount'], 0) ?><br>
                                <strong>Balance:</strong> 
                                <span class="fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                                    TZS <?= number_format($balance, 0) ?>
                                </span>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <?php if ($is_checkin && $b['booking_status'] == 'confirmed'): ?>
                                <form method="POST" class="d-inline checkin-form">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="action" value="checkin">
                                    <button type="submit" class="btn btn-success btn-lg px-5 checkin-btn">
                                        <i class="fas fa-sign-in-alt me-2"></i> Check-in
                                    </button>
                                </form>
                            <?php elseif ($is_checkout && $b['booking_status'] == 'checked_in'): ?>
                                <?php if ($balance > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="action" value="checkout">
                                    <div class="mb-3">
                                        <label class="form-label small">Final Payment Method</label>
                                        <select name="final_payment_method" class="form-select">
                                            <option value="cash">Cash</option>
                                            <option value="mpesa">M-Pesa</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <!-- Check if balance is not zero -->
                                    <?php if ($balance > 0): ?>
                                        <div class="alert alert-warning">
                                            <strong>Note:</strong> There is an outstanding balance of 
                                            <span class="fw-bold text-danger">TZS <?= number_format($balance, 0) ?></span>.
                                            This will be recorded as final payment upon check-out.
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-info">
                                            <strong>Note:</strong> No outstanding balance. You can proceed to check-out.
                                        </div>
                                        <button type="submit" class="btn btn-danger btn-lg px-5">
                                            <i class="fas fa-sign-out-alt me-2"></i> Check-out & Record Payment
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <?php else: ?>
                                <form method="POST" class="d-inline checkout-form">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="action" value="checkout">
                                    <button type="submit" class="btn btn-danger btn-lg px-5 checkout-btn">
                                        <i class="fas fa-sign-out-alt me-2"></i> Check-out
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Print Invoice / Receipt -->
                                <div class="mt-3">
                                    <a href="invoice.php?id=<?= $b['id'] ?>" target="_blank" 
                                       class="btn btn-<?= $balance > 0 ? 'warning' : 'success' ?> btn-lg px-5">
                                        <i class="fas fa-<?= $balance > 0 ? 'file-invoice' : 'receipt' ?> me-2"></i>
                                        <?= $balance > 0 ? 'Print Invoice' : 'Print Receipt' ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No action available now</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-calendar-day fa-5x text-muted mb-4"></i>
            <h3 class="text-muted">Neither check-in nor check-out is available today!</h3>
            <p class="text-muted">Peace day for reception!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>