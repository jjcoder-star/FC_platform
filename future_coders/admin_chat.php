<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

// Check if user is logged in and is admin or mentor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'mentor'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle file upload (only for admins and mentors)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = [
        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'text/plain', 'application/zip', 'application/x-rar-compressed',
        'application/x-zip-compressed'
    ];
    
    if (in_array($_FILES['file_upload']['type'], $allowed_types)) {
        $upload_dir = 'uploads/admin_chat/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['file_upload']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_path)) {
            // Save to database
            $insert_sql = "INSERT INTO admin_chat_messages (user_id, message_type, content, file_path, file_name) 
                           VALUES (?, 'file', ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("isss", $user_id, $_FILES['file_upload']['name'], $target_path, $_FILES['file_upload']['name']);
            $insert_stmt->execute();
            
            // Notify other admins and mentors
            notifyAdminMembers($user_id, "sent a file: " . $_FILES['file_upload']['name']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);
    
    // Save text message
    $insert_sql = "INSERT INTO admin_chat_messages (user_id, message_type, content) 
                   VALUES (?, 'text', ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("is", $user_id, $message);
    $insert_stmt->execute();
    
    // Notify other members
    notifyAdminMembers($user_id, substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''));
}

function notifyAdminMembers($sender_id, $message_part) {
    global $conn;
    
    // Get all admins and mentors
    $members_sql = "SELECT user_id FROM users WHERE role IN ('admin', 'mentor')";
    $members_stmt = $conn->prepare($members_sql);
    $members_stmt->execute();
    $members = $members_stmt->get_result();
    
    $sender_name = $_SESSION['fullname'];
    while ($member = $members->fetch_assoc()) {
        if ($member['user_id'] != $sender_id) {
            $notification_msg = "New message in Official Chat from $sender_name: $message_part";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->bind_param("is", $member['user_id'], $notification_msg);
            $notif_stmt->execute();
        }
    }
}

// Get chat messages
$messages_sql = "SELECT acm.*, u.username, u.fullname, u.profile_picture, u.role 
                FROM admin_chat_messages acm
                JOIN users u ON acm.user_id = u.user_id
                ORDER BY acm.created_at ASC
                LIMIT 200";
$messages_stmt = $conn->prepare($messages_sql);
$messages_stmt->execute();
$messages = $messages_stmt->get_result();

// Get admin/mentor members
$members_sql = "SELECT u.user_id, u.fullname, u.profile_picture, u.role, u.email,
                       CASE WHEN u.user_id = ? THEN 'You' ELSE u.fullname END as display_name
                FROM users u
                WHERE u.role IN ('admin', 'mentor')
                ORDER BY u.role DESC, u.fullname ASC";
$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("i", $user_id);
$members_stmt->execute();
$members = $members_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Chat - Future Coders</title>
    <link rel="stylesheet" href="css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <div class="chat-container">
        <!-- Members Sidebar -->
        <div class="members-sidebar">
            <div class="members-header">
                <h3><i class="fas fa-crown"></i> Official Chat (<?php echo $members->num_rows; ?>)</h3>
                <p class="chat-description">Admins & Mentors Only</p>
                <?php if ($user_role === 'admin'): ?>
                    <button class="video-call-btn" onclick="startVideoCall()">
                        <i class="fas fa-video"></i> Start Video Call
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Members list hidden as per user request -->
            <!-- <div class="members-list">
                <?php while ($member = $members->fetch_assoc()): ?>
                    <div class="member-item">
                        <div class="member-avatar">
                            <img src="uploads/<?php echo htmlspecialchars($member['profile_picture']); ?>" 
                                 alt="<?php echo htmlspecialchars($member['display_name']); ?>">
                            <span class="online-status"></span>
                        </div>
                        <div class="member-info">
                            <div class="member-name">
                                <?php echo htmlspecialchars($member['display_name']); ?>
                                <?php if ($member['role'] === 'admin'): ?>
                                    <span class="admin-badge">Admin</span>
                                <?php elseif ($member['role'] === 'mentor'): ?>
                                    <span class="mentor-badge">Mentor</span>
                                <?php endif; ?>
                            </div>
                            <div class="member-email"><?php echo htmlspecialchars($member['email'] ?: 'No email'); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div> -->
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header">
                <div class="chat-info">
                    <h2><i class="fas fa-crown"></i> Official Chat</h2>
                    <p>Share lessons, projects, and collaborate with the team</p>
                </div>
                <div class="chat-actions">
                    <a href="<?php echo $user_role === 'admin' ? 'admin_dashboard.php' : 'mentor_dashboard.php'; ?>" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php if ($messages->num_rows > 0): ?>
                    <?php while ($message = $messages->fetch_assoc()): ?>
                        <div class="message <?php echo $message['user_id'] == $user_id ? 'sent' : 'received'; ?>" style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px;">
                            <div class="message-avatar" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <img src="uploads/<?php echo htmlspecialchars($message['profile_picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($message['username'] ?? $message['fullname']); ?>" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div style="text-align: center; font-size: 12px; color: #2c3e50; margin-top: 4px; font-weight: 600;">
                                    <?php echo htmlspecialchars($message['username'] ?? $message['fullname']); ?>
                                </div>
                            </div>
                            
                            <div class="message-content" style="max-width: 70%; padding: 10px 14px; border-radius: 15px; font-size: 14px; background: #f0f0f0;">
                                <div class="message-time" style="font-size: 11px; color: #6c757d; margin-bottom: 6px;">
                                    <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                                </div>
                                
                                <?php if ($message['message_type'] === 'file'): ?>
                                    <div class="message-file" style="display: flex; align-items: center; gap: 12px; padding: 8px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                                        <?php 
                                        $file_ext = strtolower(pathinfo($message['file_name'], PATHINFO_EXTENSION));
                                        $icon_class = 'fa-file';
                                        
                                        if (in_array($file_ext, ['pdf'])) $icon_class = 'fa-file-pdf';
                                        elseif (in_array($file_ext, ['doc', 'docx'])) $icon_class = 'fa-file-word';
                                        elseif (in_array($file_ext, ['ppt', 'pptx'])) $icon_class = 'fa-file-powerpoint';
                                        elseif (in_array($file_ext, ['mp4', 'avi', 'mov', 'wmv'])) $icon_class = 'fa-file-video';
                                        elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $icon_class = 'fa-file-image';
                                        elseif (in_array($file_ext, ['zip', 'rar'])) $icon_class = 'fa-file-archive';
                                        ?>
                                        
                                        <i class="fas <?php echo $icon_class; ?>" style="font-size: 20px; color: #6c757d;"></i>
                                        <div class="file-info" style="flex: 1;">
                                            <span class="file-name" style="font-weight: 600; color: #2c3e50; font-size: 13px;">
                                                <?php echo htmlspecialchars($message['file_name']); ?>
                                            </span>
                                            <a href="<?php echo htmlspecialchars($message['file_path']); ?>" 
                                               target="_blank" 
                                               download="<?php echo htmlspecialchars($message['file_name']); ?>"
                                               class="download-btn" style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; font-size: 12px; transition: background 0.3s;">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="message-text" style="color: #2c3e50; line-height: 1.4; font-size: 14px;">
                                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-chat">
                        <i class="fas fa-crown"></i>
                        <p>Welcome to the Official Chat! Start sharing lessons and projects.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="chat-input">
                <form id="file-upload-form" enctype="multipart/form-data" style="display: none;">
                    <input type="file" id="file-upload" name="file_upload" 
                           accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.avi,.mov,.wmv,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip,.rar">
                </form>
                <button class="attachment-btn" onclick="document.getElementById('file-upload').click()" title="Send file">
                    <i class="fas fa-paperclip"></i>
                </button>
                
                <form method="POST" class="message-form" id="messageForm">
                    <input type="text" name="message" placeholder="Share a lesson, project, or message..." required>
                    <button type="submit">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Video Call Modal -->
    <div id="videoCallModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-video"></i> Admin Video Call</h3>
                <button class="close-modal" onclick="closeVideoCall()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="video-container">
                    <video id="localVideo" autoplay muted></video>
                    <video id="remoteVideo" autoplay></video>
                </div>
                <div class="video-controls">
                    <button onclick="toggleMute()" id="muteBtn">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button onclick="toggleVideo()" id="videoBtn">
                        <i class="fas fa-video"></i>
                    </button>
                    <button onclick="endCall()" class="end-call">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        window.onload = function() {
            scrollToBottom();
            // Prevent video call modal from auto-opening
            closeVideoCall();
        };
        
        // File upload handling
        document.getElementById('file-upload').onchange = function() {
            document.getElementById('file-upload-form').submit();
        };
        
        // Auto refresh messages every 5 seconds
        // Disabled to prevent interrupting typing
        // setInterval(function() {
        //     location.reload();
        // }, 5000);

        // AJAX message sending
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(data => {
                // Reload messages area only
                location.reload();
            })
            .catch(error => {
                alert('Error sending message: ' + error);
            });
            form.reset();
        });
        
        // Video call functions
        function startVideoCall() {
            document.getElementById('videoCallModal').style.display = 'flex';
            alert('Video call feature coming soon! This will integrate with a video calling service.');
        }
        
        function closeVideoCall() {
            document.getElementById('videoCallModal').style.display = 'none';
        }
        
        function toggleMute() {
            const btn = document.getElementById('muteBtn');
            const icon = btn.querySelector('i');
            if (icon.classList.contains('fa-microphone')) {
                icon.classList.remove('fa-microphone');
                icon.classList.add('fa-microphone-slash');
            } else {
                icon.classList.remove('fa-microphone-slash');
                icon.classList.add('fa-microphone');
            }
        }
        
        function toggleVideo() {
            const btn = document.getElementById('videoBtn');
            const icon = btn.querySelector('i');
            if (icon.classList.contains('fa-video')) {
                icon.classList.remove('fa-video');
                icon.classList.add('fa-video-slash');
            } else {
                icon.classList.remove('fa-video-slash');
                icon.classList.add('fa-video');
            }
        }
        
        function endCall() {
            closeVideoCall();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('videoCallModal');
            if (event.target === modal) {
                closeVideoCall();
            }
        }
    </script>

    <?php include_once("includes/footer.php"); ?>
</body>
</html> 