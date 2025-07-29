<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Get mentor stats
$resources_sql = "SELECT COUNT(*) AS count FROM resources WHERE user_id = $user_id";
$resources_result = $conn->query($resources_sql);
$resources_count = $resources_result->fetch_assoc()['count'];

// Get mentees count from mentorship table
$mentees_sql = "SELECT COUNT(DISTINCT mentee_id) AS count FROM mentorship WHERE mentor_id = $user_id AND status = 'active'";
$mentees_result = $conn->query($mentees_sql);
$mentees_count = $mentees_result->fetch_assoc()['count'];

// Get active programs count
$programs_sql = "SELECT COUNT(DISTINCT program_name) AS count FROM mentorship WHERE mentor_id = $user_id AND program_name IS NOT NULL";
$programs_result = $conn->query($programs_sql);
$programs_count = $programs_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mentor Profile - Future Coders</title>
  <link rel="stylesheet" href="css/mentor_profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="profile-wrapper">
  <!-- Left Sidebar -->
  <div class="sidebar">
    <div class="logo">FC<br><span>Future Coders</span></div>
    <img src="uploads/<?php echo $user['profile_picture']; ?>" alt="Profile Picture" class="profile-pic">
    <div class="mentor-name"><?php echo $user['fullname']; ?></div>
    <div class="mentor-title"><?php echo $user['specialization'] ?? 'Coding Mentor'; ?></div>
    
    <div class="mentor-links">
      <a href="mentor_dashboard.php" class="btn">
        <i class="fas fa-home"></i> Dashboard
      </a>
      <a href="resources.php" class="btn">
        <i class="fas fa-plus-circle"></i> New Resource
      </a>
      <a href="mentor_schedule.php" class="btn">
        <i class="fas fa-calendar-alt"></i> Schedule
      </a>
      <a href="logout.php" class="btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <h1><?php echo $user['fullname']; ?></h1>
      <span class="mentor-role">
        <i class="fas fa-chalkboard-teacher"></i> Mentor
      </span>
      
      <a href="edit_mentor_profile.php" class="edit-profile">
        <i class="fas fa-edit"></i> Edit Profile
      </a>
    </div>

    <!-- Profile Info -->
    <div class="info-table">
      <p><strong>Fullname:</strong> <?php echo $user['fullname']; ?></p>
      <p><strong>Username:</strong> @<?php echo $user['username']; ?></p>
      <p><strong>Specialization:</strong> <?php echo $user['specialization'] ?? 'General Programming'; ?></p>
      <p><strong>Experience:</strong> <?php echo $user['experience'] ?? '5+ years'; ?></p>
      <p><strong>Join Date:</strong> <?php echo date("F d, Y", strtotime($user['date_joined'])); ?></p>
    </div>

    <!-- Mentor Stats Boxes -->
    <div class="bottom-boxes">
      <div class="box">
        <div><i class="fas fa-book"></i></div>
        <h4>Resources</h4>
        <p><?php echo $resources_count; ?></p>
      </div>
      <div class="box">
        <div><i class="fas fa-users"></i></div>
        <h4>Mentees</h4>
        <p><?php echo $mentees_count; ?></p>
      </div>
      <div class="box">
        <div><i class="fas fa-project-diagram"></i></div>
        <h4>Programs</h4>
        <p><?php echo $programs_count; ?></p>
      </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="recent-activity">
      <h3><i class="fas fa-clock"></i> Recent Activity</h3>
      <div class="activity-item">
        <i class="fas fa-book activity-icon"></i>
        <div>
          <p>Added new resource "Advanced Python Tutorial"</p>
          <small>2 hours ago</small>
        </div>
      </div>
      <div class="activity-item">
        <i class="fas fa-comment activity-icon"></i>
        <div>
          <p>Replied to student question about JavaScript</p>
          <small>1 day ago</small>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>