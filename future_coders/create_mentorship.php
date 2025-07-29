<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mentor_id = $_SESSION['user_id'];
    $program_name = trim($_POST['program_name']);
    $topic = trim($_POST['topic']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'] ?? null;
    $max_students = $_POST['max_students'] ?? null;
    
    // Basic validation
    if (empty($program_name)) {
        $_SESSION['error'] = "Program name is required";
    } else {
        // Create the program
        $sql = "INSERT INTO mentorship (mentor_id, program_name, topic, description, start_date, end_date, max_students, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssi", $mentor_id, $program_name, $topic, $description, $start_date, $end_date, $max_students);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Mentorship program created successfully!";
            header("Location: mentor_profile_view.php");
            exit();
        } else {
            $_SESSION['error'] = "Error creating program: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Mentorship Program - Future Coders</title>
    <link rel="stylesheet" href="css/mentor_profile_view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .create-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #0a66c2;
            outline: none;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .submit-btn {
            background: #0a66c2;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .submit-btn:hover {
            background: #004182;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .success-message {
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="mentor-nav">
        <div class="nav-left">
            <a href="mentor_dashboard.php">Home</a>
            <a href="resources.php">Resources</a>
        </div>
        <div class="nav-right">
            <a href="mentor_profile_view.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Programs
            </a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="create-form">
        <h1><i class="fas fa-plus-circle"></i> Create New Mentorship Program</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="create_mentorship.php">
            <div class="form-group">
                <label for="program_name">Program Name</label>
                <input type="text" id="program_name" name="program_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="topic">Topic/Skill Focus</label>
                <input type="text" id="topic" name="topic" class="form-control" placeholder="e.g., Python, Web Development, etc.">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date (Optional)</label>
                    <input type="date" id="end_date" name="end_date" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="max_students">Maximum Students (Leave empty for no limit)</label>
                <input type="number" id="max_students" name="max_students" class="form-control" min="1">
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Create Program
            </button>
        </form>
    </div>

    <?php include_once("includes/footer.php"); ?>
</body>
</html>