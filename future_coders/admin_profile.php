<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Get admin statistics
$stats_sql = "SELECT 
              (SELECT COUNT(*) FROM users) as total_users,
              (SELECT COUNT(*) FROM posts) as total_posts,
              (SELECT COUNT(*) FROM resources) as total_resources,
              (SELECT COUNT(*) FROM mentorship) as total_mentorships,
              (SELECT COUNT(*) FROM admin_logs WHERE admin_id = $user_id) as actions_taken";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Profile - Future Coders</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/admin_profile.css">
</head>
<body>
<div class="profile-wrapper">
  <div class="sidebar">
    <div class="logo">FC<br><span>Future Coders</span></div>
    <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
         alt="Profile Picture" class="profile-pic">
    <div class="admin-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
    <div class="admin-role">System Administrator</div>
    <a href="admin_dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main-content">
    <div class="header">
      <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
      <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
      
      <a href="edit_admin_profile.php" class="edit-profile">
        <i class="fas fa-edit"></i> Edit Profile
      </a>
    </div>

    <div class="quick-stats">
      <div><strong><?php echo $stats['total_users'] ?? 0; ?></strong><br>Users</div>
      <div><strong><?php echo $stats['total_posts'] ?? 0; ?></strong><br>Posts</div>
      <div><strong><?php echo $stats['actions_taken'] ?? 0; ?></strong><br>Actions</div>
    </div>

    <div class="info-table">
      <p><strong><i class="fas fa-id-card"></i> Full Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
      <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
      <p><strong><i class="fas fa-key"></i> Password:</strong> ••••••••</p>
      <p><strong><i class="fas fa-calendar-alt"></i> Join Date:</strong> 
        <?php echo date("F d, Y", strtotime($user['date_joined'])); ?>
      </p>
      <p><strong><i class="fas fa-shield-alt"></i> Admin Since:</strong> 
        <?php echo date("F d, Y", strtotime($user['date_joined'])); ?>
      </p>
    </div>

    <div class="bottom-boxes">
      <div class="box">
        <div><i class="fas fa-users-cog"></i></div>
        <h4>Managed Users</h4>
        <p><?php echo $stats['total_users'] ?? 0; ?></p>
      </div>
      <div class="box">
        <div><i class="fas fa-book"></i></div>
        <h4>Resources</h4>
        <p><?php echo $stats['total_resources'] ?? 0; ?></p>
      </div>
      <div class="box">
        <div><i class="fas fa-handshake"></i></div>
        <h4>Mentorships</h4>
        <p><?php echo $stats['total_mentorships'] ?? 0; ?></p>
      </div>
    </div>
    
    <!-- Recent Admin Actions -->
    <div class="recent-actions">
      <h3><i class="fas fa-history"></i> Recent Actions</h3>
      <table>
        <thead>
          <tr>
            <th>Action</th>
            <th>Target</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $actions_sql = "SELECT action, target_id, date_time 
                          FROM admin_logs 
                          WHERE admin_id = $user_id
                          ORDER BY date_time DESC 
                          LIMIT 5";
          $actions = $conn->query($actions_sql);
          
          if ($actions && $actions->num_rows > 0) {
            while ($action = $actions->fetch_assoc()) {
              echo "<tr>
                      <td>{$action['action']}</td>
                      <td>{$action['target_id']}</td>
                      <td>".date("M d, Y H:i", strtotime($action['date_time']))."</td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='3'>No recent actions found</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>