<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);
$success = $error = '';

if ($booking_id == 0) {
    header("Location: bookings.php");
    exit();
}

// Fetch booking details
$booking = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, g.name as guest_name, r.room_number 
    FROM bookings b 
    LEFT JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.id = $booking_id
"));

if (!$booking) {
    $error = "Booking is not found.";
}

$balance = $booking['total_amount'] - $booking['paid_amount'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    $method = $_POST['payment_method'];
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0) {
        $error = "Amount must be greater than 0.";
    } elseif ($amount > $balance) {
        $error = "Amount exceeds the remaining balance.";
    } else {
        // Record payment
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (booking_id, amount, payment_method, notes, recorded_by) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "idssi", $booking_id, $amount, $method, $notes, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);

        // Update booking paid amount
        $new_paid = $booking['paid_amount'] + $amount;
        $new_status = ($new_paid >= $booking['total_amount']) ? 'paid' : ($new_paid > 0 ? 'partial' : 'pending');

        mysqli_query($conn, "UPDATE bookings SET paid_amount = $new_paid, payment_method = '$method', payment_status = '$new_status' WHERE id = $booking_id");

        // LOG ACTIVITY
        log_booking_activity($conn, 'Recorded Payment', $booking['booking_code'], "Amount: TZS " . number_format($amount, 0) . ", Method: $method");

        $success = "Payment of TZS " . number_format($amount, 0) . " has been recorded!";
        $booking['paid_amount'] = $new_paid;
        $booking['payment_status'] = $new_status;
        $balance = $booking['total_amount'] - $new_paid;
    }
}

$page_title = "Record Payment";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Record Payment - <?= $booking['booking_code'] ?></h1>
            <a href="view_booking.php?id=<?= $booking_id ?>" class="btn btn-secondary">
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

        <div class="row">
            <div class="col-lg-5">
                <div class="card shadow mb-5">
                    <div class="card-header py-3 bg-info text-white">
                        <h6 class="m-0 font-weight-bold">Booking Summary</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Guest:</strong> <?= htmlspecialchars($booking['guest_name'] ?? 'Direct') ?></p>
                        <p><strong>Room:</strong> <?= htmlspecialchars($booking['room_number']) ?></p>
                        <p><strong>Total Amount:</strong> TZS <?= number_format($booking['total_amount'], 0) ?></p>
                        <p><strong>Paid Amount:</strong> TZS <?= number_format($booking['paid_amount'], 0) ?></p>
                        <p><strong>Balance:</strong> <span class="text-danger fw-bold">TZS <?= number_format($balance, 0) ?></span></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $booking['payment_status'] == 'paid' ? 'success' : ($booking['payment_status'] == 'partial' ? 'warning' : 'danger') ?>">
                                <?= ucfirst($booking['payment_status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Record New Payment</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Amount Paid</label>
                                <input type="number" name="amount" class="form-control" min="1000" step="1000" max="<?= $balance ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg px-5 shadow">
                                    <i class="fas fa-money-bill-wave me-2"></i> Record Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>