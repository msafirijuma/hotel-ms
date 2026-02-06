<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'reception'])) {
    header("Location: ../login.php");
    exit();
}
require '../config/db_connect.php';

// Fetch rooms as resources
$rooms_query = mysqli_query($conn, "SELECT id, room_number FROM rooms ORDER BY room_number");
$resources = [];
while ($room = mysqli_fetch_assoc($rooms_query)) {
    $resources[] = [
        'id' => $room['id'],
        'title' => $room['room_number']
    ];
}

// Fetch bookings as events
$bookings_query = mysqli_query($conn, "
    SELECT b.id, b.booking_code, b.check_in, b.check_out, b.booking_status, 
           r.id as room_id, g.name as guest_name
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    LEFT JOIN guests g ON b.guest_id = g.id
    WHERE b.booking_status NOT IN ('checked_out', 'cancelled')
");
$events = [];
while ($b = mysqli_fetch_assoc($bookings_query)) {
    $color = match ($b['booking_status']) {
        'checked_in' => '#0d6efd',
        'confirmed' => '#198754',
        default => '#ffc107'
    };
    $events[] = [
        'id' => $b['id'],
        'resourceId' => $b['room_id'],
        'title' => $b['booking_code'] . ' - ' . ($b['guest_name'] ?? 'Guest'),
        'start' => $b['check_in'],
        'end' => date('Y-m-d', strtotime($b['check_out'] . ' +1 day')),
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#fff'
    ];
}

$page_title = "Bookings Calender";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
    <div class="container-fluid pt-5 pt-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Kalenda ya Bookings</h1>
            <div>
                <a href="add_booking.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus"></i> Ongeza Booking
                </a>
                <a href="bookings.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Orodha
                </a>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Angalia Availability & Bookings</h6>
            </div>
            <div class="card-body p-0">
                <div id="calendar" style="height: 800px;"></div>
            </div>
        </div>
    </div>
</div>
<!-- END MAIN CONTENT WRAPPER -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: '100%',
        resources: <?= json_encode($resources) ?>,
        events: <?= json_encode($events) ?>,
        editable: false,
        selectable: true,
        eventClick: function(info) {
            alert(
                'Booking Code: ' + info.event.title + 
                '\nCheck-in: ' + info.event.start.toLocaleDateString() +
                '\nCheck-out: ' + new Date(info.event.end - 86400000).toLocaleDateString() +
                '\nStatus: ' + info.event.extendedProps?.status || 'N/A'
            );
        },
        select: function(info) {
            const roomId = info.resource?.id || '';
            const checkIn = info.startStr;
            const checkOut = info.endStr || new Date(info.start.getTime() + 86400000).toISOString().split('T')[0];
            if (roomId) {
                window.location.href = `add_booking.php?room_id=${roomId}&check_in=${checkIn}&check_out=${checkOut}`;
            } else {
                alert('Tafadhali chagua chumba kwa kubonyeza slot ndani ya chumba.');
            }
        }
    });

    calendar.render();
});
</script>

<?php include '../includes/footer.php'; ?>