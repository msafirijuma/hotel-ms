<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$success = $error = '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;


// Handle Status Update (quick action)
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['booking_status'];
    $allowed = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        // Update booking status
        mysqli_query($conn, "UPDATE bookings SET booking_status = '$new_status' WHERE id = $booking_id");

        // Auto update room status
        if ($new_status == 'checked_in') {
            mysqli_query($conn, "UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.status = 'occupied' WHERE b.id = $booking_id");
        } elseif ($new_status == 'checked_out') {
            mysqli_query($conn, "UPDATE rooms r JOIN bookings b ON r.id = b.room_id SET r.status = 'dirty' WHERE b.id = $booking_id");
        }
        $success = "Booking status updated successfully!";
    }
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM bookings b LEFT JOIN guests g ON b.guest_id = g.id";
if ($search) {
    $count_sql .= " WHERE g.name LIKE ? OR b.booking_code LIKE ? OR g.phone LIKE ?";
}
$count_stmt = $conn->prepare($count_sql);
if ($search) {
    $like = "%$search%";
    $count_stmt->bind_param("sss", $like, $like, $like);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $per_page));

// Fetch bookings
$sql = "SELECT b.*, g.name as guest_name, g.phone, r.room_number, rt.type_name 
        FROM bookings b 
        LEFT JOIN guests g ON b.guest_id = g.id 
        LEFT JOIN rooms r ON b.room_id = r.id 
        LEFT JOIN room_types rt ON r.type_id = rt.id";
if ($search) {
    $sql .= " WHERE g.name LIKE ? OR b.booking_code LIKE ? OR g.phone LIKE ?";
}
$sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search) {
    $like = "%$search%";
    $stmt->bind_param("sssii", $like, $like, $like, $per_page, $offset);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$bookings = $stmt->get_result();

$page_title = "Bookings";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Bookings List (<?= $total ?>)</h1>
            <a href="add_booking.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Booking
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by email or booking code or phone..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <?php if ($search): ?>
                            <a href="bookings.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Booking Code</th>
                                <th>Visitor</th>
                                <th>Room</th>
                                <th>Booking Status</th>
                                <th>Change Status</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows > 0): ?>
                                <?php $no = $offset + 1; while ($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($b['booking_code']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($b['guest_name'] ?? 'Direct') ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($b['phone'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['room_number'] . ' - ' . $b['type_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $b['booking_status'] == 'confirmed' ? 'success' :
                                            ($b['booking_status'] == 'pending' ? 'warning' :
                                            ($b['booking_status'] == 'checked_in' ? 'primary' :
                                            ($b['booking_status'] == 'checked_out' ? 'secondary' : 'danger')))
                                        ?>">
                                            <?= ucfirst(str_replace('_', ' ', $b['booking_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <select name="booking_status" onchange="this.form.submit()" class="form-select form-select-sm">
                                                <option value="pending" <?= $b['booking_status']=='pending'?'selected':'' ?>>Pending</option>
                                                <option value="confirmed" <?= $b['booking_status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                                                <option value="checked_in" <?= $b['booking_status']=='checked_in'?'selected':'' ?>>Checked In</option>
                                                <option value="checked_out" <?= $b['booking_status']=='checked_out'?'selected':'' ?>>Checked Out</option>
                                                <option value="cancelled" <?= $b['booking_status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        TZS <?= number_format($b['total_amount'], 0) ?><br>
                                        <small>Paid: TZS <?= number_format($b['paid_amount'], 0) ?></small><br>
                                        <span class="badge bg-<?= $b['payment_status'] == 'paid' ? 'success' : ($b['payment_status'] == 'partial' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($b['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="record_payment.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="view_booking.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-info" title="View Booking"><i class="fas fa-eye"></i></a>
                                        <a href="edit_booking.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-warning" title="Edit Booking"><i class="fas fa-edit"></i></a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete Booking" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $b['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Delete Booking Modal -->
                                <div class="modal fade" id="deleteModal<?= $b['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $b['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteModalLabel<?= $b['id'] ?>">
                                                    <i class="fas fa-exclamation-triangle me-2"></i> Confirm Delete Booking
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center py-4">
                                                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                                <p class="mb-2">Do you want to <strong>delete this booking</strong>?</p>
                                                <h5 class="text-danger"><?= htmlspecialchars($b['booking_code']) ?></h5>
                                                <p class="mb-1"><?= htmlspecialchars($b['guest_name'] ?? 'Mgeni') ?></p>
                                                <p class="text-muted small">
                                                    Room <?= htmlspecialchars($b['room_number']) ?> • 
                                                    <?= date('d/m/Y', strtotime($b['check_in'])) ?> → <?= date('d/m/Y', strtotime($b['check_out'])) ?>
                                                </p>
                                                <p class="text-danger fw-bold mt-3">This action cannot be undone!</p>
                                            </div>
                                            <div class="modal-footer justify-content-center">
                                                <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                                <a href="delete_booking.php?id=<?= $b['id'] ?>" class="btn btn-danger btn-lg">
                                                    <i class="fas fa-trash"></i> Yes, Delete Booking
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                       No any booking yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END MAIN CONTENT WRAPPER -->

 <!-- Print Screen   -->
<style>
@page {
    font-family: 'Times New Roman', Times, serif !important;
    font-size: 12px;
    margin-bottom: 10px;
}

.head {
    font-size: 14px !important;
}

.btn-printable,
.btn-back,
.alert,
.alert-close {
    display: none !important;
}


th,td {
    color: #222529 !important;
    padding-block: 10px !important;
}

.total-expense-label, 
.total-revenue-label,
.caption {
    color: #111;
    margin-top: 5px;
    margin-bottom: 10px !important;
}

.not-print {
    display: none !important;
}


.brand-copyright {
    display: block;
    position: absolute;
    bottom: 10px;
    left: 30%;
    color: #222;
    font-size: 1.2rem !important;
}

</style>

<?php include '../includes/footer.php'; ?>