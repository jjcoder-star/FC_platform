<?php
include_once("config.php");

if (!isset($_GET['user_id'])) {
    echo "No user selected.";
    exit();
}

$user_id = intval($_GET['user_id']);

// Fetch user details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
if (!$user_result || mysqli_num_rows($user_result) == 0) {
    echo "User not found.";
    exit();
}
$user = mysqli_fetch_assoc($user_result);

// Fetch bio & university
$extra_sql = "SELECT * FROM user_details WHERE user_id = $user_id";
$extra_result = mysqli_query($conn, $extra_sql);
$extra = mysqli_fetch_assoc($extra_result);

// Count posts, followers, following
$post_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM posts WHERE user_id = $user_id"))['total'];
$snippet_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM code_snippets WHERE user_id = $user_id"))['total'];
$follower_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM follows WHERE followed_id = $user_id"))['total'];
$following_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM follows WHERE follower_id = $user_id"))['total'];

// Resources (if mentor or admin)
$resource_count = 0;
$students_helped = 0;
if ($user['role'] === 'mentor' || $user['role'] === 'admin') {
    $resource_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM resources WHERE user_id = $user_id"))['total'];
    $students_helped = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM mentorship WHERE mentor_id = $user_id"))['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $user['fullname']; ?> - Public Profile</title>
  <link rel="stylesheet" href="css/public_profile.css">
</head>
<body>
<div class="profile-card">
  <div class="sidebar">
    <img src="uploads/<?php echo $user['profile_picture']; ?>" class="profile-pic" alt="Profile Picture">
    <h2><?php echo $user['fullname']; ?></h2>
    <p>@<?php echo $user['username']; ?></p>
    <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
    <p><strong>University:</strong> <?php echo $extra['university'] ?? 'N/A'; ?></p>
    <p><strong>Bio:</strong> <?php echo $extra['bio'] ?? 'No bio written.'; ?></p>
    
    <div class="follow-btn">
      <!-- This will be upgraded with JS/AJAX -->
      <form method="POST" action="follow_action.php">
        <input type="hidden" name="followed_id" value="<?php echo $user_id; ?>">
        <button type="submit">Follow</button>
      </form>
    </div>

    <a href="community.php" class="back-btn">← Back to Community</a>
  </div>

  <div class="main-stats">
    <div class="stat-box"><strong><?php echo $post_count; ?></strong><br>Posts</div>
    <div class="stat-box"><strong><?php echo $snippet_count; ?></strong><br>Code Snippets</div>
    <div class="stat-box"><strong><?php echo $follower_count; ?></strong><br>Followers</div>
    <div class="stat-box"><strong><?php echo $following_count; ?></strong><br>Following</div>

    <?php if ($user['role'] === 'mentor' || $user['role'] === 'admin'): ?>
      <div class="stat-box"><strong><?php echo $resource_count; ?></strong><br>Resources</div>
      <div class="stat-box"><strong><?php echo $students_helped; ?></strong><br>Students Helped</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
