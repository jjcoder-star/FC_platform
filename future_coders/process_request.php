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

$request_id = intval($_POST['request_id']);
$action = $_POST['action'];

if ($action == 'accept') {
    $status = 'accepted';
    $msg = 'accepted';
} else {
    $status = 'rejected';
    $msg = 'rejected';
}

// Get request details first
$request_query = "SELECT mr.*, m.program_name, m.mentor_id, u.fullname as student_name 
                  FROM mentorship_requests mr
                  JOIN mentorship m ON mr.program_id = m.mentorship_id
                  JOIN users u ON mr.student_id = u.user_id
                  WHERE mr.request_id = ?";
$request_stmt = $conn->prepare($request_query);
$request_stmt->bind_param("i", $request_id);
$request_stmt->execute();
$request_result = $request_stmt->get_result();
$request = $request_result->fetch_assoc();

if (!$request) {
    header("Location: mentorship.php?msg=request_not_found");
    exit();
}

// Verify the mentor owns this program
if ($request['mentor_id'] != $_SESSION['user_id']) {
    header("Location: mentorship.php?msg=unauthorized");
    exit();
}

// Update request status
$update_query = "UPDATE mentorship_requests SET status = ?, response_date = NOW() WHERE request_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("si", $status, $request_id);

if ($update_stmt->execute()) {
    // Create notification for student
$student_id = $request['student_id'];
    $program_name = $request['program_name'];

    if ($status == 'accepted') {
        $notification_msg = "Your request to join '$program_name' has been accepted! You can now chat with your mentor.";
    } else {
        $notification_msg = "Your request to join '$program_name' was rejected. You can try other programs.";
    }
    
    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
    $notif_stmt = $conn->prepare($notif_query);
    $notif_stmt->bind_param("is", $student_id, $notification_msg);
    $notif_stmt->execute();
    
    // Check if request came from notification or mentorship page
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'mentorship.php') !== false) {
header("Location: mentorship.php?msg=$msg");
    } else {
        // Redirect back to the page they came from (likely dashboard)
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'mentor_dashboard.php') . "?msg=$msg");
    }
} else {
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'mentorship.php') !== false) {
        header("Location: mentorship.php?msg=error");
    } else {
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'mentor_dashboard.php') . "?msg=error");
    }
}
exit();
?>
