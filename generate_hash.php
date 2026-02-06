<?php
// generate_hash.php - Tengeneza password hash mpya

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plain_password = $_POST['password'];
    $hash = password_hash($plain_password, PASSWORD_DEFAULT);
    echo "<h3>Password wazi: " . htmlspecialchars($plain_password) . "</h3>";
    echo "<h4>Hash mpya (copy hii kwenye database):</h4>";
    echo "<textarea style='width:100%; height:100px;'>" . $hash . "</textarea>";
    echo "<hr>";
    echo "<p><strong>Test login:</strong> Tumia password wazi ili uingie.</p>";
}
?>

<!DOCTYPE html>
<html>
<head><title>Generate Password Hash</title></head>
<body class="p-5">
    <h1>Tengeneza Password Hash Mpya</h1>
    <form method="POST">
        <label>Ingiza password mpya unayotaka:</label><br>
        <input type="text" name="password" class="form-control mt-2" style="width:400px;" required placeholder="mfano: newpass2025">
        <button type="submit" class="btn btn-primary mt-3">Tengeneza Hash</button>
    </form>
</body>
</html>