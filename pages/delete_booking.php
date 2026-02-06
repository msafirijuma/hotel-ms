<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$booking_id = (int)($_GET['id'] ?? 0);
$success = false;
$message = '';
$error = '';

if ($booking_id == 0) {
    $error = "No selected booking.";
} else {
    // Fetch booking details first (kwa message)
    $booking_query = mysqli_query($conn, "
        SELECT b.booking_code, g.name as guest_name, r.room_number 
        FROM bookings b 
        LEFT JOIN guests g ON b.guest_id = g.id 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.id = $booking_id
    ");

    if (mysqli_num_rows($booking_query) == 0) {
        $error = "Booking is not found or already deleted.";
    } else {
        $booking = mysqli_fetch_assoc($booking_query);

        // Get room_id for status update
        $room_result = mysqli_query($conn, "SELECT room_id FROM bookings WHERE id = $booking_id");
        $room_row = mysqli_fetch_assoc($room_result);
        $room_id = $room_row['room_id'] ?? 0;

        // Update room status to available
        if ($room_id) {
            mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE id = $room_id");
        }

        // Delete the booking
        if (mysqli_query($conn, "DELETE FROM bookings WHERE id = $booking_id")) {
            $success = true;
            $guest = $booking['guest_name'] ?? 'Visitor';
            $room = $booking['room_number'] ?? 'Unknown';
            $message = "Booking ya <strong>{$booking['booking_code']}</strong> ($guest - Room $room) Deleted Successfully!";
        } else {
            $error = "Error deleting booking.";
        }
    }
}

$page_title = "Delete Booking";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-10">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-body text-center py-5 px-4">
                        <?php if ($success): ?>
                            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                            <h3 class="text-success mb-3">Success!</h3>
                            <p class="lead text-muted mb-4"><?= $message ?></p>
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="spinner-border text-primary me-3" role="status">
                                    <span class="visually-hidden">Directing ...</span>
                                </div>
                                <p class="text-muted mb-0">Redirecting to bookings after 3 seconds ...</p>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-times-circle fa-5x text-danger mb-4"></i>
                            <h3 class="text-danger mb-3">Error!</h3>
                            <p class="lead text-muted mb-4"><?= $error ?></p>
                            <a href="bookings.php" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-arrow-left me-2"></i> Back to Bookings
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($success): ?>
<script>
    setTimeout(function() {
        window.location.href = "bookings.php";
    }, 3000);
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>