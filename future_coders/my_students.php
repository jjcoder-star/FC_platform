<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

// Check if user is mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch mentor's students with profile and program info (without follow status)
$students_sql = "SELECT u.user_id, u.fullname, u.profile_picture, m.program_name
                 FROM users u
                 JOIN mentorship_requests mr ON u.user_id = mr.student_id
                 JOIN mentorship m ON mr.program_id = m.mentorship_id
                 WHERE m.mentor_id = ? AND mr.status = 'accepted'
                 GROUP BY u.user_id, m.program_name";
$students_stmt = $conn->prepare($students_sql);
$students_stmt->bind_param("i", $user_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Students - Future Coders</title>
    <link rel="stylesheet" href="css/resources.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Professional styling for students list */
        .students-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .student-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 250px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: box-shadow 0.3s ease;
        }
        .student-item:hover {
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #25d366;
        }
        .student-info {
            flex: 1;
        }
        .student-name {
            font-weight: 700;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .student-program {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i>
                <h2>My Students (<?php echo $students_result->num_rows; ?>)</h2>
            </div>
            <div class="card-body">
                <?php if ($students_result->num_rows > 0): ?>
                    <div class="students-list">
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <div class="student-item">
                                <img src="uploads/<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="<?php echo htmlspecialchars($student['fullname']); ?>" class="student-avatar" />
                                <div class="student-info">
                                    <div class="student-name"><?php echo htmlspecialchars($student['fullname']); ?></div>
                                    <div class="student-program">Program: <?php echo htmlspecialchars($student['program_name']); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No students found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once("includes/footer.php"); ?>
</body>
</html>
