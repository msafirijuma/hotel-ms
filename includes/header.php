<?php
// Start session once here only
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Custom CSS (Dashboard page) -->
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- Custom CSS (Pages) -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Select2 CSS -->
    <link href="assets/select2/select2.min.css" rel="stylesheet">

    <!-- Bootstrap CSS (local) -->
    <link href="/hotel-management/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- bootstrap cdn -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Calender CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

     <title>Hotel HMS | <?= $page_title ?? 'Dashboard' ?></title>

</head>
<body>

<!-- Notification Bell + Sound Toggle -->
<div class="d-flex align-items-center me-3">
    <!-- Sound Toggle Button -->
    <button class="btn btn-link text-white me-2" id="soundToggle" title="Toggle Notification Sound">
        <i class="fas fa-volume-up fa-lg" id="soundIcon"></i>
    </button>

    <!-- Bell Icon -->
    <div class="dropdown">
        <button class="btn btn-link text-white position-relative notification-bell" data-bs-toggle="dropdown" id="notificationBell">
            <i class="fas fa-bell fa-lg"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;">
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
</div>

<!-- Mobile Hamburger -->
<div class="d-lg-none p-3 bg-primary text-white fixed-top d-flex justify-content-between align-items-center" style="z-index: 1040;">
    <h5 class="mb-0">Hotel HMS</h5>
    <button class="btn btn-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
        <i class="fas fa-bars fa-lg"></i>
    </button>
</div>

<div class="d-flex">