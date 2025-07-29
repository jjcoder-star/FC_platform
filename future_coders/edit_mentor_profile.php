<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Handle profile picture upload
    $profilePicture = $_POST['existing_pic'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $fileType = $_FILES['profile_picture']['type'];
        
        if (array_key_exists($fileType, $allowedTypes)) {
            $extension = $allowedTypes[$fileType];
            $filename = uniqid() . '.' . $extension;
            $target = 'uploads/' . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
                // Delete old picture if it exists and isn't the default
                if ($profilePicture && $profilePicture !== 'default.jpg') {
                    @unlink('uploads/' . $profilePicture);
                }
                $profilePicture = $filename;
            }
        }
    }

    // Build and execute update query
    $sql = "UPDATE users SET fullname = ?, email = ?, profile_picture = ?";
    $params = [$fullname, $email, $profilePicture];
    $types = "sss";
    
    if ($password) {
        $sql .= ", password = ?";
        $params[] = $password;
        $types .= "s";
    }
    
    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Update session data
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        $_SESSION['profile_picture'] = $profilePicture;
        
        header("Location: {$role}_profile.php");
        exit();
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit <?php echo ucfirst($role); ?> Profile</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #242c4f, #425280);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .edit-container {
      background: white;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
      width: 400px;
    }

    .edit-form h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #1c3b5a;
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      color: #333;
      font-weight: 600;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="file"] {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }

    .current-pic {
      margin-top: 10px;
      text-align: center;
    }

    .current-pic img {
      max-width: 100px;
      border-radius: 50%;
      margin-top: 5px;
    }

    .actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 25px;
    }

    .cancel {
      color: #888;
      text-decoration: none;
      font-weight: 500;
      padding: 10px 0;
    }

    .cancel:hover {
      color: #555;
    }

    button {
      background-color: #1c3b5a;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
    }

    button:hover {
      background-color: #00b2cc;
    }

    .error {
      color: #e74c3c;
      margin-bottom: 15px;
      text-align: center;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="edit-container">
    <form action="" method="POST" enctype="multipart/form-data" class="edit-form">
      <h2>Edit Your Profile</h2>
      <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
      </div>

      <div class="form-group">
        <label>New Password (optional)</label>
        <input type="password" name="password" placeholder="Leave blank to keep old password">
      </div>

      <div class="form-group">
        <label>Change Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">
        <input type="hidden" name="existing_pic" value="<?php echo htmlspecialchars($user['profile_picture']); ?>">
        <?php if ($user['profile_picture']): ?>
          <div class="current-pic">
            <p>Current Picture:</p>
            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                 alt="Profile Picture"
                 onerror="this.src='uploads/default.jpg'">
          </div>
        <?php endif; ?>
      </div>

      <div class="actions">
        <a href="<?php echo $role; ?>_profile.php" class="cancel">Cancel</a>
        <button type="submit" name="save">Save Changes</button>
      </div>
    </form>
  </div>
</body>
</html>