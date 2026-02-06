<?php

use FontLib\Table\Type\head;

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } elseif ($_SESSION['role'] === 'reception') {
        header("Location: dashboard_reception.php");
    } elseif ($_SESSION['role'] === 'housekeeping') {
        header("Location: dashboard_housekeeping.php");
    } else{
        header("Location: login.php");
    }
    exit();
}

require 'config/db_connect.php';

$error = '';

function testData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = testData($_POST['email']);
    $password = testData($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please fill in email and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Update last_login
                mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: dashboard.php");
                } elseif ($user['role'] === 'reception') {
                    header("Location: dashboard_reception.php");
                } elseif ($user['role'] === 'housekeeping') {
                    header("Location: dashboard_housekeeping.php");
                } 
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Email is not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/hotel-management/assets/css/style.css">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <title>Login | Las Hotel MS</title>
</head>
<body class="container-body">
    <div class="card login-card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <i class="fas fa-hotel fa-4x text-primary mb-3"></i>
                <h3 class="text-dark">Las Hotel MS</h3>
                <p class="text-muted">Log in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login w-100 py-3 text-white fw-bold">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    Demo:<br>
                    Admin: admin@gmail.com / @admin123<br>
                    Reception: reception@gmail.com / reception123<br>
                    Housekeeping: hk@gmail.com / hk12345
                </small>
            </div>
        </div>
    </div>
</body>
</html>