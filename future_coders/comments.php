<?php
include_once("config.php");

if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);

    $sql = "SELECT c.comment_text, c.created_at, u.fullname, u.profile_picture 
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.post_id = $post_id 
            ORDER BY c.created_at DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="comment">';
            echo '<img src="uploads/' . $row['profile_picture'] . '" class="comment-avatar">';
            echo '<div>';
            echo '<strong>' . htmlspecialchars($row['fullname']) . '</strong>';
            echo '<p>' . htmlspecialchars($row['comment_text']) . '</p>';
            echo '<span>' . date("M d, Y", strtotime($row['created_at'])) . '</span>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No comments yet. Be the first to comment!</p>';
    }
}
?>
