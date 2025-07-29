<?php
include_once("config.php");

// Fetch users from database
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM users WHERE fullname LIKE '%$search%' OR username LIKE '%$search%' ORDER BY user_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin - Manage Users</title>
  <link rel="stylesheet" href="css/admin_users.css">
</head>
<body>

<div class="admin-users-container">
  <h2>Manage Users</h2>
  
  <form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Search by name or username" value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Profile</th>
        <th>Full Name</th>
        <th>Username</th>
        <th>Role</th>
        <th>Year</th>
        <th>Date Joined</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['user_id']; ?></td>
            <td><img src="uploads/<?php echo $row['profile_picture']; ?>" class="user-img" alt="profile"></td>
            <td><?php echo $row['fullname']; ?></td>
            <td>@<?php echo $row['username']; ?></td>
            <td><?php echo ucfirst($row['role']); ?></td>
            <td><?php echo isset($row['year']) ? $row['year'] : '-'; ?></td>
            <td><?php echo $row['date_joined']; ?></td>
            <td>
              <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="edit-btn">Edit</a>
              <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="delete-btn" onclick="return confirm('Delete this user?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8">No users found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
