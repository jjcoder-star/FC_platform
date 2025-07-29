<?php
// You can include session_start() only if not already started
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>

<header class="main-header">
  <div class="logo-box">
    <img src="uploads/logo.png" alt="Future Coders Logo" class="site-logo">
    <h1 class="site-title">Future Coders</h1>
  </div>
  <link rel="stylesheet" href="css/header.css">
</header>

<?php if (isset($_SESSION['user_id'])): ?>
  <?php include 'includes/notifications.php'; ?>
<?php endif; ?>
