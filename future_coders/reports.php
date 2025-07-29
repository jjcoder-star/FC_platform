<?php
include_once("config.php");

// Check authentication and admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch system-wide statistics
$stats = [];
$sql = "SELECT 
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
            (SELECT COUNT(*) FROM users WHERE role = 'mentor') as total_mentors,
            (SELECT COUNT(*) FROM posts) as total_posts,
            (SELECT COUNT(*) FROM comments) as total_comments,
            (SELECT COUNT(*) FROM code_snippets) as total_snippets,
            (SELECT COUNT(*) FROM resources) as total_resources,
            (SELECT COUNT(*) FROM mentorship WHERE status = 'active') as active_mentorships,
            (SELECT COUNT(*) FROM mentorship WHERE status = 'pending') as pending_mentorships";

$result = $conn->query($sql);
if ($result) {
    $stats = $result->fetch_assoc();
}

// Get top mentors by students mentored
$topMentors = $conn->query("
    SELECT u.fullname, COUNT(mr.student_id) as students_mentored
    FROM users u
    LEFT JOIN mentorship m ON u.user_id = m.mentor_id
    LEFT JOIN mentorship_requests mr ON m.mentorship_id = mr.program_id AND mr.status = 'accepted'
    WHERE u.role = 'mentor'
    GROUP BY u.user_id, u.fullname
    ORDER BY students_mentored DESC
    LIMIT 5
");

// Get recent activities from multiple tables
$activities_sql = "(SELECT 'user' as type, CONCAT('New ', role, ': ', fullname) as description, date_joined as date FROM users ORDER BY date_joined DESC LIMIT 3)
                  UNION ALL
                  (SELECT 'post' as type, CONCAT('Post: ', LEFT(title, 30)) as description, created_at as date FROM posts ORDER BY created_at DESC LIMIT 3)
                  UNION ALL
                  (SELECT 'mentorship' as type, CONCAT('Mentorship started') as description, start_date as date FROM mentorship WHERE status = 'active' ORDER BY start_date DESC LIMIT 3)
                  ORDER BY date DESC LIMIT 5";
$activities = $conn->query($activities_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - Future Coders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/reports.css">
    <?php include('includes/header.php'); ?>
</head>
<body>
    <div class="admin-container">
      
        
        <div class="admin-content">
            <h1><i class="fas fa-chart-bar"></i> System Reports</h1>
            
            <!-- Summary Cards -->
            <div class="report-cards">
                <!-- User Statistics -->
                <div class="report-card">
                    <div class="report-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="report-content">
                        <h3>Total Users</h3>
                        <span><?= $stats['total_users'] ?? 0 ?></span>
                        <div class="report-substats">
                            <span><i class="fas fa-user-graduate"></i> <?= $stats['total_students'] ?? 0 ?> Students</span>
                            <span><i class="fas fa-chalkboard-teacher"></i> <?= $stats['total_mentors'] ?? 0 ?> Mentors</span>
                        </div>
                    </div>
                </div>
                
                <!-- Content Statistics -->
                <div class="report-card">
                    <div class="report-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="report-content">
                        <h3>Content</h3>
                        <div class="report-substats">
                            <span><i class="fas fa-comment"></i> <?= $stats['total_posts'] ?? 0 ?> Posts</span>
                            <span><i class="fas fa-comments"></i> <?= $stats['total_comments'] ?? 0 ?> Comments</span>
                            <span><i class="fas fa-code"></i> <?= $stats['total_snippets'] ?? 0 ?> Code Snippets</span>
                            <span><i class="fas fa-book"></i> <?= $stats['total_resources'] ?? 0 ?> Resources</span>
                        </div>
                    </div>
                </div>
                
                <!-- Mentorship Statistics -->
                <div class="report-card">
                    <div class="report-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="report-content">
                        <h3>Mentorships</h3>
                        <div class="report-substats">
                            <span><i class="fas fa-check-circle"></i> <?= $stats['active_mentorships'] ?? 0 ?> Active</span>
                            <span><i class="fas fa-clock"></i> <?= $stats['pending_mentorships'] ?? 0 ?> Pending</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Mentors Section -->
            <div class="dashboard-section">
                <h2><i class="fas fa-trophy"></i> Top Mentors</h2>
                <div class="mentors-grid">
                    <?php while ($mentor = $topMentors->fetch_assoc()): ?>
                        <div class="mentor-card">
                            <div class="mentor-info">
                                <h4><?php echo htmlspecialchars($mentor['fullname']); ?></h4>
                                <p class="students-count">
                                    <i class="fas fa-users"></i> 
                                    <?php echo $mentor['students_mentored']; ?> students mentored
                                </p>
                            </div>
                            <div class="mentor-badge">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Recent Activities Table -->
            <div class="recent-activities">
                <h2><i class="fas fa-history"></i> Recent Activities</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($activities && $activities->num_rows > 0) {
                            while ($activity = $activities->fetch_assoc()) {
                                echo "<tr>
                                        <td><span class='activity-type {$activity['type']}'>".ucfirst($activity['type'])."</span></td>
                                        <td>".htmlspecialchars($activity['description'])."</td>
                                        <td>".date('M j, Y', strtotime($activity['date']))."</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No recent activities found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>