<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullname = $_POST['fullname'];
  $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

  // Profile Picture
  $profilePicture = $_POST['existing_pic'];
  if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
      $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
      $target = 'uploads/' . $filename;
      if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
        $profilePicture = $filename;
      }
    }
  }

  // Build update query
  $sql = "UPDATE users SET fullname = '$fullname', profile_picture = '$profilePicture'";
  if ($password) $sql .= ", password = '$password'";
  $sql .= " WHERE user_id = $user_id";

  if ($conn->query($sql)) {
    $_SESSION['fullname'] = $fullname;
    header("Location: student_profile.php");
    exit();
  } else {
    $error = "Failed to update profile.";
  }
}

// Fetch current user data
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="css/edit_profile_style.css">
</head>
<body>
  <div class="edit-container">
    <form action="" method="POST" enctype="multipart/form-data" class="edit-form">
      <h2>Edit Your Profile</h2>
      <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="fullname" value="<?php echo $user['fullname']; ?>" required>
      </div>

      <div class="form-group">
        <label>New Password (optional)</label>
        <input type="password" name="password" placeholder="Leave blank to keep old password">
      </div>

      <div class="form-group">
        <label>Change Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">
        <input type="hidden" name="existing_pic" value="<?php echo $user['profile_picture']; ?>">
      </div>

      <div class="actions">
        <a href="student_profile.php" class="cancel">Cancel</a>
        <button type="submit" name="save">Save</button>
      </div>
    </form>
  </div>
</body>
</html>
