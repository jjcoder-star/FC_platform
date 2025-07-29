<?php
include_once("config.php");



if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Forum - Future Coders</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include('includes/header.php'); ?>

  <div class="main-content">
    <h2>Forum</h2>

    <!-- New Post Form -->
    <form method="POST" class="forum-post-form">
      <input type="text" name="title" placeholder="Post Title" required><br>
      <textarea name="content" placeholder="What’s your question or thought?" rows="5" required></textarea><br>
      <button name="submit_post">Post</button>
    </form>

    <?php
    if (isset($_POST['submit_post'])) {
      $title = $conn->real_escape_string($_POST['title']);
      $content = $conn->real_escape_string($_POST['content']);
      $user_id = $_SESSION['user_id'];

      $sql = "INSERT INTO posts (user_id, title, content) VALUES ('$user_id', '$title', '$content')";
      if ($conn->query($sql)) {
        echo "<p style='color:green;'>Post submitted!</p>";
      } else {
        echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
      }
    }
    ?>

    <!-- Display Posts -->
    <h3>Recent Discussions</h3>
    <?php
   $result = $conn->query("
  SELECT posts.*, users.fullname, users.profile_picture 
  FROM posts 
  JOIN users ON posts.user_id = users.user_id 
  ORDER BY created_at DESC
");

$result = $conn->query("SELECT posts.*, users.fullname, users.profile_picture FROM posts JOIN users ON posts.user_id = users.user_id ORDER BY created_at DESC");

while ($post = $result->fetch_assoc()) {
    
    $profilePic = !empty($post['profile_picture']) ? $post['profile_picture'] : 'default.png';

    echo "
    <div class='post-card'>
      <div class='post-header'>
        <img src='uploads/{$profilePic}' alt='Profile'>
        <div class='post-user-info'>
          <strong>{$post['fullname']}</strong><br>
          <small>{$post['created_at']}</small>
        </div>
      </div>
      <h4>{$post['title']}</h4>
      <p>{$post['content']}</p>
    </div>";
}

    ?>
  </div>

</body>
</html>
