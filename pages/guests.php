<!-- Page for rendering only today's guests -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';
include '../includes/functions.php';
$success = $error = '';
// Fetch today's guests
$today = date('Y-m-d');
$guests = mysqli_query($conn, "
    SELECT b.id, b.guest_id, b.room_id, r.room_number, b.check_in, b.check_out
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.check_in <= '$today' AND b.check_out >= '$today' AND b.status = 'checked_in'
    ORDER BY b.check_in ASC
");
$page_title = "Today's Guests";
include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="main-content pb-5">
    <div class="container-fluid pt-5 pt-lg-4">
        <h1 class="h3 mb-4 text-gray-800">Today's Guests (<?= $guests->num_rows ?>)</h1>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow border-0">
            <div class="card-body p-4">
                <?php if ($guests->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Guest Name</th>
                                    <th>Room Number</th>
                                    <th>Check-In Date</th>
                                    <th>Check-Out Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($guest = mysqli_fetch_assoc($guests)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($guest['guest_name']) ?></td>
                                        <td><?= htmlspecialchars($guest['room_number']) ?></td>
                                        <td><?= htmlspecialchars($guest['check_in']) ?></td>
                                        <td><?= htmlspecialchars($guest['check_out']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No guests are checked in today.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
