<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Custome CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Hotel Welcome Titlt -->
    <title>Welcome - Las Hotel MS</title>
</head>
<body class="container-body">
    <div class="card welcome-card text-center">
        <div class="card-body p-5">
            <i class="fas fa-hotel fa-4x text-primary mb-4"></i>
            <h1 class="text-dark mb-3">Welcome Las Hotel</h1>
            <p class="lead text-muted mb-4">Hotel Management System</p>
            <a href="login.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-sign-in-alt mr-1"></i> Enter now
            </a> 
            <div class="mt-4">
                <small class="text-muted">© 2025 Las Hotel • Tanzania</small>
            </div>
        </div>
    </div>

    <!-- Auto redirect after 5 seconds (optional) -->
    <script>
        setTimeout(() => window.location.href = "login.php", 5000);
    </script>
</body>
</html>