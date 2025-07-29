<?php
include_once("config.php");

if (!isset($_GET['id'])) {
  echo "❌ No user ID provided!";
  exit();
}

$user_id = $_GET['id'];

// Fetch existing user
$result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
if ($result->num_rows !== 1) {
  echo "❌ User not found!";
  exit();
}
$user = $result->fetch_assoc();

if (isset($_POST['update'])) {
  $fullname = $_POST['fullname'];
  $username = $_POST['username'];
  $email = $_POST['email'];
  $role = $_POST['role'];

  $sql = "UPDATE users SET fullname='$fullname', username='$username', email='$email', role='$role' WHERE user_id=$user_id";

  if ($conn->query($sql)) {
    header("Location: admin_users.php");
    exit();
  } else {
    echo "❌ Update failed: " . $conn->error;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit User</title>
  <link rel="stylesheet" href="css/edit_user.css">
</head>
<body>
  <div class="edit-container">
    <h2>Edit User Info</h2>
    <form method="POST">
      <label>Full Name:</label>
      <input type="text" name="fullname" value="<?= $user['fullname'] ?>" required>

      <label>Username:</label>
      <input type="text" name="username" value="<?= $user['username'] ?>" required>

      <label>Email:</label>
      <input type="email" name="email" value="<?= $user['email'] ?>" required>

      <label>Role:</label>
      <select name="role">
        <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
        <option value="mentor" <?= $user['role'] == 'mentor' ? 'selected' : '' ?>>Mentor</option>
        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>

      <button type="submit" name="update">Update</button>
    </form>
  </div>
</body>
</html>
