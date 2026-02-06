<?php 
// Current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// User role for role-based links 
$role = $_SESSION['role'] ?? '';
?>

<!-- Name and logo from hotel_setting database -->
<?php  
$hotel_name = "Hotel MS"; // Default name
$result = mysqli_query($conn, "SELECT hotel_name, logo_path FROM hotel_settings LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $hotel_name = $row['hotel_name'];
    $hotel_logo = $row['logo_path'];
}
?>

<!-- Sidebar -->
<nav class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center mt-4 my-2 text-decoration-none" href="/hotel-management/dashboard.php">
        <i class="fas fa-hotel fa-2x text-white-50"></i>
        <span class="sidebar-brand-text mx-2 text-dark text-decoration-none"><?= $hotel_name ?></span>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Notification Bell + Sound Toggle  -->
    <li class="nav-item d-flex justify-content-between align-items-center px-3 py-2 mb-3 bg-gradient-light">
        <!-- Sound Toggle -->
        <button class="btn btn-link text-light p-0" id="soundToggle" title="Toggle Notification Sound">
            <i class="fas fa-volume-up fa-lg" id="soundIcon"></i>
        </button>

        <!-- Bell Icon -->
        <div class="dropdown">
            <button class="btn btn-link text-light position-relative p-0 notification-bell" data-bs-toggle="dropdown" id="notificationBell" title="Notifications">
                <i class="fas fa-bell fa-lg"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none; font-size: 0.7rem; min-width: 18px; height: 18px; line-height: 18px;">
                    0
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" id="notificationDropdown" style="width: 380px; max-height: 500px; overflow-y: auto;">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notifications</span>
                    <small class="text-muted" id="notificationTime">Loading...</small>
                </li>
                <div id="notificationList">
                    <li class="text-center py-4 text-muted"><em>Loading notifications...</em></li>
                </div>
            </ul>
        </div>
    </li>

    <!-- Dashboard - All Roles -->
    <?php if ($role === 'admin'): ?>
        <li class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a class="nav-link" href="/hotel-management/dashboard.php" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Dashboard Overview">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
    <?php elseif ($role === 'reception'): ?>
        <li class="nav-item <?= $current_page == 'dashboard_reception.php' ? 'active' : '' ?>">
            <a class="nav-link" href="/hotel-management/dashboard_reception.php" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Dashboard Overview">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
    <?php elseif ($role === 'housekeeping'): ?>
        <li class="nav-item <?= $current_page == 'dashboard_housekeeping.php' ? 'active' : '' ?>">
            <a class="nav-link" href="/hotel-management/dashboard_housekeeping.php" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Dashboard Overview">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Reception & Admin Only -->
    <?php if (in_array($role, ['admin', 'reception'])): ?>
        <li class="nav-item dropdown <?= in_array($current_page, ['rooms.php', 'room_types.php']) ? 'active' : '' ?>">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-bed"></i>
                <span>Rooms</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/hotel-management/pages/rooms.php">
                     <i class="fas fa-list me-2"></i> Rooms List</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a class="dropdown-item" href="/hotel-management/pages/room_types.php">
                     <i class="fas fa-building me-2"></i>Room Types</a></li>
                <?php endif; ?>
            </ul>
        </li>
        <li class="nav-item <?= $current_page == 'bookings.php' ? 'active' : '' ?>">
            <a class="nav-link" href="/hotel-management/pages/bookings.php">
                <i class="fas fa-calendar-check"></i>
                <span>Bookings</span>
            </a>
        </li>
        <!-- Check-in / Check-out  -->
        <li class="nav-item <?= $current_page == 'checkin_checkout.php' ? "active" : "" ?>">
            <a class="nav-link" href="/hotel-management/pages/checkin_checkout.php">
                <i class="fas fa-sign-in-alt"></i>
                <span>Check-in / Check-out</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Task Management -->
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <li class="nav-item <?= $current_page == 'housekeeping_assign.php' ? 'active' : '' ?>">
            <a class="nav-link" href="/hotel-management/pages/housekeeping_assign.php">
                <i class="fas fa-tasks"></i>
                <span>Assign Housekeepers</span>
            </a>
        </li>        
    <?php endif; ?>

    <!-- Staff Scheduling -->
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <li class="nav-item <?= $current_page == 'staff_scheduling.php' ? 'active' : '' ?>">
            <a class="nav-link" href="/hotel-management/pages/staff_scheduling.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Staff Scheduling</span>
            </a>
        </li>
    <?php endif; ?>


    <!-- Admin Only Section -->
     <!-- Put this details in a dropdown button -->
    <?php if ($role === 'admin'): ?>
        <hr class="sidebar-divider">
      <li class="nav-item dropdown <?= in_array($current_page, ['reports.php', 'users.php', 'shift_management.php', 'hotel_settings.php', 'audit_log.php']) ? 'active' : '' ?>">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-user-shield"></i>
            <span>Admin Tools</span>
        </a>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="/hotel-management/pages/reports.php">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/hotel-management/pages/users.php">
                    <i class="fas fa-users-cog me-2"></i> Workers
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/hotel-management/pages/shift_management.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Manage Shift</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/hotel-management/pages/hotel_settings.php">
                    <i class="fas fa-cog me-2"></i> Hotel Settings
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/hotel-management/pages/audit_log.php">
                    <i class="fas fa-history me-2"></i> Audit Log
                </a>
            </li>
        </ul>
    </li>   
    <?php endif; ?>

    <!-- Bell icon notification for assigned task -->
    <?php if ($role === 'housekeeping'): ?>
        <?php
        $assigned_tasks = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM housekeeping_tasks 
            WHERE assigned_to = {$_SESSION['user_id']} AND status = 'pending'
        "))['cnt'];
        ?>
        <li class="nav-item <?= $current_page == 'housekeeping.php' ? 'active' : '' ?>">
            <a class="nav-link position-relative" href="/hotel-management/pages/housekeeping.php">
                <i class="fas fa-bell"></i>
                <span>Housekeeping Tasks</span>
                <?php if ($assigned_tasks > 0): ?>
                    <span class="position-absolute top-10 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                        <?= $assigned_tasks ?>
                        <span class="visually-hidden">New tasks</span>
                    </span>
                <?php endif; ?>
            </a>
        </li>
    <?php endif; ?>

    <!-- Assign this page to only housekeeping and reception staff -->
    <?php if ($_SESSION['role'] === 'housekeeping'): ?>
        <li class="nav-item <?= $current_page === 'my_shift.php' ? 'active' : '' ?>">
            <a class="nav-link <?= $current_page === 'my_shift.php' ? 'active' : ''; ?>" href="/hotel-management/pages/my_shift.php">
                <i class="fas fa-history fa-fw"></i>
                <span>My Shift</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Housekeeping Sidebar Links -->
    <?php if ($_SESSION['role'] === 'housekeeping'): ?>
        <li class="nav-item <?= $current_page === 'task_history.php' ? 'active' : '' ?>">
            <a class="nav-link <?php $current_page === 'task_history.php' ? 'active' : ''; ?>" href="/hotel-management/pages/task_history.php">
                <i class="fas fa-history fa-fw"></i>
                <span>Task History</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- All (user profile) -->
    <li class="nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
        <a class="nav-link" href="/hotel-management/pages/profile.php">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">
    <!-- Dark Mode Toggle Button -->
    <!-- <div class="nav-link btn btn-link text-start text-white p-0 mb-3" id="darkModeToggle" title="Toggle Dark Mode">
        <button class="btn btn-link text-white p-0">
            <i class="fas fa-moon"></i>
        </button>
        <span class="text-light">Dark Mode</span>
    </div> -->

    <!-- Logout Button -->
    <button type="button" class="nav-link btn btn-link text-start text-white p-0" onclick="confirmLogout()">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout (<?= htmlspecialchars($_SESSION['name']) ?>)</span>
    </button>

    <!-- Sidebar Toggle Button -->
    <!-- <div class="text-center d-none mt-4">
        <button class="rounded-circle border-0 p-2 bg-white-10" id="sidebarToggle"></button>
    </div> -->

</nav>
<!-- End Sidebar -->