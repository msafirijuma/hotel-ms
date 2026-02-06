<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'config/db_connect.php';

// === Statistics ===
$today = date('Y-m-d');

$total_rooms = get_single_value("SELECT COUNT(*) FROM rooms");
$available_rooms = get_single_value("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
$occupied_rooms = get_single_value("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'");
$dirty_rooms = get_single_value("SELECT COUNT(*) FROM rooms WHERE status = 'dirty'");
$maintenance_rooms = get_single_value("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'");

// Get number of check-ins and check-outs today
$today = date('Y-m-d');
$today_checkins = get_single_value("SELECT COUNT(*) FROM bookings WHERE DATE(check_in) = '$today'");
$today_checkouts = get_single_value("SELECT COUNT(*) FROM bookings WHERE DATE(check_out) = '$today'");

// Get number of bookings today
$today_bookings = get_single_value("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = '$today'");

// Get total number of pending bookings today
$pending_bookings = get_single_value("SELECT COUNT(*) FROM bookings WHERE status = 'pending' AND DATE(created_at) = '$today'");

// Get number of new guests today
$today_guests = get_single_value("SELECT COUNT(*) FROM guests WHERE DATE(created_at) = '$today'");

// Get number of pending check-ins
$pending_checkins = get_single_value("SELECT COUNT(*) FROM bookings WHERE DATE(check_in) = '$today' AND status = 'pending'");

// Get number of pending check-outs
$pending_checkouts = get_single_value("SELECT COUNT(*) FROM bookings WHERE DATE(check_out) = '$today' AND status = 'pending'");

// Get number of active bookings
$active_bookings = get_single_value("SELECT COUNT(*) FROM bookings WHERE status = 'checked-in'");

$page_title = "Dashboard";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
            <div>
                <h1 class="h3 mb-1 text-gray-800">Dashboard</h1>
                <p class="mb-0 text-muted small">
                    Welcome, <strong class="me-2"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'User')?></strong> • <?= date('l, d F Y') ?>
                </p>
            </div>
            <div class="text-sm-end">
                <small class="text-muted">Last Login: <?= date('d/m/Y H:i', strtotime($_SESSION['last_login'] ?? 'now')) ?></small>
            </div>
        </div>

        <!-- First Row - Room Stats -->
        <div class="row g-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-left-info shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Rooms </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $total_rooms ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-hotel fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-left-success shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $available_rooms ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-door-open fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-left-primary shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Occupied</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $occupied_rooms ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-left-danger shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center justify-content-between">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Dirty</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $dirty_rooms ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-broom fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row - Today's total booking and pending bookings -->
        <div class="row mt-4 pb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 bg-gradient-dark text-dark">
                        <h6 class="m-0 font-weight-bold">Today's Booking & Guests</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-12 col-md-3">
                                <a href="pages/bookings.php" class="btn btn-primary btn-lg w-100 py-4">
                                    <i class="fas fa-calendar-day fa-2x d-block mb-2"></i>
                                    Today's Bookings (<?= $today_bookings ?>)
                                </a>
                            </div>
                            <div class="col-12 col-md-3">
                                <a href="pages/bookings.php?status=pending" class="btn btn-warning btn-lg w-100 py-4">
                                    <i class="fas fa-hourglass-half fa-2x d-block mb-2"></i>
                                    Pending Bookings (<?= $pending_bookings ?>)
                                </a>    
                            </div>
                            <div class="col-12 col-md-3">
                                <a href="pages/bookings.php?status=active" class="btn btn-info btn-lg w-100 py-4">
                                    <i class="fas fa-list fa-2x d-block mb-2"></i>
                                    Active Bookings (<?= $active_bookings ?>)
                                </a>
                            </div>
                            <div class="col-12 col-md-3">
                                <a href="pages/guests.php" class="btn btn-success btn-lg w-100 py-4">
                                    <i class="fas fa-user-friends fa-2x d-block mb-2"></i>
                                    New Guests Today (<?= $today_guests ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Third Row - Check in / Check-out  Stats & Room status -->
        <div class="row g-4 mt-4">
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 bg-gradient-primary text-dark">
                        <h6 class="m-0 font-weight-bold">Today Check-in / Check-out</h6>
                    </div>
                    <div class="card-body">
                        <!-- Show total number of check-in/check-out -->
                         <div class="container">
                            <div class="row">
                                <div class="col-md-6 text-center">
                                    <a href="pages/checkin_checkout.php" class="btn btn-success btn-lg w-100 py-4 mb-3">
                                        <i class="fas fa-sign-in-alt fa-2x d-block mb-2"></i>
                                        Check-in <?= $today_checkins > 0 ? "($today_checkins)" : '(0)'; ?>
                                    </a>
                                </div>
                                <div class="col-md-6 text-center">
                                    <a href="pages/checkin_checkout.php" class="btn btn-danger btn-lg w-100 py-4 mb-3">
                                        <i class="fas fa-sign-out-alt fa-2x d-block mb-2"></i>
                                        Check-out <?= $today_checkouts > 0 ? "($today_checkouts)" : '(0)'; ?>
                                    </a>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-60">
                    <div class="card-header py-3 bg-gradient-success text-dark">
                        <h6 class="m-0 font-weight-bold">Room Status</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="roomStatusChart" width="80%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>   

        <!-- Quick Actions -->
        <div class="row mt-4 pb-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 bg-gradient-dark text-dark">
                        <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-12 col-md-4">
                                <a href="pages/add_booking.php" class="btn btn-success btn-lg w-100 py-4">
                                    <i class="fas fa-plus fa-2x d-block mb-2"></i>
                                    Add Booking
                                </a>
                            </div>
                            <div class="col-12 col-md-4">
                                <a href="pages/checkin_checkout.php" class="btn btn-primary btn-lg w-100 py-4">
                                    <i class="fas fa-key fa-2x d-block mb-2"></i>
                                    Check-in / Out
                                </a>
                            </div>
                            <div class="col-12 col-md-4">
                                <a href="pages/bookings.php" class="btn btn-info btn-lg w-100 py-4">
                                    <i class="fas fa-list fa-2x d-block mb-2"></i>
                                    Bookings List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

// Room Status Doughnut Chart
const roomStatusCtx = document.getElementById('roomStatusChart').getContext('2d');
new Chart(roomStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Available', 'Occupied', 'Dirty', 'Maintenance'],
        datasets: [{
            data: [<?= $available_rooms ?>, <?= $occupied_rooms ?>, <?= $dirty_rooms ?>, <?= $maintenance_rooms ?>],
            backgroundColor: ['#1cc88a', '#007bff', '#dc3545', '#858796'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Ajax Auto-Update Stats Every 5 Seconds
// setInterval(() => {
//     fetch('/api/dashboard_stats.php')
//         .then(r => r.json())
//         .then(data => {
//             // Update cards na charts na data mpya
//             document.querySelector('.total-rooms').textContent = data.total_rooms;
//             // Update chart data
//         });
// }, 5000);

// Auto-Update Stats Every 300 Seconds
setInterval(() => {
    location.reload(); // Reload page with new data
}, 300000); // 300 seconds

</script>

<style>
.hover-shadow { transition: all 0.3s ease; }
.hover-shadow:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
</style>

<?php include 'includes/footer.php'; ?>