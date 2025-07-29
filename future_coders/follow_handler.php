<?php
include_once("config.php");
include_once("follow_functions.php");

if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $follower_id = $_SESSION['user_id'];
    $following_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Validate action
    if (!in_array($action, ['follow', 'unfollow'])) {
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Prevent users from following themselves
    if ($follower_id === $following_id) {
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Check if target user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $following_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }
    
    // Process follow/unfollow
    if ($action === 'follow') {
        followUser($conn, $follower_id, $following_id);
    } elseif ($action === 'unfollow') {
        unfollowUser($conn, $follower_id, $following_id);
    }
    
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit();
}
?>