<?php
include_once("config.php");
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$mentor_id = $_SESSION['user_id'];

// Handle request processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'accept' or 'reject'
    
    // Process request using process_request.php
    include 'process_request.php';
    exit();
}

// Handle program deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_program'])) {
    include 'delete_program.php';
    exit();
}

// Get mentor's programs
$programs_sql = "SELECT * FROM mentorship 
                WHERE mentor_id = ? AND mentee_id IS NULL
                ORDER BY start_date DESC";
$programs_stmt = $conn->prepare($programs_sql);
$programs_stmt->bind_param("i", $mentor_id);
$programs_stmt->execute();
$programs = $programs_stmt->get_result();

// Get pending requests from mentorship_requests table
$requests_sql = "SELECT mr.*, m.program_name, u.fullname AS student_name, u.profile_picture
                FROM mentorship_requests mr
                JOIN mentorship m ON mr.program_id = m.mentorship_id
                JOIN users u ON mr.student_id = u.user_id
                WHERE m.mentor_id = ? AND mr.status = 'pending'
                ORDER BY mr.request_date DESC";
$requests_stmt = $conn->prepare($requests_sql);
$requests_stmt->bind_param("i", $mentor_id);
$requests_stmt->execute();
$requests = $requests_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Mentorship Programs</title>
    <link rel="stylesheet" href="css/mentorship.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include_once("includes/header.php"); ?>
    
    <div class="mentorship-container">
        <div class="mentorship-header">
            <h1><i class="fas fa-graduation-cap"></i> My Mentorship Programs</h1>
            <a href="create_mentorship.php" class="btn-create">
                <i class="fas fa-plus"></i> Create New Program
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="message-container">
                <?php 
                $msg = $_GET['msg'];
                $message_class = 'success';
                $message_text = '';
                
                switch($msg) {
                    case 'program_deleted':
                        $message_text = 'Program deleted successfully!';
                        break;
                    case 'cannot_delete_with_requests':
                        $message_class = 'error';
                        $message_text = 'Cannot delete program with pending or accepted requests.';
                        break;
                    case 'unauthorized':
                        $message_class = 'error';
                        $message_text = 'You are not authorized to perform this action.';
                        break;
                    case 'delete_error':
                        $message_class = 'error';
                        $message_text = 'Error deleting program. Please try again.';
                        break;
                    case 'accepted':
                        $message_text = 'Request accepted successfully!';
                        break;
                    case 'rejected':
                        $message_text = 'Request rejected successfully!';
                        break;
                }
                ?>
                <div class="message <?= $message_class ?>">
                    <i class="fas fa-<?= $message_class === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= $message_text ?>
                    <button class="close-message" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="programs-section">
            <?php if ($programs->num_rows > 0): ?>
                <?php while ($program = $programs->fetch_assoc()): ?>
                    <div class="program-card">
                        <div class="program-header">
                            <h2><?php echo htmlspecialchars($program['program_name']); ?></h2>
                            <p><?php echo htmlspecialchars($program['description']); ?></p>
                        </div>
                        
                        <div class="program-details">
                            <div class="detail-item">
                                <span class="detail-label">Duration:</span>
                                <span class="detail-value">
                                    <?php echo date('M j, Y', strtotime($program['start_date'])); ?> - 
                                    <?php echo $program['end_date'] ? date('M j, Y', strtotime($program['end_date'])) : 'Ongoing'; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Students:</span>
                                <span class="detail-value">
                                    <?php 
                                    $count_sql = "SELECT COUNT(*) FROM mentorship_requests WHERE program_id = ? AND status = 'accepted'";
                                    $count_stmt = $conn->prepare($count_sql);
                                    $count_stmt->bind_param("i", $program['mentorship_id']);
                                    $count_stmt->execute();
                                    $count = $count_stmt->get_result()->fetch_array()[0];
                                    echo $count;
                                    ?>
                                    <?php if ($program['max_students']): ?>
                                        / <?php echo $program['max_students']; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="program-actions">
                            <a href="chat.php?program=<?php echo urlencode($program['program_name']); ?>" class="btn-chat">
                                <i class="fas fa-comments"></i> Chat
                            </a>
                            <a href="view_that.php?id=<?php echo $program['mentorship_id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit_program.php?id=<?php echo $program['mentorship_id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn-delete" onclick="confirmDelete(<?php echo $program['mentorship_id']; ?>, '<?php echo htmlspecialchars($program['program_name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-graduation-cap"></i>
                    <p>You haven't created any mentorship programs yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($requests->num_rows > 0): ?>
            <div class="requests-section">
                <h2><i class="fas fa-clock"></i> Pending Requests</h2>
                <?php while ($request = $requests->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-info">
                            <div class="student-info">
                                <img src="uploads/<?php echo htmlspecialchars($request['profile_picture'] ?: 'default.jpg'); ?>" alt="Student" class="student-pic">
                                <div>
                                    <h3><?php echo htmlspecialchars($request['student_name']); ?></h3>
                                    <p>Request to join: <strong><?php echo htmlspecialchars($request['program_name']); ?></strong></p>
                                    <small>Requested: <?php echo date('M j, Y g:i A', strtotime($request['request_date'])); ?></small>
                                </div>
                            </div>
                        </div>
                        <form method="POST" class="request-actions">
                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                            <button type="submit" name="action" value="accept" class="btn-accept">
                                <i class="fas fa-check"></i> Accept
                            </button>
                            <button type="submit" name="action" value="reject" class="btn-reject">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
                <button class="close-modal" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the program "<span id="programName"></span>"?</p>
                <p class="warning">This action cannot be undone. All rejected requests for this program will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="program_id" id="programId">
                    <button type="submit" name="delete_program" class="btn-confirm-delete">
                        <i class="fas fa-trash"></i> Delete Program
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(programId, programName) {
            document.getElementById('programId').value = programId;
            document.getElementById('programName').textContent = programName;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.parentElement.remove();
            });
        }, 5000);
    </script>
    
    <?php include_once("includes/footer.php"); ?>
</body>
</html>