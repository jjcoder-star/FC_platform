<?php
include_once("config.php");

if (isset($_POST['register'])) {
  $fullname = $_POST['fullname'];
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role']; // Role from select dropdown
  $year = $_POST['year']; // Year from select dropdown
  $date_joined = date('Y-m-d H:i:s');

  // Handle profile picture upload
  $profilePicture = '';
  if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
      $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
      $target = 'uploads/' . $filename;
      if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
        $profilePicture = $filename;
      } else {
        echo "❌ Failed to upload profile picture."; exit();
      }
    } else {
      echo "❌ Invalid file type. Please upload JPG or PNG."; exit();
    }
  }

  // Insert into users table (assuming 'year' column exists in your table!)
  $sql = "INSERT INTO users (fullname, username, email, password, role, profile_picture, year, date_joined)
          VALUES ('$fullname', '$username', '$email', '$password', '$role', '$profilePicture', '$year', '$date_joined')";

  if ($conn->query($sql)) {
    header("Location: login.php");
    exit();
  } else {
    echo "❌ Error: " . $conn->error;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register - Future Coders</title>
  <link rel="stylesheet" href="css/auth_style.css">
</head>
<body>
  <div class="auth-container">
    <h2>Register</h2>
    <form action="register.php" method="POST" enctype="multipart/form-data">
      <input type="text" name="fullname" placeholder="Full Name" required>
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>

      <label for="role">Select Role</label>
      <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="student">Student</option>
        <option value="mentor">Mentor</option>
        <option value="admin">Admin</option>
      </select>

      <label for="year">Select Academic Year</label>
      <select name="year" required>
        <option value="">-- Select Year --</option>
        <option value="1">1st Year</option>
        <option value="2">2nd Year</option>
        <option value="3">3rd Year</option>
        <option value="4">4th Year</option>
      </select>

      <label for="profile_picture">Upload Profile Picture (JPG/PNG)</label>
      <input type="file" name="profile_picture" accept="image/*" required>

      <button type="submit" name="register">Register</button>
    </form>
    <div class="bottom-link">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>
</body>
</html>
