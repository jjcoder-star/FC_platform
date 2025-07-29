<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$mentorship_id = $_GET['mentorship_id'];

$check = $conn->prepare("SELECT * FROM mentorship_requests WHERE mentorship_id = ? AND student_id = ? AND status = 'accepted'");
$check->bind_param("ii", $mentorship_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    echo "You are not part of this group.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Group Chat</title>
    <link rel="stylesheet" href="group_chat.css">
</head>
<body>
    <div class="group-chat">
        <h2>Group Chat Room</h2>
        <div class="chat-box" id="chat-box">
            <!-- Chat messages will go here -->
        </div>
        <form method="post" id="chat-form">
            <input type="text" name="message" placeholder="Type a message..." required>
            <button type="submit">Send</button>
        </form>
    </div>

    <script>
    // Dummy real-time logic placeholder
    document.getElementById('chat-form').onsubmit = function(e) {
        e.preventDefault();
        const box = document.getElementById('chat-box');
        const msg = this.message.value;
        box.innerHTML += `<div class='msg'><b>You:</b> ${msg}</div>`;
        this.message.value = '';
    };
    </script>
</body>
</html>
