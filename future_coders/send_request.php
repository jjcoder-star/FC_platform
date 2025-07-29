<?php
include 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mentors_list.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$program_id = intval($_POST['program_id']);
$mentor_id = intval($_POST['mentor_id']);

// Check if request already exists
$check_query = "SELECT * FROM mentorship_requests WHERE student_id = ? AND program_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $student_id, $program_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    header("Location: mentor_profile_view.php?mentor_id=$mentor_id&msg=already_requested");
    exit();
}

// Check if program is full
$program_query = "SELECT m.*, 
                  (SELECT COUNT(*) FROM mentorship_requests WHERE program_id = m.mentorship_id AND status = 'accepted') as enrolled_count
                  FROM mentorship m 
                  WHERE m.mentorship_id = ?";
$program_stmt = $conn->prepare($program_query);
$program_stmt->bind_param("i", $program_id);
$program_stmt->execute();
$program_result = $program_stmt->get_result();
$program = $program_result->fetch_assoc();

if (!$program) {
    header("Location: mentors_list.php?msg=program_not_found");
    exit();
}

// Check if program is full
if ($program['max_students'] && $program['enrolled_count'] >= $program['max_students']) {
    header("Location: mentor_profile_view.php?mentor_id=$mentor_id&msg=program_full");
    exit();
}

// Insert the request
$insert_query = "INSERT INTO mentorship_requests (student_id, program_id, status) VALUES (?, ?, 'pending')";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("ii", $student_id, $program_id);

if ($insert_stmt->execute()) {
    // Get student name for notifications
    $student_query = "SELECT fullname FROM users WHERE user_id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_name = $student_result->fetch_assoc()['fullname'];

// Insert notification for mentor
    $mentor_notification = "New mentorship request from $student_name for program: " . $program['program_name'];
    $mentor_notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $mentor_notif_stmt = $conn->prepare($mentor_notif_query);
    $mentor_notif_stmt->bind_param("is", $mentor_id, $mentor_notification);
    $mentor_notif_stmt->execute();
    
    // Insert notification for student
    $student_notification = "You requested to join program: " . $program['program_name'] . ". Waiting for mentor response.";
    $student_notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $student_notif_stmt = $conn->prepare($student_notif_query);
    $student_notif_stmt->bind_param("is", $student_id, $student_notification);
    $student_notif_stmt->execute();

header("Location: mentor_profile_view.php?mentor_id=$mentor_id&msg=request_sent");
} else {
    header("Location: mentor_profile_view.php?mentor_id=$mentor_id&msg=error");
}
exit();
?>
