<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Statistics
$total_rooms = get_single_value("SELECT COUNT(*) FROM rooms");

// Occupancy Rate
$occupied_days = get_single_value("SELECT COALESCE(SUM(DATEDIFF(check_out, check_in)), 0) FROM bookings WHERE MONTH(check_in) = $month AND YEAR(check_in) = $year AND booking_status IN ('checked_in', 'checked_out')");
$possible_days = $total_rooms * cal_days_in_month(CAL_GREGORIAN, $month, $year);
$occupancy_rate = $possible_days > 0 ? round(($occupied_days / $possible_days) * 100, 1) : 0;

// Revenue This Month
$month_revenue = get_single_value("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE MONTH(p.payment_date) = $month AND YEAR(p.payment_date) = $year");

// Total Guests
$total_guests = get_single_value("SELECT COALESCE(SUM(adults + children), 0) FROM bookings WHERE MONTH(check_in) = $month AND YEAR(check_in) = $year");

// Average Stay
$avg_stay = get_single_value("SELECT COALESCE(AVG(DATEDIFF(check_out, check_in)), 0) FROM bookings WHERE MONTH(check_in) = $month AND YEAR(check_in) = $year");

// Daily Revenue (last 30 days)
$daily_revenue = [];
$dates = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('d/m', strtotime($date));
    $rev = get_single_value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(payment_date) = '$date'");
    $daily_revenue[] = $rev;
}

// Monthly Revenue
$monthly_revenue = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Agu', 'Sep', 'Oct', 'Nov', 'Dec'];
for ($m = 1; $m <= 12; $m++) {
    $rev = get_single_value("SELECT COALESCE(SUM(amount), 0) FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE MONTH(p.payment_date) = $m AND YEAR(p.payment_date) = $year");
    $monthly_revenue[] = $rev;
}

$page_title = "Ripoti";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Hotel's Reports (Live Update)</h1>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <select name="month" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= sprintf('%02d', $m) ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-select">
                        <?php for ($y = date('Y')-2; $y <= date('Y'); $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button class="btn btn-primary">Filter</button>
                </form>
            </div>
    </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-left-primary shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Occupancy Rate</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $occupancy_rate ?>%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-left-success shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Revenue <?= date('F Y', mktime(0,0,0,$month)) ?></div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">TZS <?= number_format($month_revenue, 0) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-left-info shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Guests</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= $total_guests ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-left-warning shadow h-100 hover-shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Average Staying</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= number_format($avg_stay, 1) ?> days</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 bg-gradient-info text-dark">
                        <h6 class="m-0 font-weight-bold">Daily Revenue (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyRevenueChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 bg-gradient-primary text-dark">
                        <h6 class="m-0 font-weight-bold">Revenue By Months (<?= $year ?>)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Revenue
const dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Revenue (TZS)',
            data: <?= json_encode($daily_revenue) ?>,
            borderColor: '#36b9cc',
            backgroundColor: 'rgba(54, 185, 204, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { tooltip: { callbacks: { label: ctx => 'Revenue: TZS ' + ctx.parsed.y.toLocaleString() } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'TZS ' + v.toLocaleString() } } }
    }
});

// Monthly Revenue
const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Mapato (TZS)',
            data: <?= json_encode($monthly_revenue) ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.7)',
            borderColor: '#4e73df',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { tooltip: { callbacks: { label: ctx => 'Mapato: TZS ' + ctx.parsed.y.toLocaleString() } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'TZS ' + v.toLocaleString() } } }
    }
});

// Auto Refresh Every 30 Seconds
setInterval(() => location.reload(), 30000);
</script>

<style>
    .hover-shadow { 
        transition: all 0.3s ease; 
    }
    .hover-shadow:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important; 
    }
</style>

<?php include '../includes/footer.php'; ?>