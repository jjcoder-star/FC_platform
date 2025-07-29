<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

// Check student login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

$fullname = $user['fullname'];
$year = $user['year'];
$profile = $user['profile_picture']; // Use profile picture from database without default fallback



// Get student's mentorship requests
$requests_query = "SELECT mr.*, m.program_name, u.fullname as mentor_name 
                  FROM mentorship_requests mr
                  JOIN mentorship m ON mr.program_id = m.mentorship_id
                  JOIN users u ON m.mentor_id = u.user_id
                  WHERE mr.student_id = ?
                  ORDER BY mr.request_date DESC";
$requests_stmt = $conn->prepare($requests_query);
$requests_stmt->bind_param("i", $user_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
?>

<?php include("includes/header.php"); ?>

<link rel="stylesheet" href="css/student_dashboard.css">

<div class="dashboard-container">
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="uploads/images/logo.png" alt="Logo" class="logo">
    </div>
    <div class="sidebar-profile">
      <img src="uploads/<?php echo htmlspecialchars($profile); ?>" alt="Profile" class="profile-pic">
      <h3><?php echo htmlspecialchars($fullname); ?></h3>
     <p>
  <strong>
    <?php
      echo $year;
      echo match($year) {
        '1' => 'st Year',
        '2' => 'nd Year',
        '3' => 'rd Year',
        default => 'student',
      };
    ?>
  </strong>
</p>

    </div>
    <nav class="sidebar-menu">
      <a href="student_dashboard.php" class="active"> <i class="fas fa-home"></i>Dashboard</a>
      <a href="community.php"> <i class="fas fa-users"></i>Community</a>
      <a href="resources.php"> <i class="fas fa-book"></i>Resources</a>
      <a href="student_profile.php"><i class="fas fa-user"></i>Profile</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>
  </aside>

  <main class="main-content">
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="message-container">
            <?php 
            $msg = $_GET['msg'];
            $message_class = 'success';
            $message_text = '';
            
            switch($msg) {
                case 'accepted':
                    $message_text = 'Your request was accepted! You can now chat with your mentor.';
                    break;
                case 'rejected':
                    $message_class = 'warning';
                    $message_text = 'Your request was rejected. You can try other programs.';
                    break;
                case 'error':
                    $message_class = 'error';
                    $message_text = 'An error occurred. Please try again.';
                    break;
            }
            ?>
            <div class="message <?= $message_class ?>">
                <i class="fas fa-<?= $message_class === 'success' ? 'check-circle' : ($message_class === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
                <?= $message_text ?>
                <button class="close-message" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <h1>👋 Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h1>
    <p>This is your personalized Future Coders dashboard.<br>Explore, connect, and grow together.</p>

    <div class="cards">
      <div class="card forum-card">
        <i class="fas fa-comments"></i>
        <h3>Forum Discussions</h3>
        <p>Join the latest coding Q&A and peer help.</p>
        <a href="forum.php" class="card-btn">Enter Forum</a>
      </div>
      <div class="card resources-card">
        <i class="fas fa-book"></i>
        <h3>Study Resources</h3>
        <p>Download and upload CS tutorials, notes, and more.</p>
        <a href="resources.php" class="card-btn">Access Resources</a>
      </div>
      <div class="card mentorship-card">
        <i class="fas fa-user-graduate"></i>
        <h3>Find a Mentor</h3>
        <p>Connect with senior students for support and guidance.</p>
        <a href="mentors_list.php" class="card-btn">Join Mentorship</a>
      </div>
    </div>

    <!-- Mentorship Requests Section -->
    <?php if ($requests_result->num_rows > 0): ?>
    <div class="mentorship-section">
      <h2><i class="fas fa-graduation-cap"></i> My Mentorship Requests</h2>
      <div class="requests-grid">
        <?php while ($request = $requests_result->fetch_assoc()): ?>
          <div class="request-card">
            <div class="request-header">
              <h3><?php echo htmlspecialchars($request['program_name']); ?></h3>
              <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                <?php echo ucfirst($request['status']); ?>
              </span>
            </div>
            <div class="request-details">
              <p><strong>Mentor:</strong> <?php echo htmlspecialchars($request['mentor_name']); ?></p>
              <p><strong>Requested:</strong> <?php echo date('M j, Y', strtotime($request['request_date'])); ?></p>
            </div>
            <div class="request-actions">
              <?php if ($request['status'] === 'accepted'): ?>
                <a href="chat.php?program=<?php echo urlencode($request['program_name']); ?>" class="btn-chat">
                  <i class="fas fa-comments"></i> Chat with Mentor
                </a>
              <?php elseif ($request['status'] === 'pending'): ?>
                <span class="pending-message">
                  <i class="fas fa-clock"></i> Waiting for mentor response
                </span>
              <?php else: ?>
                <span class="rejected-message">
                  <i class="fas fa-times"></i> Request was rejected
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php include("includes/footer.php"); ?>

<script>
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(function(message) {
            message.parentElement.remove();
        });
    }, 5000);
</script>
