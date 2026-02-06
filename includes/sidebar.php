<!-- Fetch hotel settings database -->
<?php
$hotel_name = "Hotel MS"; // Default name
$result = mysqli_query($conn, "SELECT hotel_name, logo_path FROM hotel_settings LIMIT 1");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $hotel_name = $row['hotel_name'];
    $hotel_logo = $row['logo_path'];
}
?>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="offcanvasSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white"><?= $hotel_name ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include 'sidebar_links.php'; ?>
    </div>
</div>

<!-- Desktop Sidebar -->
<nav class="sidebar d-none d-lg-block">
    <?php include 'sidebar_links.php'; ?>

    <!-- Desktop Toggle -->
    <div class="text-center mt-auto mb-4">
        <button id="sidebarToggle" class="btn btn-outline-light rounded-circle p-2">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
</nav>