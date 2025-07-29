<?php
// Get user's notifications
$user_id = $_SESSION['user_id'] ?? 0;
$notifications_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5";
$notifications_stmt = $conn->prepare($notifications_query);
$notifications_stmt->bind_param("i", $user_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['count'];
?>

<link rel="stylesheet" href="css/notifications.css">

<div class="notifications-container" id="notificationsContainer">
    <div class="notifications-header">
        <h3><i class="fas fa-bell"></i> Notifications <?php if ($unread_count > 0): ?><span class="badge"><?= $unread_count ?></span><?php endif; ?></h3>
        <button class="close-notifications" onclick="toggleNotifications()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="notifications-list">
        <?php if ($notifications_result->num_rows > 0): ?>
            <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                <div class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $notification['notification_id'] ?>">
                    <div class="notification-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="notification-content">
                        <p><?= htmlspecialchars($notification['message']) ?></p>
                        <small><?= date('M j, Y g:i A', strtotime($notification['timestamp'])) ?></small>
                        
                        <?php 
                        // Check if this is a mentorship request notification for mentors
                        if ($_SESSION['role'] === 'mentor' && strpos($notification['message'], 'New mentorship request from') !== false) {
                            // Extract request details from the message
                            $message = $notification['message'];
                            if (preg_match('/New mentorship request from (.+?) for program: (.+)/', $message, $matches)) {
                                $student_name = $matches[1];
                                $program_name = $matches[2];
                                
                                // Get the actual request details
                                $request_query = "SELECT mr.request_id, mr.student_id, m.mentorship_id 
                                                FROM mentorship_requests mr
                                                JOIN mentorship m ON mr.program_id = m.mentorship_id
                                                JOIN users u ON mr.student_id = u.user_id
                                                WHERE u.fullname = ? AND m.program_name = ? AND mr.status = 'pending'
                                                ORDER BY mr.request_date DESC LIMIT 1";
                                $request_stmt = $conn->prepare($request_query);
                                $request_stmt->bind_param("ss", $student_name, $program_name);
                                $request_stmt->execute();
                                $request_result = $request_stmt->get_result();
                                
                                if ($request_result->num_rows > 0) {
                                    $request = $request_result->fetch_assoc();
                                    ?>
                                    <div class="notification-actions">
                                        <form method="POST" action="process_request.php" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                            <button type="submit" name="action" value="accept" class="btn-accept-small">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn-reject-small">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </div>
                    <button class="mark-read" onclick="markAsRead(<?= $notification['notification_id'] ?>)">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="notification-toggle" onclick="toggleNotifications()">
    <i class="fas fa-bell"></i>
    <?php if ($unread_count > 0): ?>
        <span class="notification-badge"><?= $unread_count ?></span>
    <?php endif; ?>
</div>

<script>
function toggleNotifications() {
    const container = document.getElementById('notificationsContainer');
    container.classList.toggle('open');
}

function markAsRead(notificationId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
            notificationItem.classList.remove('unread');
            notificationItem.classList.add('read');
            
            // Update badge count
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
        }
    });
}

// Close notifications when clicking outside
document.addEventListener('click', function(event) {
    const container = document.getElementById('notificationsContainer');
    const toggle = document.querySelector('.notification-toggle');
    
    if (!container.contains(event.target) && !toggle.contains(event.target)) {
        container.classList.remove('open');
    }
});
</script> 