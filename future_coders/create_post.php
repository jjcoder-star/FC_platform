<?php
include_once("config.php");


if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_post'])) {
  $title = $_POST['title'];
  $content = $_POST['content'];
  $user_id = $_SESSION['user_id'];

  $sql = "INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iss", $user_id, $title, $content);

  if ($stmt->execute()) {
    header("Location: community.php");
    exit();
  } else {
    echo "❌ Error: " . $conn->error;
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Create Post - Future Coders</title>
  <link rel="stylesheet" href="css/create_post.css">
</head>
<body>
  <?php include("<includes/header.php"); ?>

  <div class="post-container">
    <h2>Create a New Post 📝</h2>
    <form method="POST" class="post-form">
      <input type="text" name="title" placeholder="Post Title" required>
      <textarea name="content" rows="6" placeholder="Write your thoughts..." required></textarea>
      <button type="submit" name="submit_post">Publish</button>
    </form>
  </div>

  <?php include("includes/footer.php"); ?>
</body>
</html>
