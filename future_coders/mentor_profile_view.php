<?php

include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is a student (only students should view mentor profiles)
if ($_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['mentor_id'])) {
    echo "Mentor ID is missing.";
    exit;
}

$mentor_id = intval($_GET['mentor_id']);
$current_user_id = $_SESSION['user_id'];

// Fetch mentor data with prepared statement - only fetch existing fields
$query = "SELECT u.fullname, u.profile_picture 
          FROM users u 
          WHERE u.user_id = ? AND u.role = 'mentor'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$mentor = $result->fetch_assoc();

if (!$mentor) {
    echo "Mentor not found.";
    exit;
}

$fullname = htmlspecialchars($mentor['fullname']);
$profile_picture = $mentor['profile_picture'];
// Set default values for university and bio since these columns don't exist
$university = "University of Technology";
$bio = "I'm a senior student and mentor. Welcome to my profile!";

// Fetch mentorship programs with prepared statement
$programs_query = "SELECT * FROM mentorship WHERE mentor_id = ? AND mentee_id IS NULL";
$programs_stmt = $conn->prepare($programs_query);
$programs_stmt->bind_param("i", $mentor_id);
$programs_stmt->execute();
$programs_result = $programs_stmt->get_result();

// Check request status function with prepared statement
function getRequestStatus($conn, $student_id, $program_id) {
    $stmt = $conn->prepare("SELECT status FROM mentorship_requests WHERE student_id = ? AND program_id = ?");
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['status'];
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fullname ?> - Mentor Profile</title>
    <link rel="stylesheet" href="css/mentor_profile_view.css">
    <link rel="stylesheet" href="css/header.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="message-container">
                <?php 
                $msg = $_GET['msg'];
                $message_class = 'success';
                $message_text = '';
                
                switch($msg) {
                    case 'request_sent':
                        $message_text = 'Request sent successfully! You will be notified when the mentor responds.';
                        break;
                    case 'already_requested':
                        $message_class = 'warning';
                        $message_text = 'You have already requested to join this program.';
                        break;
                    case 'program_full':
                        $message_class = 'error';
                        $message_text = 'This program is full. Please try other programs.';
                        break;
                    case 'error':
                        $message_class = 'error';
                        $message_text = 'An error occurred. Please try again.';
                        break;
                }
                ?>
                <div class="message <?= $message_class ?>">
                    <i class="fas fa-<?= $message_class === 'success' ? 'check-circle' : ($message_class === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
                    <?= $message_text ?>
                    <button class="close-message" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="back-section">
            <a href="mentors_list.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Mentors List
            </a>
        </div>

        <div class="profile-header">
            <div class="profile-image">
                <img src="uploads/<?= htmlspecialchars($profile_picture) ?>" class="profile-pic" alt="<?= $fullname ?>'s Profile Picture">
            </div>
            <div class="profile-info">
                <h1><?= $fullname ?></h1>
                <p class="university"><i class="fas fa-university"></i> <?= $university ?></p>
                <p class="bio"><i class="fas fa-user"></i> <?= $bio ?></p>
            </div>
        </div>

        <div class="programs-section">
            <h2><i class="fas fa-graduation-cap"></i> Mentorship Programs</h2>
            
            <?php if ($programs_result->num_rows > 0): ?>
                <div class="programs-grid">
                    <?php while ($row = $programs_result->fetch_assoc()): 
                        $program_id = $row['mentorship_id']; // Use 'mentorship_id' instead of 'id'
                        $program_name = htmlspecialchars($row['program_name']);
                        $description = htmlspecialchars($row['description']);
                        $status = getRequestStatus($conn, $current_user_id, $program_id);
            ?>
                    <div class="program-card">
                        <div class="program-header">
                            <h3><?= $program_name ?></h3>
                        </div>
                        <div class="program-content">
                            <p class="program-description"><?= $description ?></p>
                        </div>
                        <div class="program-actions">
                            <a href="view_that.php?id=<?= $program_id ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            
                    <?php if ($status === 'Accepted'): ?>
                                <a href="chat.php?program=<?= urlencode($program_name) ?>" class="btn btn-chat">
                                    <i class="fas fa-comments"></i> Enter Chat
                                </a>
                    <?php elseif ($status === 'Pending'): ?>
                                <span class="status-badge status-pending">
                                    <i class="fas fa-clock"></i> Request Pending
                                </span>
                            <?php elseif ($status === 'Rejected'): ?>
                                <span class="status-badge status-rejected">
                                    <i class="fas fa-times"></i> Request Rejected
                                </span>
                    <?php else: ?>
                                <form method="POST" action="send_request.php" style="display: inline;">
                                    <input type="hidden" name="mentor_id" value="<?= $mentor_id ?>">
                                    <input type="hidden" name="program_id" value="<?= $program_id ?>">
                                    <button type="submit" class="btn btn-request">
                                        <i class="fas fa-paper-plane"></i> Request to Join
                                    </button>
                                </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-programs">
                    <i class="fas fa-info-circle"></i>
                    <p>This mentor hasn't created any mentorship programs yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
            
            // Auto-hide messages after 5 seconds
            setTimeout(function() {
                const messages = document.querySelectorAll('.message');
                messages.forEach(function(message) {
                    message.parentElement.remove();
                });
            }, 5000);
        });
    </script>
</body>
</html>
