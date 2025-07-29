<?php
include 'config.php';


$user_id = $_SESSION['user_id'];
$result = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC");

while ($n = mysqli_fetch_assoc($result)) {
    echo "<p>{$n['message']}</p>";
}
?>
