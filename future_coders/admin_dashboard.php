<?php
include_once("config.php");

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// Get admin info
$adminName = $_SESSION['fullname'];

// Fetch real statistics
$userCount = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$mentorCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='mentor'")->fetch_assoc()['total'];
$studentCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];
$postCount = $conn->query("SELECT COUNT(*) as total FROM posts")->fetch_assoc()['total'];

// Get mentorship statistics
$totalPrograms = $conn->query("SELECT COUNT(*) as total FROM mentorship")->fetch_assoc()['total'];
$activePrograms = $conn->query("SELECT COUNT(*) as total FROM mentorship WHERE status='active'")->fetch_assoc()['total'];
$totalRequests = $conn->query("SELECT COUNT(*) as total FROM mentorship_requests")->fetch_assoc()['total'];
$acceptedRequests = $conn->query("SELECT COUNT(*) as total FROM mentorship_requests WHERE status='accepted'")->fetch_assoc()['total'];

// Get top mentors by students mentored
$topMentors = $conn->query("
    SELECT u.fullname, COUNT(mr.student_id) as students_mentored
    FROM users u
    LEFT JOIN mentorship m ON u.user_id = m.mentor_id
    LEFT JOIN mentorship_requests mr ON m.mentorship_id = mr.program_id AND mr.status = 'accepted'
    WHERE u.role = 'mentor'
    GROUP BY u.user_id, u.fullname
    ORDER BY students_mentored DESC
    LIMIT 5
");

// Get recent activities
$recentPosts = $conn->query("
    SELECT p.*, u.fullname, u.role
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT 5
");

$recentRequests = $conn->query("
    SELECT mr.*, u.fullname as student_name, m.program_name, mentor.fullname as mentor_name
    FROM mentorship_requests mr
    JOIN users u ON mr.student_id = u.user_id
    JOIN mentorship m ON mr.program_id = m.mentorship_id
    JOIN users mentor ON m.mentor_id = mentor.user_id
    ORDER BY mr.request_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Future Coders</title>
  <link rel="stylesheet" href="css/admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <?php include_once("includes/header.php"); ?>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2><i class="fas fa-crown"></i> Future Coders</h2>
      <ul>
        <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="resources.php"><i class="fas fa-book"></i> Resources</a></li>
        <li><a href="community.php"><i class="fas fa-comments"></i> Community</a></li>
        <li><a href="admin_chat.php"><i class="fas fa-crown"></i> Official Chat</a></li>
        <li class="dropdown">
          <a href="#"><i class="fas fa-cog"></i> Settings</a>
          <ul class="dropdown-content">
            <li><a href="admin_profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="mentorship.php"><i class="fas fa-graduation-cap"></i> Mentorships</a></li>
          </ul>
        </li>
        <li><a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </aside>

    <!-- Center Dashboard -->
    <main class="main-content">
      <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($adminName); ?>! Here's what's happening in your platform.</p>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card users">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content">
            <h3>Total Users</h3>
            <p class="stat-number"><?= $userCount ?></p>
            <small><?= $studentCount ?> students, <?= $mentorCount ?> mentors</small>
          </div>
        </div>
        
        <div class="stat-card mentors">
          <div class="stat-icon">
            <i class="fas fa-user-graduate"></i>
          </div>
          <div class="stat-content">
            <h3>Mentorship Programs</h3>
            <p class="stat-number"><?= $totalPrograms ?></p>
            <small><?= $activePrograms ?> active programs</small>
          </div>
        </div>
        
        <div class="stat-card requests">
          <div class="stat-icon">
            <i class="fas fa-handshake"></i>
          </div>
          <div class="stat-content">
            <h3>Mentorship Requests</h3>
            <p class="stat-number"><?= $totalRequests ?></p>
            <small><?= $acceptedRequests ?> accepted</small>
          </div>
        </div>
        
        <div class="stat-card posts">
          <div class="stat-icon">
            <i class="fas fa-comments"></i>
          </div>
          <div class="stat-content">
            <h3>Community Posts</h3>
            <p class="stat-number"><?= $postCount ?></p>
            <small>Engagement growing</small>
          </div>
        </div>
      </div>

      <!-- Top Mentors Section -->
      <div class="dashboard-section">
        <h2><i class="fas fa-trophy"></i> Top Mentors</h2>
        <div class="mentors-grid">
          <?php while ($mentor = $topMentors->fetch_assoc()): ?>
            <div class="mentor-card">
              <div class="mentor-info">
                <h4><?php echo htmlspecialchars($mentor['fullname']); ?></h4>
                <p class="students-count">
                  <i class="fas fa-users"></i> 
                  <?php echo $mentor['students_mentored']; ?> students mentored
                </p>
              </div>
              <div class="mentor-badge">
                <i class="fas fa-star"></i>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="activities-section">
        <div class="activity-column">
          <h3><i class="fas fa-comments"></i> Recent Posts</h3>
          <div class="activity-list">
            <?php while ($post = $recentPosts->fetch_assoc()): ?>
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fas fa-comment"></i>
                </div>
                <div class="activity-content">
                  <p><strong><?php echo htmlspecialchars($post['fullname']); ?></strong> 
                     (<?php echo ucfirst($post['role']); ?>) posted</p>
                  <small><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>

        <div class="activity-column">
          <h3><i class="fas fa-handshake"></i> Recent Requests</h3>
          <div class="activity-list">
            <?php while ($request = $recentRequests->fetch_assoc()): ?>
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-content">
                  <p><strong><?php echo htmlspecialchars($request['student_name']); ?></strong> 
                     requested to join <strong><?php echo htmlspecialchars($request['program_name']); ?></strong></p>
                  <small>Status: <?php echo ucfirst($request['status']); ?> • 
                         <?php echo date('M j, Y', strtotime($request['request_date'])); ?></small>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </main>

    <!-- Right Panel -->
    <div class="right-panel">
      <div class="welcome-box">
        <h3>Welcome, <?= htmlspecialchars($adminName) ?> 👋</h3>
        <p>You are logged in as Admin.</p>
        <p>Manage users, mentors, posts, and more from here.</p>
        <a href="admin_chat.php" class="chat-btn">
          <i class="fas fa-crown"></i> Join Official Chat
        </a>
      </div>

      <div class="quick-actions">
        <h4>Quick Actions</h4>
        <a href="admin_users.php" class="action-btn">
          <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="reports.php" class="action-btn">
          <i class="fas fa-chart-bar"></i> View Reports
        </a>
        <a href="resources.php" class="action-btn">
          <i class="fas fa-book"></i> Manage Resources
        </a>
        <a href="community.php" class="action-btn">
          <i class="fas fa-comments"></i> Monitor Community
        </a>
      </div>
    </div>
  </div>

 <?php include("includes/footer.php"); ?>
</body>
</html>
