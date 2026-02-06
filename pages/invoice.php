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

// Fetch booking details
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
    die("Booking is not found.");
}

$nights = (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400;
$room_charge = $booking['price_per_night'] * $nights;
$balance = $booking['total_amount'] - $booking['paid_amount'];

// Detect if it's Invoice or Receipt
$is_receipt = ($balance <= 0 && $booking['paid_amount'] > 0);
$document_type = $is_receipt ? 'Official Receipt' : 'Official Invoice';
$document_title = $is_receipt ? 'Receipt' : 'Invoice';
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $document_title ?> - <?= htmlspecialchars($booking['booking_code']) ?></title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; margin: 0; padding: 20px; }
            .card { border: none; box-shadow: none; }
        }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .invoice-container { max-width: 900px; margin: 30px auto; background: white; }
        .hotel-header { border-bottom: 4px double #4e73df; padding-bottom: 25px; margin-bottom: 40px; }
        .document-type { font-size: 2.5rem; font-weight: bold; color: <?= $is_receipt ? '#1cc88a' : '#36b9cc' ?>; }
        .table { font-size: 1.1rem; }
        .total-row { font-size: 1.3rem; background: #f8f9fa; }
        .balance-positive { color: #e74a3b; }
        .balance-zero { color: #1cc88a; }
        .thank-you { font-size: 1.8rem; color: #4e73df; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Button -->
        <div class="no-print text-center my-4">
            <button onclick="window.print()" class="btn btn-<?= $is_receipt ? 'success' : 'primary' ?> btn-lg px-5 shadow">
                <i class="fas fa-print me-2"></i> Print <?= $document_title ?>
            </button>
            <a href="view_booking.php?id=<?= $booking_id ?>" class="btn btn-secondary btn-lg ms-3">
                <i class="fas fa-arrow-left me-2"></i> Back to Booking
            </a>
        </div>

        <div class="card invoice-container shadow-lg rounded-4">
            <div class="card-body p-5">
                <!-- Hotel Header -->
                <div class="hotel-header text-center">
                    <h1 class="display-5 fw-bold text-primary mb-3">Las Hotel</h1>
                    <p class="lead mb-1">Hotel Management System</p>
                    <p class="mb-4">
                        Dar es Salaam, Tanzania<br>
                        Simu: +255 700 000 000 | Email: info@lashoteltz.co.tz<br>
                        Website: www.hotelhms.co.tz
                    </p>
                    <div class="document-type"><?= $document_type ?></div>
                </div>

                <!-- Guest & Booking Info -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-primary mb-3">Guest Information</h5>
                        <table class="table table-borderless">
                            <tr><td><strong>Name:</strong></td><td><?= htmlspecialchars($booking['guest_name'] ?? 'Direct Booking') ?></td></tr>
                            <tr><td><strong>Phone:</strong></td><td><?= htmlspecialchars($booking['phone'] ?? '-') ?></td></tr>
                            <tr><td><strong>Email:</strong></td><td><?= htmlspecialchars($booking['email'] ?? '-') ?></td></tr>
                            <tr><td><strong>ID Number:</strong></td><td><?= htmlspecialchars($booking['id_number'] ?? '-') ?></td></tr>
                            <tr><td><strong>Address:</strong></td><td><?= nl2br(htmlspecialchars($booking['address'] ?? '-')) ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="fw-bold text-primary mb-3">Booking Details</h5>
                        <table class="table table-borderless">
                            <tr><td><strong>Booking Code:</strong></td><td><span class="fw-bold fs-5 text-primary"><?= htmlspecialchars($booking['booking_code']) ?></span></td></tr>
                            <tr><td><strong>Date of <?= $document_title ?>:</strong></td><td><?= date('d/m/Y H:i') ?></td></tr>
                            <tr><td><strong>Check-in:</strong></td><td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td></tr>
                            <tr><td><strong>Check-out:</strong></td><td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td></tr>
                            <tr><td><strong>Nights:</strong></td><td><?= $nights ?></td></tr>
                            <tr><td><strong>Status:</strong></td><td>
                                <span class="badge bg-<?= $booking['payment_status'] == 'paid' ? 'success' : ($booking['payment_status'] == 'partial' ? 'warning' : 'danger') ?> fs-6">
                                    <?= ucfirst($booking['payment_status']) ?>
                                </span>
                            </td></tr>
                        </table>
                    </div>
                </div>

                <!-- Charges Table -->
                <h5 class="fw-bold text-primary mb-3">Room Charges</h5>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="50%">Description</th>
                            <th width="15%" class="text-center">Quantity</th>
                            <th width="20%" class="text-end">Price per Night</th>
                            <th width="15%" class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Chumba <?= htmlspecialchars($booking['room_number']) ?> - <?= htmlspecialchars($booking['type_name']) ?></td>
                            <td class="text-center"><?= $nights ?> usiku</td>
                            <td class="text-end">TZS <?= number_format($booking['price_per_night'], 0) ?></td>
                            <td class="text-end">TZS <?= number_format($room_charge, 0) ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" class="text-end fw-bold">Total Amount</td>
                            <td class="text-end fw-bold">TZS <?= number_format($booking['total_amount'], 0) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">Paid Amount</td>
                            <td class="text-end text-success fw-bold">TZS <?= number_format($booking['paid_amount'], 0) ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" class="text-end fw-bold <?= $balance > 0 ? 'balance-positive' : 'balance-zero' ?>">
                                Remaining Balance
                            </td>
                            <td class="text-end fw-bold <?= $balance > 0 ? 'balance-positive' : 'balance-zero' ?>">
                                TZS <?= number_format($balance, 0) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Final Message -->
                <div class="text-center mt-5 pt-5 border-top">
                    <h2 class="thank-you">Thank You Very Much!</h2>
                    <p class="lead text-muted">For choosing Las Hotel. We look forward to welcoming you again soon.</p>
                    <p class="text-muted">See you soon ❤️</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Optional: Auto print on load (remove comment if you want)
        window.onload = () => window.print();
    </script>
</body>
</html>