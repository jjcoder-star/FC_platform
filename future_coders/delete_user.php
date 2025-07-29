<?php
include_once("config.php");

if (isset($_GET['id'])) {
  $user_id = $_GET['id'];

  $sql = "DELETE FROM users WHERE user_id = $user_id";

  if ($conn->query($sql)) {
    header("Location: admin_users.php");
    exit();
  } else {
    echo "❌ Deletion failed: " . $conn->error;
  }
} else {
  echo "❌ No user ID provided!";
}
?>
