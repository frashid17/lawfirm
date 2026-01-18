<?php
/**
 * CLIENT MESSAGES
 * Chat/messaging system between clients and advocates
 */

session_start();
require_once '../config/database.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$message = "";
$error = "";


// Handle sending message - check for POST with message data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['send_message']) || (isset($_POST['message']) && !empty(trim($_POST['message']))))) {
    $case_no = $_POST['case_no'] ?? null;
    $message_text = trim($_POST['message'] ?? '');
    
    if (empty($case_no)) {
        $error = "Please select a case";
    } elseif (empty($message_text)) {
        $error = "Please enter a message";
    } else {
        try {
            // Get advocate assigned to case
            $stmt = $conn->prepare("SELECT AdvtId FROM CASE_ASSIGNMENT WHERE CaseNo = ? AND Status = 'Active' LIMIT 1");
            $stmt->execute([$case_no]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assignment) {
                try {
                    $stmt = $conn->prepare("INSERT INTO MESSAGE (CaseNo, ClientId, AdvocateId, SenderRole, Message) VALUES (?, ?, ?, 'client', ?)");
                    $result = $stmt->execute([$case_no, $client_id, $assignment['AdvtId'], $message_text]);
                    
                    if ($result) {
                        $message_id = $conn->lastInsertId();
                        if ($message_id > 0) {
                            header("Location: messages.php?case=" . urlencode($case_no) . "&sent=1");
                            exit();
                        } else {
                            $error = "Message was not inserted. Please check database connection.";
                        }
                    } else {
                        $error_info = $stmt->errorInfo();
                        $error = "Failed to send message. SQL Error: " . ($error_info[2] ?? 'Unknown error');
                    }
                } catch(PDOException $e) {
                    if ($e->getCode() == '42S02') {
                        $error = "MESSAGE table does not exist in database. Please run database/create_message_table.sql in phpMyAdmin first.";
                    } else {
                        $error = "Error sending message: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
                    }
                }
            } else {
                $error = "No advocate assigned to this case";
            }
        } catch(PDOException $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    }
}

// Check if message was just sent
// Check if message was just sent (used for auto-refresh cooldown, no alert shown)
// The sent parameter is used by JavaScript to prevent auto-refresh after sending

// Mark messages as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try {
        $stmt = $conn->prepare("UPDATE MESSAGE SET IsRead = TRUE WHERE MessageId = ? AND ClientId = ?");
        $stmt->execute([$_GET['mark_read'], $client_id]);
    } catch(PDOException $e) {
        // Silent fail
    }
}

// Get client's cases
try {
    $stmt = $conn->prepare("SELECT c.* FROM `CASE` c WHERE c.ClientId = ? ORDER BY c.CaseName");
    $stmt->execute([$client_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading cases: " . $e->getMessage();
}

// Get selected case or first case
$selected_case = $_GET['case'] ?? ($cases[0]['CaseNo'] ?? null);

// Get messages for selected case
$messages = [];
if ($selected_case) {
    try {
        // Mark all advocate messages as read
        $stmt = $conn->prepare("UPDATE MESSAGE SET IsRead = TRUE WHERE CaseNo = ? AND ClientId = ? AND SenderRole = 'advocate'");
        $stmt->execute([$selected_case, $client_id]);
        
        $stmt = $conn->prepare("SELECT m.*, a.FirstName, a.LastName 
                                FROM MESSAGE m 
                                LEFT JOIN ADVOCATE a ON m.AdvocateId = a.AdvtId 
                                WHERE m.CaseNo = ? AND m.ClientId = ? 
                                ORDER BY m.CreatedAt ASC");
        $stmt->execute([$selected_case, $client_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error loading messages: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .chat-container {
            display: flex;
            gap: 20px;
            height: 600px;
        }
        .chat-cases {
            width: 250px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            overflow-y: auto;
        }
        .chat-cases h4 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        .case-item {
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .case-item:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--primary-color);
        }
        .case-item.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .chat-messages {
            flex: 1;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        .messages-header {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .messages-list {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f0f2f5;
        }
        .message-item {
            display: flex;
            gap: 10px;
        }
        /* Client view: Client messages on RIGHT, Advocate messages on LEFT */
        .message-item.client {
            justify-content: flex-end;
        }
        .message-item.advocate {
            justify-content: flex-start;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 12px;
            word-wrap: break-word;
            position: relative;
        }
        /* Client messages (sent by client) - green, right side */
        .message-item.client .message-bubble {
            background: #dcf8c6;
            color: #000;
            border-bottom-right-radius: 4px;
        }
        /* Advocate messages (received from advocate) - white, left side */
        .message-item.advocate .message-bubble {
            background: white;
            color: var(--dark-text);
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 11px;
            margin-top: 4px;
            opacity: 0.7;
        }
        .message-form {
            padding: 10px 15px;
            border-top: 1px solid var(--border);
            background: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message-input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            background: #f0f2f5;
            border-radius: 24px;
            padding: 8px 16px;
            gap: 8px;
        }
        .message-input-wrapper input[type="text"] {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            padding: 6px 0;
            font-size: 15px;
            color: var(--dark-text);
        }
        .message-input-wrapper input[type="text"]::placeholder {
            color: #999;
        }
        .send-btn {
            background: var(--primary-color);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 18px;
        }
        .send-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
        }
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Messages - Client Portal</h1>
            <div class="header-user">
                <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="my_cases.php"><i class="fas fa-folder-open"></i> My Cases</a></li>
                <li><a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php" class="active"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="chat-container">
            <div class="chat-cases">
                <h4>Select Case</h4>
                <?php foreach ($cases as $case): ?>
                    <div class="case-item <?php echo ($selected_case == $case['CaseNo']) ? 'active' : ''; ?>" 
                         onclick="window.location.href='?case=<?php echo $case['CaseNo']; ?>'">
                        <strong><?php echo htmlspecialchars($case['CaseName']); ?></strong>
                        <br><small><?php echo htmlspecialchars($case['CaseType']); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-messages">
                <?php if ($selected_case): ?>
                    <?php
                    $case_name = '';
                    foreach ($cases as $c) {
                        if ($c['CaseNo'] == $selected_case) {
                            $case_name = $c['CaseName'];
                            break;
                        }
                    }
                    ?>
                    <div class="messages-header">
                        <h3 style="margin: 0; color: white;"><?php echo htmlspecialchars($case_name); ?></h3>
                    </div>
                    
                    <div class="messages-list" id="messagesList">
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="message-item <?php echo $msg['SenderRole']; ?>">
                                    <div class="message-bubble">
                                        <?php if (!empty($msg['Message'])): ?>
                                            <div><?php echo nl2br(htmlspecialchars($msg['Message'])); ?></div>
                                        <?php endif; ?>
                                        <?php if ($msg['SenderRole'] == 'advocate'): ?>
                                            <div class="message-time">
                                                <?php echo htmlspecialchars($msg['FirstName'] . ' ' . $msg['LastName']); ?> • 
                                                <?php echo date('M d, Y H:i', strtotime($msg['CreatedAt'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="message-time">
                                                You • <?php echo date('M d, Y H:i', strtotime($msg['CreatedAt'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--gray); margin: auto;">No messages yet. Start the conversation!</p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="" class="message-form" id="messageForm" onsubmit="return handleFormSubmit(event)">
                        <input type="hidden" name="case_no" value="<?php echo $selected_case; ?>">
                        <input type="hidden" name="send_message" value="1">
                        <div class="message-input-wrapper">
                            <input type="text" name="message" id="messageInput" placeholder="Type a message..." autocomplete="off">
                        </div>
                        <button type="submit" name="send_message" class="send-btn" id="sendBtn" title="Send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                        <p style="color: var(--gray);">Please select a case to view messages</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
        
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        
        function checkInput() {
            const hasText = messageInput.value.trim().length > 0;
            sendBtn.disabled = !hasText;
        }
        
        messageInput.addEventListener('input', checkInput);
        checkInput();
        
        // Prevent duplicate form submissions
        let formSubmitting = false;
        function handleFormSubmit(e) {
            if (formSubmitting) {
                e.preventDefault();
                return false;
            }
            formSubmitting = true;
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            return true;
        }
        
        // Track if user is typing
        let isTyping = false;
        let typingTimeout;
        
        messageInput.addEventListener('focus', function() {
            isTyping = true;
        });
        
        messageInput.addEventListener('input', function() {
            isTyping = true;
            clearTimeout(typingTimeout);
            // Reset typing flag after 2 seconds of no typing
            typingTimeout = setTimeout(function() {
                isTyping = false;
            }, 2000);
        });
        
        messageInput.addEventListener('blur', function() {
            setTimeout(function() {
                isTyping = false;
            }, 1000);
        });
        
        // Track if message was just sent
        let messageJustSent = <?php echo (isset($_GET['sent']) && $_GET['sent'] == '1') ? 'true' : 'false'; ?>;
        let sendCooldown = 0;
        
        // Disable auto-refresh for 10 seconds after sending
        if (messageJustSent) {
            sendCooldown = 10;
            let cooldownInterval = setInterval(function() {
                sendCooldown--;
                if (sendCooldown <= 0) {
                    clearInterval(cooldownInterval);
                }
            }, 1000);
        }
        
        // Auto-refresh every 5 seconds (only if user is not typing and not in cooldown)
        setInterval(function() {
            if (document.visibilityState === 'visible' && !isTyping && messageInput.value.trim() === '' && sendCooldown <= 0) {
                location.reload();
            }
        }, 5000);
    </script>
</body>
</html>
