<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if program ID exists
if (!isset($_GET['id'])) {
    header("Location: " . ($_SESSION['role'] === 'mentor' ? "mentorship.php" : "mentors_list.php"));
    exit();
}

$program_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Get program details
$program_sql = "SELECT m.*, u.fullname AS mentor_name, u.profile_picture AS mentor_pic
               FROM mentorship m
               JOIN users u ON m.mentor_id = u.user_id
               WHERE m.mentorship_id = ?";
$program_stmt = $conn->prepare($program_sql);
$program_stmt->bind_param("i", $program_id);
$program_stmt->execute();
$program = $program_stmt->get_result()->fetch_assoc();

if (!$program) {
    $_SESSION['error'] = "Program not found";
    header("Location: " . ($current_user_role === 'mentor' ? "mentorship.php" : "mentors_list.php"));
    exit();
}

// Count enrolled students
$students_sql = "SELECT COUNT(*) AS student_count 
                FROM mentorship 
                WHERE mentor_id = ? AND program_name = ? AND status = 'active'";
$students_stmt = $conn->prepare($students_sql);
$students_stmt->bind_param("is", $program['mentor_id'], $program['program_name']);
$students_stmt->execute();
$students = $students_stmt->get_result()->fetch_assoc();

// Check if current user is enrolled (for students)
$is_enrolled = false;
if ($current_user_role === 'student') {
    $enrollment_sql = "SELECT COUNT(*) AS enrolled 
                      FROM mentorship 
                      WHERE mentor_id = ? AND mentee_id = ? AND program_name = ? AND status = 'active'";
    $enrollment_stmt = $conn->prepare($enrollment_sql);
    $enrollment_stmt->bind_param("iis", $program['mentor_id'], $current_user_id, $program['program_name']);
    $enrollment_stmt->execute();
    $enrollment = $enrollment_stmt->get_result()->fetch_assoc();
    $is_enrolled = $enrollment['enrolled'] > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($program['program_name']); ?> - View Program</title>
    <link rel="stylesheet" href="css/view_that.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <div class="view-container">
        <div class="program-card">
            <div class="program-header">
                <h1 class="program-title">
                    <i class="fas fa-graduation-cap"></i>
                    <?php echo htmlspecialchars($program['program_name']); ?>
                </h1>
                <p class="program-topic"><?php echo htmlspecialchars($program['topic'] ?? 'General Mentorship'); ?></p>
                <div class="mentor-info">
                    <img src="uploads/<?php echo htmlspecialchars($program['mentor_pic'] ?? 'default.png'); ?>" class="mentor-avatar" alt="Mentor">
                    <span>Mentored by <?php echo htmlspecialchars($program['mentor_name']); ?></span>
                </div>
            </div>

            <div class="program-description">
                <h2 class="description-title">
                    <i class="fas fa-info-circle"></i> Description
                </h2>
                <p class="description-text">
                    <?php echo htmlspecialchars($program['description'] ?? 'No description provided.'); ?>
                </p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="stat-number"><?php echo $students['student_count']; ?></h3>
                    <p class="stat-label">Students Enrolled</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h3 class="stat-number"><?php echo date('M j, Y', strtotime($program['start_date'])); ?></h3>
                    <p class="stat-label">Start Date</p>
                </div>
                
                <?php if ($program['end_date']): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="stat-number"><?php echo date('M j, Y', strtotime($program['end_date'])); ?></h3>
                    <p class="stat-label">End Date</p>
                </div>
                <?php endif; ?>
                
                <?php if ($program['max_students']): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="stat-number"><?php echo $program['max_students'] - $students['student_count']; ?></h3>
                    <p class="stat-label">Spots Available</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <?php if ($current_user_role === 'mentor' && $program['mentor_id'] === $current_user_id): ?>
                    <!-- Mentor who owns the program -->
                    <a href="mentorship.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Programs
                    </a>
                    <a href="edit_program.php?id=<?php echo $program_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Program
                    </a>
                    <form action="delete_program.php" method="POST" style="display: inline;">
                        <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this program?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                
                <?php elseif ($current_user_role === 'admin'): ?>
                    <!-- Admin view -->
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="edit_program.php?id=<?php echo $program_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Program
                    </a>
                    <form action="delete_program.php" method="POST" style="display: inline;">
                        <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this program?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                
                <?php elseif ($current_user_role === 'student'): ?>
                    <!-- Student view -->
                    <a href="mentor_profile_view.php?mentor_id=<?php echo $program['mentor_id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Mentor
                    </a>
                    <?php if ($is_enrolled): ?>
                        <button class="btn btn-disabled">
                            <i class="fas fa-check-circle"></i> Already Enrolled
                        </button>
                    <?php else: ?>
                        <form action="send_request.php" method="POST" style="display: inline;">
                            <input type="hidden" name="mentor_id" value="<?php echo $program['mentor_id']; ?>">
                            <input type="hidden" name="program_name" value="<?php echo htmlspecialchars($program['program_name']); ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Request to Join
                            </button>
                        </form>
                    <?php endif; ?>
                
                <?php else: ?>
                    <!-- Other mentors viewing -->
                    <a href="mentorship.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Programs
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once("includes/footer.php"); ?>
</body>
</html>