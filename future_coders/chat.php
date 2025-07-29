<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if program parameter exists
if (!isset($_GET['program'])) {
    header("Location: " . ($_SESSION['role'] === 'mentor' ? "mentorship.php" : "student_dashboard.php"));
    exit();
}

$program_name = $_GET['program'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Verify user has access to this program
$access_sql = "SELECT m.*, u.fullname, u.profile_picture 
               FROM mentorship m
               JOIN users u ON m.mentor_id = u.user_id
               WHERE m.program_name = ? AND
               ((m.mentor_id = ?) OR 
                (m.mentorship_id IN (SELECT program_id FROM mentorship_requests WHERE student_id = ? AND status = 'accepted')))";
$access_stmt = $conn->prepare($access_sql);
$access_stmt->bind_param("sii", $program_name, $user_id, $user_id);
$access_stmt->execute();
$program = $access_stmt->get_result()->fetch_assoc();

if (!$program) {
    header("Location: " . ($_SESSION['role'] === 'mentor' ? "mentorship.php" : "student_dashboard.php"));
    exit();
}

// Handle file upload and message sending with role-based restrictions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        // Only mentor who created the program can upload files
        if ($user_role === 'mentor' && $user_id === $program['mentor_id']) {
            $allowed_types = [
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'text/plain', 'application/zip', 'application/x-rar-compressed'
            ];
            
            if (in_array($_FILES['file_upload']['type'], $allowed_types)) {
                $upload_dir = 'uploads/chat_files/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = uniqid() . '_' . basename($_FILES['file_upload']['name']);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_path)) {
                    // Save to database
                    $insert_sql = "INSERT INTO chat_messages (program_id, user_id, message_type, content, file_path, file_name) 
                                   VALUES (?, ?, 'file', ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("iisss", $program['mentorship_id'], $user_id, 
                                           $_FILES['file_upload']['name'], $target_path, $_FILES['file_upload']['name']);
                    $insert_stmt->execute();
                    
                    // Notify members
                    notifyMembers($program['mentorship_id'], $user_id, "sent a file: " . $_FILES['file_upload']['name']);
                    
                    // Redirect to prevent form resubmission
                    header("Location: chat.php?program=" . urlencode($program_name));
                    exit();
                }
            }
        }
    } elseif (isset($_POST['message']) && !empty(trim($_POST['message']))) {
        $message = trim($_POST['message']);
        
        // Students can only send text messages
        if ($user_role === 'student') {
            $insert_sql = "INSERT INTO chat_messages (program_id, user_id, message_type, content) 
                           VALUES (?, ?, 'text', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iis", $program['mentorship_id'], $user_id, $message);
            $insert_stmt->execute();
            
            // Notify members
            notifyMembers($program['mentorship_id'], $user_id, substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''));
            
            // Redirect to prevent form resubmission
            header("Location: chat.php?program=" . urlencode($program_name));
            exit();
        }
        // Mentor who created the program can send text messages
        elseif ($user_role === 'mentor' && $user_id === $program['mentor_id']) {
            $insert_sql = "INSERT INTO chat_messages (program_id, user_id, message_type, content) 
                           VALUES (?, ?, 'text', ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iis", $program['mentorship_id'], $user_id, $message);
            $insert_stmt->execute();
            
            // Notify members
            notifyMembers($program['mentorship_id'], $user_id, substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''));
            
            // Redirect to prevent form resubmission
            header("Location: chat.php?program=" . urlencode($program_name));
            exit();
        }
    }
}

function notifyMembers($program_id, $sender_id, $message_part) {
    global $conn;
    
    // Get all members of this program
    $members_sql = "SELECT DISTINCT user_id FROM (
                    SELECT mentor_id as user_id FROM mentorship WHERE mentorship_id = ?
                    UNION
                    SELECT student_id FROM mentorship_requests WHERE program_id = ? AND status = 'accepted'
                   ) as members";
    $members_stmt = $conn->prepare($members_sql);
    $members_stmt->bind_param("ii", $program_id, $program_id);
    $members_stmt->execute();
    $members = $members_stmt->get_result();
    
    $sender_name = $_SESSION['fullname'];
    $program_name = $_GET['program'];
    
    while ($member = $members->fetch_assoc()) {
        // Don't notify the sender
        if ($member['user_id'] != $sender_id) {
            $notification_msg = "$sender_name sent a message in $program_name: $message_part";
            $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notif_stmt = $conn->prepare($notif_query);
            $notif_stmt->bind_param("is", $member['user_id'], $notification_msg);
            $notif_stmt->execute();
        }
        }
    }


// Get chat messages
$messages_sql = "SELECT cm.*, u.fullname, u.profile_picture, u.role 
                FROM chat_messages cm
                JOIN users u ON cm.user_id = u.user_id
                WHERE cm.program_id = ?
                ORDER BY cm.created_at ASC
                LIMIT 200";
$messages_stmt = $conn->prepare($messages_sql);
$messages_stmt->bind_param("i", $program['mentorship_id']);
$messages_stmt->execute();
$messages = $messages_stmt->get_result();

// Get program members
$members_sql = "SELECT u.user_id, u.fullname, u.profile_picture, u.role, u.email,
                       CASE WHEN u.user_id = ? THEN 'You' ELSE u.fullname END as display_name
                FROM users u
                WHERE u.user_id IN (
                    SELECT mentor_id FROM mentorship WHERE mentorship_id = ?
                    UNION
                    SELECT student_id FROM mentorship_requests WHERE program_id = ? AND status = 'accepted'
                )
                ORDER BY u.role DESC, u.fullname ASC";
$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("iii", $user_id, $program['mentorship_id'], $program['mentorship_id']);
$members_stmt->execute();
$members = $members_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($program_name); ?> - Chat</title>
    <link rel="stylesheet" href="css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>

    <div class="chat-container">
        <!-- Members Sidebar -->
        <div class="members-sidebar">
            <div class="members-header">
                <h3><i class="fas fa-users"></i> <?php echo htmlspecialchars($program_name); ?> (<?php echo $members->num_rows; ?>)</h3>
                <?php if ($user_role === 'mentor'): ?>
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
                                <?php if ($member['role'] === 'mentor'): ?>
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
                    <h2><?php echo htmlspecialchars($program_name); ?></h2>
                    <p>Created by <?php echo htmlspecialchars($program['fullname']); ?></p>
                </div>
                <div class="chat-actions">
                    <a href="<?php echo $user_role === 'mentor' ? 'mentorship.php' : 'student_dashboard.php'; ?>" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
            <?php if ($messages->num_rows > 0): ?>
                <?php while ($message = $messages->fetch_assoc()): ?>
                        <div class="message <?php echo $message['user_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <div class="message-avatar">
                                <img src="uploads/<?php echo htmlspecialchars($message['profile_picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($message['fullname']); ?>">
                            </div>
                            
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-sender"><?php echo htmlspecialchars($message['fullname']); ?></span>
                            <?php if ($message['role'] === 'mentor'): ?>
                                <span class="mentor-badge">Mentor</span>
                            <?php endif; ?>
                                    <span class="message-time"><?php echo date('h:i A', strtotime($message['created_at'])); ?></span>
                        </div>
                                
                                <?php if ($message['message_type'] === 'file'): ?>
                                    <div class="message-file">
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
                                        
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                        <div class="file-info">
                                            <span class="file-name"><?php echo htmlspecialchars($message['file_name']); ?></span>
                                <a href="<?php echo htmlspecialchars($message['file_path']); ?>" 
                                   target="_blank" 
                                               download="<?php echo htmlspecialchars($message['file_name']); ?>"
                                               class="download-btn">
                                                <i class="fas fa-download"></i> Download
                                </a>
                                        </div>
                            </div>
                        <?php else: ?>
                            <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                    </div>
                                <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <p>No messages yet. Start the conversation!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="chat-input">
                <?php if ($user_role === 'mentor'): ?>
                <form id="file-upload-form" enctype="multipart/form-data" style="display: none;">
                        <input type="file" id="file-upload" name="file_upload" 
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.avi,.mov,.wmv,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip,.rar">
                </form>
                    <button class="attachment-btn" onclick="document.getElementById('file-upload').click()" title="Send file">
                    <i class="fas fa-paperclip"></i>
                </button>
            <?php endif; ?>
            
                <form method="POST" class="message-form" id="messageForm">
                <input type="text" name="message" placeholder="Type your message..." required>
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
                <h3><i class="fas fa-video"></i> Video Call</h3>
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
            // Here you would integrate with a video calling service like Twilio, Agora, etc.
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