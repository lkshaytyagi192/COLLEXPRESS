<?php
session_start();
require_once 'db.php';
require_once 'theme_config.php'; 
// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student'; // Assume student if not set

// Handle sending new message
if(isset($_POST['send_message'])){
    $proposalId = intval($_POST['proposal_id']);
    $receiverId = intval($_POST['receiver_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $senderType = $userType == 'student' ? 'student' : 'service_provider';
    
    $insert = mysqli_query($conn, "INSERT INTO messages (proposal_id, sender_id, receiver_id, sender_type, message) 
                                   VALUES ('$proposalId', '$userId', '$receiverId', '$senderType', '$message')");
    
    if($insert){
        $success = "Message sent successfully!";
        // Mark messages as read for current user
        mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE receiver_id = '$userId' AND proposal_id = '$proposalId'");
    } else {
        $error = "Failed to send message!";
    }
}

// Get selected conversation
$selectedProposalId = isset($_GET['proposal_id']) ? intval($_GET['proposal_id']) : 0;
$selectedConversation = null;

// Fetch all conversations for this user
$conversations = [];
if($userType == 'student'){
    // For students - get proposals with messages
    $convQuery = "SELECT DISTINCT p.id, p.title, p.budget, p.status, 
                         sp.full_name as other_user_name, sp.id as other_user_id,
                         (SELECT COUNT(*) FROM messages m WHERE m.proposal_id = p.id AND m.receiver_id = '$userId' AND m.is_read = 0) as unread_count,
                         (SELECT m.message FROM messages m WHERE m.proposal_id = p.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
                         (SELECT m.created_at FROM messages m WHERE m.proposal_id = p.id ORDER BY m.created_at DESC LIMIT 1) as last_message_time
                  FROM proposals p 
                  LEFT JOIN service_providers sp ON sp.id = p.assigned_sp_id
                  INNER JOIN messages m ON m.proposal_id = p.id
                  WHERE p.user_id = '$userId'
                  ORDER BY last_message_time DESC";
} else {
    // For service providers - get proposals they're involved with
    $convQuery = "SELECT DISTINCT p.id, p.title, p.budget, p.status,
                         u.full_name as other_user_name, u.id as other_user_id,
                         (SELECT COUNT(*) FROM messages m WHERE m.proposal_id = p.id AND m.receiver_id = '$userId' AND m.is_read = 0) as unread_count,
                         (SELECT m.message FROM messages m WHERE m.proposal_id = p.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
                         (SELECT m.created_at FROM messages m WHERE m.proposal_id = p.id ORDER BY m.created_at DESC LIMIT 1) as last_message_time
                  FROM proposals p 
                  INNER JOIN users u ON u.id = p.user_id
                  INNER JOIN messages m ON m.proposal_id = p.id
                  WHERE (p.assigned_sp_id = '$userId' OR m.sender_id = '$userId' OR m.receiver_id = '$userId')
                  ORDER BY last_message_time DESC";
}

$convResult = mysqli_query($conn, $convQuery);
if($convResult){
    while($row = mysqli_fetch_assoc($convResult)){
        $conversations[] = $row;
        if($selectedProposalId == $row['id']){
            $selectedConversation = $row;
        }
    }
}

// If no conversation selected but conversations exist, select first one
if(!$selectedConversation && !empty($conversations)){
    $selectedConversation = $conversations[0];
    $selectedProposalId = $selectedConversation['id'];
}

// Fetch messages for selected conversation
$messages = [];
if($selectedProposalId > 0){
    $msgQuery = "SELECT m.*, 
                        CASE 
                            WHEN m.sender_type = 'student' THEN u.full_name 
                            ELSE sp.full_name 
                        END as sender_name
                 FROM messages m
                 LEFT JOIN users u ON u.id = m.sender_id AND m.sender_type = 'student'
                 LEFT JOIN service_providers sp ON sp.id = m.sender_id AND m.sender_type = 'service_provider'
                 WHERE m.proposal_id = '$selectedProposalId'
                 ORDER BY m.created_at ASC";
    
    $msgResult = mysqli_query($conn, $msgQuery);
    if($msgResult){
        while($row = mysqli_fetch_assoc($msgResult)){
            $messages[] = $row;
        }
    }
    
    // Mark messages as read for current conversation
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE proposal_id = '$selectedProposalId' AND receiver_id = '$userId'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background: #f4f6fb;
    color: #333;
}

.sidebar {
    width: 250px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    padding: 2rem 1rem;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.5rem;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.8rem 1rem;
    text-decoration: none;
    color: white;
    border-radius: 10px;
    margin-bottom: 0.8rem;
    transition: all 0.3s ease;
}

.sidebar a:hover, 
.sidebar a.active {
    background: rgba(255, 255, 255, 0.2);
}

.main {
    margin-left: 250px;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.messages-container {
    display: flex;
    flex: 1;
    height: calc(100vh - 2rem);
    margin: 1rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.conversations-list {
    width: 350px;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
}

.conversations-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}

.conversations-header h2 {
    font-size: 1.2rem;
    color: #1f2937;
}

.conversation-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.conversation-item:hover {
    background: #f8fafc;
}

.conversation-item.active {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
}

.conv-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.conv-preview {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.conv-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #9ca3af;
}

.unread-badge {
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
}

.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}

.chat-header h3 {
    color: #1f2937;
    font-size: 1.1rem;
    margin-bottom: 0.3rem;
}

.chat-header p {
    color: #6b7280;
    font-size: 0.85rem;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message {
    display: flex;
    margin-bottom: 1rem;
}

.message.sent {
    justify-content: flex-end;
}

.message.received {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 0.8rem 1rem;
    border-radius: 18px;
    position: relative;
}

.message.sent .message-bubble {
    background: #3b82f6;
    color: white;
    border-bottom-right-radius: 4px;
}

.message.received .message-bubble {
    background: #f3f4f6;
    color: #1f2937;
    border-bottom-left-radius: 4px;
}

.message-info {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 0.3rem;
}

.message-form {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    background: white;
}

.message-input-area {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}

.message-input-area textarea {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    padding: 0.8rem 1rem;
    resize: none;
    min-height: 40px;
    max-height: 120px;
    font-family: inherit;
}

.send-btn {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.send-btn:hover {
    background: #2563eb;
    transform: scale(1.05);
}

.no-conversations {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
    text-align: center;
}

.no-conversations i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.error, .success {
    padding: 0.8rem;
    margin: 1rem;
    border-radius: 8px;
    text-align: center;
}

.error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.success {
    background: #d1fae5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }
    
    .main {
        margin-left: 200px;
    }
    
    .conversations-list {
        width: 280px;
    }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>🎓 CollExpress</h2>
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="my_proposals.php"><i class="fa-solid fa-briefcase"></i> My Proposals</a>
    <a href="messages.php" class="active"><i class="fa-solid fa-envelope"></i> Messages</a>
    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <?php if(isset($error)) echo '<div class="error">'.$error.'</div>'; ?>
    <?php if(isset($success)) echo '<div class="success">'.$success.'</div>'; ?>
    
    <div class="messages-container">
        <!-- Conversations List -->
        <div class="conversations-list">
            <div class="conversations-header">
                <h2>Conversations</h2>
            </div>
            
            <?php if(!empty($conversations)): ?>
                <?php foreach($conversations as $conv): ?>
                    <div class="conversation-item <?php echo ($selectedProposalId == $conv['id']) ? 'active' : ''; ?>" 
                         onclick="window.location.href='messages.php?proposal_id=<?php echo $conv['id']; ?>'">
                        <div class="conv-title">
                            <?php echo htmlspecialchars($conv['title']); ?>
                        </div>
                        <div class="conv-preview">
                            <?php echo $conv['other_user_name'] ? 'With: '.htmlspecialchars($conv['other_user_name']) : 'No messages yet'; ?>
                        </div>
                        <div class="conv-meta">
                            <span>₹<?php echo number_format($conv['budget']); ?></span>
                            <?php if($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-conversations">
                    <div>
                        <i class="fa-solid fa-comments"></i>
                        <h3>No conversations yet</h3>
                        <p>Start by posting a proposal or bidding on existing ones.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if($selectedConversation): ?>
                <!-- Chat Header -->
                <div class="chat-header">
                    <h3><?php echo htmlspecialchars($selectedConversation['title']); ?></h3>
                    <p>Budget: ₹<?php echo number_format($selectedConversation['budget']); ?> • Status: <?php echo ucfirst($selectedConversation['status']); ?>
                    <?php if($selectedConversation['other_user_name']): ?>
                        • With: <?php echo htmlspecialchars($selectedConversation['other_user_name']); ?>
                    <?php endif; ?>
                    </p>
                </div>

                <!-- Messages Area -->
                <div class="messages-area">
                    <?php if(!empty($messages)): ?>
                        <?php foreach($messages as $msg): ?>
                            <div class="message <?php echo ($msg['sender_id'] == $userId) ? 'sent' : 'received'; ?>">
                                <div class="message-bubble">
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                    <div class="message-info">
                                        <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; color: #6b7280; margin-top: 2rem;">
                            <i class="fa-solid fa-comment-dots" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Message Form -->
                <div class="message-form">
                    <form method="post" class="message-input-area">
                        <input type="hidden" name="proposal_id" value="<?php echo $selectedProposalId; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $selectedConversation['other_user_id']; ?>">
                        <textarea name="message" placeholder="Type your message..." required></textarea>
                        <button type="submit" name="send_message" class="send-btn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-conversations">
                    <div>
                        <i class="fa-solid fa-envelope-open"></i>
                        <h3>Select a conversation</h3>
                        <p>Choose a conversation from the list to start messaging.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
const messagesArea = document.querySelector('.messages-area');
if(messagesArea) {
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

// Auto-resize textarea
document.querySelector('textarea[name="message"]')?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Send message on Enter (but allow Shift+Enter for new line)
document.querySelector('textarea[name="message"]')?.addEventListener('keydown', function(e) {
    if(e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.closest('form').submit();
    }
});
</script>

</body>
</html>