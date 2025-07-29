<?php
include_once("config.php");
include_once("includes/header.php");

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$fullname = $_SESSION['fullname'];
$profile_pic = $_SESSION['profile_picture'];

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $post_type = $_POST['post_type'] ?? 'text';
    $project_name = trim($_POST['project_name'] ?? '');
    $project_tech = trim($_POST['project_tech'] ?? '');

    if (!empty($content)) {
        // Handle file upload for code/project posts
        $file_path = null;
        if ($post_type === 'code' && isset($_FILES['code_file']) && $_FILES['code_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/code_snippets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = uniqid() . '_' . basename($_FILES['code_file']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['code_file']['tmp_name'], $target_path)) {
                $file_path = $target_path;
            }
        }

        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, post_type, project_name, project_tech, file_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssss", $user_id, $title, $content, $post_type, $project_name, $project_tech, $file_path);
        $stmt->execute();
        
        header("Location: community.php?msg=post_created");
        exit();
    }
}

// Handle like/unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $post_id = intval($_POST['post_id']);
    
    // Check if already liked
    $check_like = $conn->prepare("SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?");
    $check_like->bind_param("ii", $post_id, $user_id);
    $check_like->execute();
    $existing_like = $check_like->get_result();
    
    if ($existing_like->num_rows > 0) {
        // Unlike
        $unlike = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
        $unlike->bind_param("ii", $post_id, $user_id);
        $unlike->execute();
    } else {
        // Like
        $like = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $like->bind_param("ii", $post_id, $user_id);
        $like->execute();
    }
    
    header("Location: community.php");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_post'])) {
    $post_id = intval($_POST['post_id']);
    $comment_text = trim($_POST['comment_text']);
    
    if (!empty($comment_text)) {
        $comment = $conn->prepare("INSERT INTO post_comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        $comment->bind_param("iis", $post_id, $user_id, $comment_text);
        $comment->execute();
    }
    
    header("Location: community.php");
    exit();
}

// Handle follow/unfollow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_user'])) {
    $follow_user_id = intval($_POST['follow_user_id']);
    
    if ($follow_user_id != $user_id) {
        // Check if already following
        $check_follow = $conn->prepare("SELECT * FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $check_follow->bind_param("ii", $user_id, $follow_user_id);
        $check_follow->execute();
        $existing_follow = $check_follow->get_result();
        
        if ($existing_follow->num_rows > 0) {
            // Unfollow
            $unfollow = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
            $unfollow->bind_param("ii", $user_id, $follow_user_id);
            $unfollow->execute();
        } else {
            // Follow
            $follow = $conn->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
            $follow->bind_param("ii", $user_id, $follow_user_id);
            $follow->execute();
        }
    }
    
    header("Location: community.php");
    exit();
}

// Fetch posts with likes, comments, and user info
$posts_sql = "SELECT p.*, u.fullname, u.profile_picture, u.role,
                     (SELECT COUNT(*) FROM post_likes WHERE post_id = p.post_id) as like_count,
                     (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) as comment_count,
                     (SELECT COUNT(*) FROM user_follows WHERE following_id = u.user_id) as followers_count,
                     (SELECT COUNT(*) FROM user_follows WHERE follower_id = u.user_id) as following_count,
                     EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.post_id AND user_id = ?) as is_liked,
                     EXISTS(SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = u.user_id) as is_following
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              ORDER BY p.created_at DESC";
$posts_stmt = $conn->prepare($posts_sql);
$posts_stmt->bind_param("ii", $user_id, $user_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

// Get user stats
$user_stats = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM user_follows WHERE following_id = ?) as followers,
    (SELECT COUNT(*) FROM user_follows WHERE follower_id = ?) as following,
    (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count");
$user_stats->bind_param("iii", $user_id, $user_id, $user_id);
$user_stats->execute();
$stats = $user_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community - Future Coders</title>
  <link rel="stylesheet" href="css/community.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="community-container">
  <!-- LEFT SIDEBAR -->
  <div class="sidebar">
    <div class="logo">FC</div>
    <ul>
      <li><a href="<?php 
        if ($role === 'admin') echo 'admin_dashboard.php';
        elseif ($role === 'mentor') echo 'mentor_dashboard.php';
        else echo 'student_dashboard.php';
      ?>"><i class="fa fa-arrow-left"></i> Back to Dashboard</a></li>
      <li><a href="community.php" class="active"><i class="fa fa-users"></i> Community</a></li>
      <li><a href="admin_chat.php"><i class="fa fa-comments"></i> Official Chat</a></li>
      <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </div>

  <!-- CENTER SECTION -->
  <div class="center">
    <!-- Success Message -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'post_created'): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i> Post created successfully!
      </div>
    <?php endif; ?>

    <div class="create-post">
      <form method="POST" enctype="multipart/form-data">
        <div class="post-type-selector">
          <label>
            <input type="radio" name="post_type" value="text" checked> Text Post
          </label>
          <label>
            <input type="radio" name="post_type" value="code"> Code Snippet
          </label>
          <label>
            <input type="radio" name="post_type" value="project"> Project Share
          </label>
        </div>
        
        <textarea name="content" placeholder="What's on your mind?" required></textarea>
        <input type="text" name="title" placeholder="Post title (optional)">
        
        <!-- Code snippet fields -->
        <div id="code-fields" style="display: none;">
          <input type="file" name="code_file" accept=".txt,.js,.py,.java,.cpp,.c,.html,.css,.php,.sql">
          <small>Upload code file or paste in content above</small>
        </div>
        
        <!-- Project fields -->
        <div id="project-fields" style="display: none;">
          <input type="text" name="project_name" placeholder="Project name">
          <input type="text" name="project_tech" placeholder="Technologies used (e.g., HTML, CSS, JS)">
        </div>
        
        <button type="submit" name="submit">Publish</button>
      </form>
    </div>

    <!-- Show Posts -->
    <?php while ($post = $posts_result->fetch_assoc()) : ?>
      <div class="post-box">
        <div class="post-header">
          <img src="uploads/<?php echo htmlspecialchars($post['profile_picture']); ?>" class="avatar">
          <div class="post-user-info">
            <h4><?php echo htmlspecialchars($post['fullname']); ?></h4>
            <small><?php echo date("F d, Y", strtotime($post['created_at'])); ?></small>
            <?php if ($post['role'] === 'mentor'): ?>
              <span class="mentor-badge">Mentor</span>
            <?php endif; ?>
          </div>
          
          <?php if ($post['user_id'] != $user_id): ?>
            <form method="POST" class="follow-form">
              <input type="hidden" name="follow_user_id" value="<?php echo $post['user_id']; ?>">
              <button type="submit" name="follow_user" class="follow-btn <?php echo $post['is_following'] ? 'following' : ''; ?>">
                <?php echo $post['is_following'] ? 'Following' : 'Follow'; ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
        
        <div class="post-body">
          <?php if (!empty($post['title'])): ?>
          <h3><?php echo htmlspecialchars($post['title']); ?></h3>
          <?php endif; ?>
          
          <?php if ($post['post_type'] === 'project' && !empty($post['project_name'])): ?>
            <div class="project-info">
              <h4><i class="fas fa-project-diagram"></i> <?php echo htmlspecialchars($post['project_name']); ?></h4>
              <?php if (!empty($post['project_tech'])): ?>
                <p class="project-tech"><strong>Tech:</strong> <?php echo htmlspecialchars($post['project_tech']); ?></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          
          <div class="post-content">
            <?php if ($post['post_type'] === 'code'): ?>
              <div class="code-block">
                <div class="code-header">
                  <span><i class="fas fa-code"></i> Code Snippet</span>
                  <?php if (!empty($post['file_path'])): ?>
                    <a href="<?php echo htmlspecialchars($post['file_path']); ?>" download class="download-code">
                      <i class="fas fa-download"></i> Download
                    </a>
                  <?php endif; ?>
                </div>
                <pre><code><?php echo htmlspecialchars($post['content']); ?></code></pre>
              </div>
            <?php else: ?>
              <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <?php endif; ?>
          </div>
        </div>
       
        <div class="post-footer">
          <form method="POST" class="like-form">
            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
            <button type="submit" name="like_post" class="like-btn <?php echo $post['is_liked'] ? 'liked' : ''; ?>">
              <i class="fa fa-heart"></i>
              <span class="like-count"><?php echo $post['like_count']; ?></span>
            </button>
          </form>
          
          <button class="comment-toggle" onclick="toggleComments(<?php echo $post['post_id']; ?>)">
            <i class="fa fa-comment"></i>
            <span class="comment-count"><?php echo $post['comment_count']; ?></span>
          </button>
          
          <div class="user-stats">
            <span class="followers-count"><?php echo $post['followers_count']; ?> followers</span>
          </div>
</div>

        <!-- Comments Section -->
<div class="comments-container" id="comments-<?php echo $post['post_id']; ?>" style="display: none;">
          <div class="comments-list" id="comment-list-<?php echo $post['post_id']; ?>">
            <?php
            $comments_sql = "SELECT pc.*, u.fullname, u.profile_picture 
                           FROM post_comments pc
                           JOIN users u ON pc.user_id = u.user_id
                           WHERE pc.post_id = ?
                           ORDER BY pc.created_at ASC";
            $comments_stmt = $conn->prepare($comments_sql);
            $comments_stmt->bind_param("i", $post['post_id']);
            $comments_stmt->execute();
            $comments = $comments_stmt->get_result();
            
            while ($comment = $comments->fetch_assoc()):
            ?>
              <div class="comment-item">
                <img src="uploads/<?php echo htmlspecialchars($comment['profile_picture']); ?>" class="comment-avatar">
                <div class="comment-content">
                  <div class="comment-header">
                    <span class="comment-author"><?php echo htmlspecialchars($comment['fullname']); ?></span>
                    <span class="comment-time"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                  </div>
                  <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

          <form class="comment-form" method="POST">
            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
    <input type="text" name="comment_text" placeholder="Write a comment..." required>
            <button type="submit" name="comment_post">Post</button>
  </form>
</div>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- RIGHT SIDEBAR -->
  <div class="rightbar">
    <div class="user-profile-card">
    <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" class="profile-avatar">
      <h4><?php echo htmlspecialchars($fullname); ?></h4>
      <div class="user-stats">
        <div class="stat">
          <span class="stat-number"><?php echo $stats['posts_count'] ?? 0; ?></span>
          <span class="stat-label">Posts</span>
        </div>
        <div class="stat">
          <span class="stat-number"><?php echo $stats['followers'] ?? 0; ?></span>
          <span class="stat-label">Followers</span>
        </div>
        <div class="stat">
          <span class="stat-number"><?php echo $stats['following'] ?? 0; ?></span>
          <span class="stat-label">Following</span>
        </div>
      </div>
    </div>
    
    <div class="section">
      <h4>🔥 Trending Tags</h4>
      <ul>
        <li>#Coding</li>
        <li>#AI</li>
        <li>#HTML</li>
        <li>#Motivation</li>
        <li>#Projects</li>
        <li>#JavaScript</li>
      </ul>
    </div>
    
    <div class="section">
      <h4>📢 Announcements</h4>
      <p>⚡ Future Coders Hackathon coming soon!</p>
      <p>🎓 New mentorship programs available</p>
    </div>
    
    <div class="section">
      <h4>👑 Top Contributors</h4>
      <p>@Zamzam</p>
      <p>@JJCoder</p>
      <p>@CodeMaster</p>
    </div>
  </div>
</div>

<script>
// Toggle post type fields
document.querySelectorAll('input[name="post_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const codeFields = document.getElementById('code-fields');
        const projectFields = document.getElementById('project-fields');
        
        codeFields.style.display = 'none';
        projectFields.style.display = 'none';
        
        if (this.value === 'code') {
            codeFields.style.display = 'block';
        } else if (this.value === 'project') {
            projectFields.style.display = 'block';
        }
    });
});

// Toggle comments
function toggleComments(postId) {
    const commentsContainer = document.getElementById('comments-' + postId);
    if (commentsContainer.style.display === 'none') {
        commentsContainer.style.display = 'block';
    } else {
        commentsContainer.style.display = 'none';
    }
}

// Auto-hide success message
setTimeout(function() {
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        successMessage.style.display = 'none';
    }
}, 3000);
</script>

</body>
</html>
