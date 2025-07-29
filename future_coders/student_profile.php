<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Profile - Future Coders</title>
  <link rel="stylesheet" href="css/student_profile.css">
</head>
<body>
<div class="profile-wrapper">
  <div class="sidebar">
    <div class="logo">FC<br><span>Future Coders</span></div>
    <img src="uploads/<?php echo $user['profile_picture']; ?>" alt="Profile Picture" class="profile-pic">
    <div class="student-name"><?php echo $user['fullname']; ?></div>
    <div class="student-year"><?php echo $user['year']; ?> Year CS Student</div>
    <a href="student_dashboard.php" class="btn">Back to Dashboard</a>
    <a href="logout.php" class="btn">logout</a>
  </div>

  <div class="main-content">
    <div class="header">
      <h1><?php echo $user['fullname']; ?></h1>
      <span class="student-role"> CS Student</span>
      
      <a href="edit_student_profile.php" class="edit-profile">Edit Profile</a>
    </div>

   

    <div class="info-table">
      <p><strong>Fullname:</strong> <?php echo $user['fullname']; ?></p>
      <p><strong>Username:</strong> @<?php echo $user['username']; ?></p>
      <p><strong>Password:</strong> ••••••••</p>
      <p><strong>Year:</strong> <?php echo $user['year']; ?></p>
      <p><strong>Join Date:</strong> <?php echo date("F d, Y", strtotime($user['date_joined'])); ?></p>
    </div>

    <div class="bottom-boxes">
      <div class="box">
        <div>💻</div>
        <h4>Projects</h4>
        <p>2</p>
      </div>
      <div class="box">
        <div>📝</div>
        <h4>Posts</h4>
        <p>6</p>
      </div>
      <div class="box">
        <div>🧠</div>
        <h4>Snippets</h4>
        <p>3</p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
