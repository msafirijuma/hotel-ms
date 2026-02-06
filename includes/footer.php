</div> <!-- End of d-flex -->

<!-- Bootstrap JS -->
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<!-- Calender JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<!-- Select2 JS -->
<script src="assets/select2/select2.min.js"></script>

<script>
    function confirmLogout() {
        if (confirm('Do you need to logout?')) {
            window.location.href = '/hotel-management/logout.php';
        }
    }
</script>

// Dark Mode Toggle
<script>
const darkToggle = document.getElementById('darkModeToggle');
const body = document.body;
const icon = darkToggle.querySelector('i');

// Load saved preference
if (localStorage.getItem('darkMode') === 'enabled') {
    body.classList.add('dark-mode');
    icon.classList.replace('fa-moon', 'fa-sun');
}

// Toggle on click dark mode button
darkToggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if (body.classList.contains('dark-mode')) {
        localStorage.setItem('darkMode', 'enabled');
        icon.classList.replace('fa-moon', 'fa-sun');
    } else {
        localStorage.setItem('darkMode', 'disabled');
        icon.classList.replace('fa-sun', 'fa-moon');
    }
});
</script>

<script>
// Fetch notifications
function updateNotifications() {
    fetch('ajax_notifications.php?action=count')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            if (data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        });

    fetch('ajax_notifications.php?action=list')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notificationList');
            const time = document.getElementById('notificationTime');
            time.textContent = 'Updated: ' + new Date().toLocaleTimeString();

            if (data.notifications.length === 0) {
                list.innerHTML = '<li class="text-center py-4 text-muted">Hakuna notification mpya</li>';
                return;
            }

            list.innerHTML = '';
            data.notifications.forEach(notif => {
                const item = document.createElement('li');
                item.innerHTML = `
                    <a class="dropdown-item ${notif.is_read ? '' : 'fw-bold bg-light'} py-3" href="#">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-${notif.type === 'booking' ? 'calendar-plus' : notif.type === 'task' ? 'broom' : 'money-bill'} text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong>${notif.title}</strong><br>
                                <small class="text-muted">${notif.message}</small><br>
                                <small class="text-muted">${new Date(notif.created_at).toLocaleString('sw')}</small>
                            </div>
                        </div>
                    </a>
                `;
                list.appendChild(item);
            });
        });
}

// Mark as read when opening dropdown
document.getElementById('notificationBell').addEventListener('click', () => {
    fetch('ajax_notifications.php?action=mark_read');
});

// Initial load
updateNotifications();

// Update every 15 seconds
setInterval(updateNotifications, 15000);

// Desktop Sidebar Collapse
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('collapsed');
});

// Check-in form handling, disable button on submit & show spinner
document.addEventListener('DOMContentLoaded', function() {
    // Find all check-in forms
    document.querySelectorAll('.checkin-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Disable button immediately
            const btn = form.querySelector('.checkin-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            btn.classList.add('btn-secondary'); // display grey color when processing
        });
    });
});

// Check-out form handling, disable button on submit & show spinner
document.addEventListener('DOMContentLoaded', function() {
    // Find all check-out forms
    document.querySelectorAll('.checkout-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Disable button immediately
            const btn = form.querySelector('.checkout-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            btn.classList.add('btn-secondary'); // display grey color when processing
        });
    });
});

// Booking form handling, disable button on submit & show spinner
document.addEventListener('DOMContentLoaded', function() {
    // Find all booking-form forms
    document.querySelectorAll('.booking-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Disable button immediately
            const btn = form.querySelector('.booking-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            btn.classList.add('btn-secondary'); // display grey color when processing
        });
    });
});

// Room form handling, disable button on submit & show spinner
document.addEventListener('DOMContentLoaded', function() {
    // Find all room-form forms
    document.querySelectorAll('.room-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Disable button immediately
            const btn = form.querySelector('.room-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            btn.classList.add('btn-secondary'); // display grey color when processing
        });
    });
});

</script>

<!-- Select2 JS -->
<script>
$(document).ready(function() {
    $('.searchable-dropdown').select2({
        placeholder: "Search here...",
        allowClear: true,
        theme: "bootstrap5",  // Bootstrap
        width: '100%',
        minimumInputLength: 1  // Search after 1+ character
    });
});
</script>

<!-- Select2 Template Result -->
<script>
$('.searchable-dropdown').select2({
    // ... other options ...
    templateResult: function(data) {
        if (!data.id) return data.text;
        let $option = $('<span>' + data.text + '</span>');
        if ($(data.element).data('current-shift')) {
            $option.css('font-weight', 'bold').css('color', '#28a745'); // Green for current shift
        }
        return $option;
    }
});
</script>
</body>
</html>