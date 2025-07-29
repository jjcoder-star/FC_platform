<?php
function isFollowing($conn, $follower_id, $following_id) {
    $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $follower_id, $following_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function followUser($conn, $follower_id, $following_id) {
    if (!isFollowing($conn, $follower_id, $following_id)) {
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $follower_id, $following_id);
        return $stmt->execute();
    }
    return false;
}

function unfollowUser($conn, $follower_id, $following_id) {
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $follower_id, $following_id);
    return $stmt->execute();
}

function getFollowersCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

function getFollowingCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}
?>