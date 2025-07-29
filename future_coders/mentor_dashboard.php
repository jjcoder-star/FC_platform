<?php
include_once("config.php");
include_once("includes/header.php");



if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
  header("Location: login.php");
  exit();
}

$fullname = $_SESSION['fullname'];
$profilePicture = $_SESSION['profile_picture'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mentor Dashboard - Future Coders</title>
  <link rel="stylesheet" href="css/mentor_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="mentor-dashboard">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="profile">
      <img src="uploads/<?php echo $profilePicture; ?>" alt="Profile Picture">
      <h3><?php echo $fullname; ?></h3>
      <p>Mentor</p>
    </div>
    <nav>
      <a href="mentor_dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
      <a href="mentor_profile.php"><i class="fas fa-user"></i> Profile</a>
      <a href="resources.php"><i class="fas fa-book"></i>Resources</a>
      <a href="forum.php"><i class="fas fa-edit"></i> Forums</a>
      <a href="mentorship.php"><i class="fas fa-edit"></i> mentorships</a>
      <a href="community.php"><i class="fas fa-users"></i> Community</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="main">
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="message-container">
            <?php 
            $msg = $_GET['msg'];
            $message_class = 'success';
            $message_text = '';
            
            switch($msg) {
                case 'accepted':
                    $message_text = 'Request accepted successfully! Student has been notified.';
                    break;
                case 'rejected':
                    $message_text = 'Request rejected successfully! Student has been notified.';
                    break;
                case 'error':
                    $message_class = 'error';
                    $message_text = 'An error occurred. Please try again.';
                    break;
            }
            ?>
            <div class="message <?= $message_class ?>">
                <i class="fas fa-<?= $message_class === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message_text ?>
                <button class="close-message" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <section class="welcome">
      <h2>Welcome, Mentor <?php echo $fullname; ?> 👋</h2>
      <p>Your guidance helps students grow in code and confidence.</p>
    </section>

    <section class="cards">
      <div class="card">
        <i class="fas fa-user-graduate"></i>
        <h3>Students Mentored</h3>
        <p>23</p>
      </div>
      <div class="card">
        <i class="fas fa-comment-dots"></i>
        <h3>Comments Given</h3>
        <p>47</p>
      </div>
      <div class="card">
        <i class="fas fa-code"></i>
        <h3>Posts Shared</h3>
        <p>18</p>
      </div>
    </section>
  </main>
</div>

<?php include_once("includes/footer.php"); ?>

<script>
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(function(message) {
            message.parentElement.remove();
        });
    }, 5000);
</script>

</body>
</html>
