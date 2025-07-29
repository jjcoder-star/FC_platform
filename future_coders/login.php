<?php
include_once("config.php");


if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $sql = "SELECT * FROM users WHERE username = '$username'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['fullname'] = $user['fullname'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['profile_picture'] = $user['profile_picture'];
      $_SESSION['year'] = $user['year'];

     
      if ($user['role'] === 'student') {
        header("Location: student_dashboard.php");
      } elseif ($user['role'] === 'mentor') {
        header("Location: mentor_dashboard.php");
      } elseif ($user['role'] === 'admin') {
        header("Location: admin_dashboard.php");
      } else {
        $error = "❌ Unknown role assigned.";
      }
      exit();
    } else {
      $error = "Wrong password!";
    }
  } else {
    $error = "User not found!";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Future Coders</title>
  <link rel="stylesheet" href="css/login-style.css">
</head>
<body>

<div class="login-container">
  <form method="POST" class="login-card">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button name="login">Login</button>
    <div class="extras">
      <a href="#">Forgot password?</a>
      <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
  </form>
</div>

</body>
</html>
