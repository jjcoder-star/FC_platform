<?php
include_once("config.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user's role
$user_role = $_SESSION['role']; // Using 'role' as per your database

// File upload directory
$upload_dir = "uploads/resources/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle resource submission (for mentors/admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $user_id = $_SESSION['user_id'];
    
    // File upload handling
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['resource_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_filename = uniqid() . '_' . preg_replace('/[^a-z0-9\.]/i', '_', $file_name);
        $target_file = $upload_dir . $new_filename;
        
        // Validate file
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt', 'jpg', 'png'];
        $max_size = 50 * 1024 * 1024; // 50MB
        
        if (!in_array($file_ext, $allowed_types)) {
            $error = "Only PDF, DOC, PPT, ZIP, TXT, JPG, PNG files are allowed.";
        } elseif ($_FILES['resource_file']['size'] > $max_size) {
            $error = "File is too large. Maximum size is 50MB.";
        } elseif (move_uploaded_file($_FILES['resource_file']['tmp_name'], $target_file)) {
            // Insert into database
            $sql = "INSERT INTO resources (user_id, title, file_path, category, description) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $user_id, $title, $target_file, $category, $description);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Resource uploaded successfully!";
                header("Location: resources.php");
                exit();
            } else {
                $error = "Database error: " . $conn->error;
                // Remove uploaded file if DB insert failed
                unlink($target_file);
            }
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Handle file download
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    $resource_id = (int)$_GET['download'];
    $sql = "SELECT file_path, title FROM resources WHERE resource_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $resource = $result->fetch_assoc();
        $file_path = $resource['file_path'];
        
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($resource['title']).'.'.pathinfo($file_path, PATHINFO_EXTENSION).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            flush();
            readfile($file_path);
            exit();
        }
    }
    $error = "File not found or you don't have permission to download it.";
}

$resources = [];

if ($user_role === 'mentor') {
    // Get all students of this mentor's programs
    $students_sql = "SELECT DISTINCT u.user_id
                     FROM users u
                     JOIN mentorship_requests mr ON u.user_id = mr.student_id
                     JOIN mentorship m ON mr.program_id = m.mentorship_id
                     WHERE m.mentor_id = ? AND mr.status = 'accepted'";
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("i", $_SESSION['user_id']);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    $student_ids = [];
    while ($student = $students_result->fetch_assoc()) {
        $student_ids[] = $student['user_id'];
    }
    
    if (count($student_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $types = str_repeat('i', count($student_ids));
        
        $sql = "SELECT r.*, u.fullname, u.profile_picture 
                FROM resources r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.user_id IN ($placeholders)
                ORDER BY r.upload_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$student_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $resources[] = $row;
        }
    }
} else {
    // For other roles, fetch all resources
    $sql = "SELECT r.*, u.fullname, u.profile_picture 
            FROM resources r
            JOIN users u ON r.user_id = u.user_id
            ORDER BY r.upload_date DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $resources[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Resources - Future Coders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/resources.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-book"></i>
                    <h1>Future Coders</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="<?= $user_role === 'student' ? 'student_dashboard.php' : ($user_role === 'mentor' ? 'mentor_dashboard.php' : 'admin_dashboard.php') ?>" 
                           class="<?= basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' || 
                                   basename($_SERVER['PHP_SELF']) === 'mentor_dashboard.php' || 
                                   basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">
                            Dashboard
                        </a></li>
                        <li><a href="resources.php" class="<?= basename($_SERVER['PHP_SELF']) === 'resources.php' ? 'active' : '' ?>">Resources</a></li>
                        <?php if ($user_role === 'student'): ?>
                            <li><a href="mentors_list.php" class="<?= basename($_SERVER['PHP_SELF']) === 'mentors_list.php' ? 'active' : '' ?>">Find Mentor</a></li>
                        <?php elseif ($user_role === 'mentor'): ?>
                            <li><a href="my_students.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_students.php' ? 'active' : '' ?>">My Students</a></li>
                        <?php elseif ($user_role === 'admin'): ?>
                            <li><a href="admin_users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_users.php' ? 'active' : '' ?>">Manage Users</a></li>
                        <?php endif; ?>
                        <li><a href="<?= $user_role === 'student' ? 'student_profile.php' : ($user_role === 'mentor' ? 'mentor_profile.php' : 'admin_profile.php') ?>" 
                           class="<?= basename($_SERVER['PHP_SELF']) === 'student_profile.php' || 
                                   basename($_SERVER['PHP_SELF']) === 'mentor_profile.php' || 
                                   basename($_SERVER['PHP_SELF']) === 'admin_profile.php' ? 'active' : '' ?>">
                            Profile
                        </a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Resource Upload Form (for mentors/admins) -->
        <?php if (in_array($user_role, ['mentor', 'admin'])): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h2>Upload New Resource</h2>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="resourceForm">
                        <div class="form-group">
                            <label for="title">Resource Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="Lecture Notes">Lecture Notes</option>
                                <option value="Assignment">Assignment</option>
                                <option value="Tutorial">Tutorial</option>
                                <option value="E-book">E-book</option>
                                <option value="Video">Video</option>
                                <option value="Code Sample">Code Sample</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="resource_file">Resource File *</label>
                            <input type="file" class="form-control" id="resource_file" name="resource_file" required>
                            <small class="form-text">Max size: 50MB (PDF, DOC, PPT, ZIP, TXT, JPG, PNG)</small>
                        </div>
                        
                        <button type="submit" name="add_resource" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Resource
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Resources List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-book-open"></i>
                <h2>Available Resources</h2>
            </div>
            <div class="card-body">
                <?php if(count($resources) > 0): ?>
                    <div class="resources-grid">
                        <?php foreach ($resources as $resource): ?>
                            <div class="resource-card">
                                <div class="resource-icon">
                                    <?php switch($resource['category']) {
                                        case 'Video': echo '<i class="fas fa-video"></i>'; break;
                                        case 'E-book': echo '<i class="fas fa-book"></i>'; break;
                                        case 'Code Sample': echo '<i class="fas fa-code"></i>'; break;
                                        default: echo '<i class="fas fa-file-alt"></i>';
                                    } ?>
                                </div>
                                <div class="resource-content">
                                    <h3><?= htmlspecialchars($resource['title']) ?></h3>
                                    <span class="resource-category"><?= htmlspecialchars($resource['category']) ?></span>
                                    <p class="resource-description"><?= htmlspecialchars($resource['description']) ?></p>
                                    
                                    <div class="resource-meta">
                                        <div class="resource-author">
                                            <img src="uploads/<?= htmlspecialchars($resource['profile_picture'] ?? 'default.png') ?>" 
                                                 alt="<?= htmlspecialchars($resource['fullname']) ?>">
                                            <span><?= htmlspecialchars($resource['fullname']) ?></span>
                                        </div>
                                        <div class="resource-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?= date('M j, Y', strtotime($resource['upload_date'])) ?>
                                        </div>
                                    </div>
                                    
                                    <a href="resources.php?download=<?= $resource['resource_id'] ?>" class="btn btn-download">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>No Resources Available</h3>
                        <p>Check back later or upload a resource if you're a mentor/admin</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // Removed the inline students list display as it is now handled in my_students.php
    // Mentor navigation already links to my_students.php
    ?>

    <?php include('includes/footer.php'); ?>
    
    <script>
        // Client-side file validation
        document.getElementById('resource_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const maxSize = 50 * 1024 * 1024; // 50MB
            const allowedTypes = ['application/pdf', 'application/msword', 
                                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                 'application/vnd.ms-powerpoint',
                                 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                 'application/zip', 'text/plain', 'image/jpeg', 'image/png'];
            
            if (file && file.size > maxSize) {
                alert('File is too large. Maximum size is 50MB.');
                e.target.value = '';
            }
            
            if (file && !allowedTypes.includes(file.type)) {
                alert('Only PDF, DOC, PPT, ZIP, TXT, JPG, PNG files are allowed.');
                e.target.value = '';
            }
        });
    </script>
</body>
</html>
