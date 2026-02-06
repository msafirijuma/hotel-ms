<?php
require 'config/db_connect.php';

echo "<h3>✅ Database Connected Successfully kwa MySQLi!</h3>";

$result = mysqli_query($conn, "SELECT COUNT(*) as users FROM users");
$row = mysqli_fetch_assoc($result);
echo "<p>Idadi ya watumiaji kwenye database: " . $row['users'] . "</p>";

echo "<p>Kama unaona hii message, kila kitu kiko sawa! 🚀</p>";
?>