<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$booking_id = (int)($_GET['id'] ?? 0);
if ($booking_id == 0) {
    header("Location: bookings.php");
    exit();
}

$booking = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, g.name as guest_name, g.phone, g.email, g.id_number, g.address,
           r.room_number, rt.type_name, rt.price_per_night
    FROM bookings b
    LEFT JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.type_id = rt.id
    WHERE b.id = $booking_id
"));

if (!$booking) {
    $error = "Booking haipatikani.";
}

$page_title = "Angalia Booking #" . $booking['booking_code'];
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Booking Details - <?= $booking['booking_code'] ?></h1>
            <div>
                <a href="edit_booking.php?id=<?= $booking['id'] ?>" class="btn btn-warning me-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="invoice.php?id=<?= $booking['id'] ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-invoice"></i> Print Invoice
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Booking Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Booking Code</th>
                                <td><strong><?= $booking['booking_code'] ?></strong></td>
                            </tr>
                            <tr>
                                <th>Room</th>
                                <td><?= $booking['room_number'] ?> - <?= $booking['type_name'] ?></td>
                            </tr>
                            <tr>
                                <th>Check-in</th>
                                <td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td>
                            </tr>
                            <tr>
                                <th>Check-out</th>
                                <td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td>
                            </tr>
                            <tr>
                                <th>Number of Nights</th>
                                <td><?= (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400 ?></td>
                            </tr>
                            <tr>
                                <th>Adults / Children</th>
                                <td><?= $booking['adults'] ?> / <?= $booking['children'] ?></td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td><strong>TZS <?= number_format($booking['total_amount'], 0) ?></strong></td>
                            </tr>
                            <tr>
                                <th>Booking Status</th>
                                <td>
                                    <span class="badge bg-<?= 
                                        $booking['booking_status'] == 'confirmed' ? 'success' :
                                        ($booking['booking_status'] == 'checked_in' ? 'primary' :
                                        ($booking['booking_status'] == 'pending' ? 'warning' : 'secondary'))
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $booking['booking_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Notes</th>
                                <td><?= nl2br(htmlspecialchars($booking['notes'] ?? '-')) ?></td>
                            </tr>
                                <tr>
                                <th>Payment Status</th>
                                <td>
                                    <span class="badge bg-<?= 
                                        $booking['payment_status'] == 'paid' ? 'success' :
                                        ($booking['payment_status'] == 'partial' ? 'warning' : 'danger')
                                    ?>">
                                        <?= ucfirst($booking['payment_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Payment Details -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-info text-white">
                        <h6 class="m-0 font-weight-bold">Visitor's Details</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($booking['guest_name'] ?? 'Direct Booking') ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($booking['phone'] ?? '-') ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($booking['email'] ?? '-') ?></p>
                        <p><strong>ID Number:</strong> <?= htmlspecialchars($booking['id_number'] ?? '-') ?></p>
                        <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($booking['address'] ?? '-')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Payment History</h6>
                </div>
                <div class="card-body">
                    <?php
                    $payments = mysqli_query($conn, "SELECT * FROM payments WHERE booking_id = $booking_id ORDER BY payment_date DESC");
                    if (mysqli_num_rows($payments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($p = mysqli_fetch_assoc($payments)): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($p['payment_date'])) ?></td>
                                    <td>TZS <?= number_format($p['amount'], 0) ?></td>
                                    <td><?= ucfirst($p['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($p['notes'] ?? '-') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted">Hakuna malipo yaliyorekodiwa bado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>