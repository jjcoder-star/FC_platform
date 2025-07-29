<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

// Only mentors can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: mentorship.php");
    exit();
}

$program_id = intval($_GET['id']);
$mentor_id = $_SESSION['user_id'];

// Get program details
$program_sql = "SELECT * FROM mentorship WHERE mentorship_id = ? AND mentor_id = ?";
$program_stmt = $conn->prepare($program_sql);
$program_stmt->bind_param("ii", $program_id, $mentor_id);
$program_stmt->execute();
$program = $program_stmt->get_result()->fetch_assoc();

if (!$program) {
    $_SESSION['error'] = "Program not found or you don't have permission to edit it";
    header("Location: mentorship.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_program'])) {
    $program_name = trim($_POST['program_name']);
    $description = trim($_POST['description']);
    $topic = trim($_POST['topic']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'] ?? null;
    $max_students = $_POST['max_students'] ?? null;
    $status = $_POST['status'];

    $update_sql = "UPDATE mentorship SET 
                  program_name = ?, 
                  description = ?, 
                  topic = ?, 
                  start_date = ?, 
                  end_date = ?, 
                  max_students = ?, 
                  status = ?
                  WHERE mentorship_id = ? AND mentor_id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssssssii", 
        $program_name, 
        $description, 
        $topic, 
        $start_date, 
        $end_date, 
        $max_students, 
        $status,
        $program_id,
        $mentor_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Program updated successfully!";
        header("Location: view_that.php?id=" . $program_id);
        exit();
    } else {
        $_SESSION['error'] = "Error updating program: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Program - Future Coders</title>
    <link rel="stylesheet" href="css/edit_program.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <div class="edit-container">
        <h1><i class="fas fa-edit"></i> Edit Program</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="edit-form">
            <div class="form-group">
                <label for="program_name">Program Name</label>
                <input type="text" id="program_name" name="program_name" 
                       value="<?php echo htmlspecialchars($program['program_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="topic">Topic/Skill Focus</label>
                <input type="text" id="topic" name="topic" 
                       value="<?php echo htmlspecialchars($program['topic'] ?? ''); ?>"
                       placeholder="e.g., Python, Web Development">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php 
                    echo htmlspecialchars($program['description'] ?? ''); 
                ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo $program['start_date']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date (Optional)</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo $program['end_date'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="max_students">Maximum Students (Optional)</label>
                    <input type="number" id="max_students" name="max_students" 
                           value="<?php echo $program['max_students'] ?? ''; ?>" min="1">
                </div>
                <div class="form-group">
                    <label for="status">Program Status</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo ($program['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($program['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="completed" <?php echo ($program['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="view_that.php?id=<?php echo $program_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" name="update_program" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <?php include_once("includes/footer.php"); ?>
</body>
</html>