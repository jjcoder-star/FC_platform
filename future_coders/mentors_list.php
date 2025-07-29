<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all mentors with their programs count
$mentors_sql = "SELECT u.*, 
               COUNT(DISTINCT m.program_name) AS program_count
               FROM users u
               LEFT JOIN mentorship m ON u.user_id = m.mentor_id AND m.mentee_id IS NULL
               WHERE u.role = 'mentor'
               GROUP BY u.user_id";
$mentors = $conn->query($mentors_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Find Mentors - Future Coders</title>
    <link rel="stylesheet" href="css/mentors_list.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <div class="mentors-container">
        <h1><i class="fas fa-user-graduate"></i> Available Mentors</h1>
        
        <div class="mentors-scroll">
            <div class="mentors-grid">
                <?php while ($mentor = $mentors->fetch_assoc()): ?>
                    <div class="mentor-card">
                        <div class="mentor-image">
                            <img src="uploads/<?php echo htmlspecialchars($mentor['profile_picture'] ?? 'default.png'); ?>">
                            <?php if ($mentor['year'] >= 4): ?>
                                <div class="senior-badge">
                                    <i class="fas fa-graduation-cap"></i> Senior
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mentor-info">
                            <h3><?php echo htmlspecialchars($mentor['fullname']); ?></h3>
                            <p class="mentor-title">Year <?php echo $mentor['year']; ?> Student</p>
                            <div class="mentor-stats">
                                <span><i class="fas fa-book"></i> <?php echo $mentor['program_count']; ?> Programs</span>
                            </div>
                            <div class="mentor-skills">
                                <?php 
                                $skills = explode(',', $mentor['skills'] ?? '');
                                foreach($skills as $skill): 
                                    if(trim($skill)): ?>
                                        <span class="skill-tag"><?php echo trim($skill); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <div class="mentor-actions">
                            <a href="mentor_profile_view.php?mentor_id=<?php echo $mentor['user_id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View Profile
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <?php include_once("includes/footer.php"); ?>
</body>
</html>