<?php
include 'config.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mentorship.php");
    exit();
}

$program_id = intval($_POST['program_id']);
$mentor_id = $_SESSION['user_id'];

// Verify the mentor owns this program
$verify_query = "SELECT * FROM mentorship WHERE mentorship_id = ? AND mentor_id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("ii", $program_id, $mentor_id);
$verify_stmt->execute();
$program = $verify_stmt->get_result()->fetch_assoc();

if (!$program) {
    header("Location: mentorship.php?msg=unauthorized");
    exit();
}

// Check if there are any pending or accepted requests for this program
$requests_query = "SELECT COUNT(*) as count FROM mentorship_requests WHERE program_id = ? AND status IN ('pending', 'accepted')";
$requests_stmt = $conn->prepare($requests_query);
$requests_stmt->bind_param("i", $program_id);
$requests_stmt->execute();
$requests_count = $requests_stmt->get_result()->fetch_assoc()['count'];

if ($requests_count > 0) {
    header("Location: mentorship.php?msg=cannot_delete_with_requests");
    exit();
}

// Delete the program
$delete_query = "DELETE FROM mentorship WHERE mentorship_id = ? AND mentor_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("ii", $program_id, $mentor_id);

if ($delete_stmt->execute()) {
    // Also delete any rejected requests for this program
    $delete_requests_query = "DELETE FROM mentorship_requests WHERE program_id = ? AND status = 'rejected'";
    $delete_requests_stmt = $conn->prepare($delete_requests_query);
    $delete_requests_stmt->bind_param("i", $program_id);
    $delete_requests_stmt->execute();
    
    header("Location: mentorship.php?msg=program_deleted");
} else {
    header("Location: mentorship.php?msg=delete_error");
}
exit();
?> 