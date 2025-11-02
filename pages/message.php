<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Update user's online status
$update_sql = "UPDATE users SET is_online = TRUE, last_seen = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();
$update_stmt->close();

// Clean up old online statuses
$cleanup_sql = "UPDATE users SET is_online = FALSE WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
$conn->query($cleanup_sql);

// FIXED: COMPLETELY REVISED CONVERSATION QUERY TO PREVENT DUPLICATES
$conversations_query = "
    SELECT 
        c.id AS conversation_id,
        CASE 
            WHEN c.user1_id = ? THEN c.user2_id 
            ELSE c.user1_id 
        END AS other_user_id,
        CASE 
            WHEN c.user1_id = ? THEN u2.username 
            ELSE u1.username 
        END AS other_user,
        CASE 
            WHEN c.user1_id = ? THEN u2.profile_pic 
            ELSE u1.profile_pic 
        END AS other_user_pic,
        CASE 
            WHEN c.user1_id = ? THEN u2.is_online 
            ELSE u1.is_online 
        END AS other_user_online,
        CASE 
            WHEN c.user1_id = ? THEN u2.last_seen 
            ELSE u1.last_seen 
        END AS other_user_last_seen,
        (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY timestamp DESC LIMIT 1) AS last_message,
        (SELECT timestamp FROM messages WHERE conversation_id = c.id ORDER BY timestamp DESC LIMIT 1) AS last_message_time
    FROM conversations c
    LEFT JOIN users u1 ON u1.id = c.user1_id
    LEFT JOIN users u2 ON u2.id = c.user2_id
    WHERE c.user1_id = ? OR c.user2_id = ?
    GROUP BY other_user_id
    ORDER BY last_message_time DESC";

$stmt = $conn->prepare($conversations_query);
$stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations_result = $stmt->get_result();
$conversations = [];

while ($row = $conversations_result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

// Search users based on input
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_query = "
        SELECT id, username, profile_pic, is_online, last_seen 
        FROM users 
        WHERE username LIKE CONCAT('%', ?, '%') AND id != ?";
    $stmt = $conn->prepare($search_query);
    $stmt->bind_param("si", $search, $user_id);
    $stmt->execute();
    $search_result = $stmt->get_result();
    $users = [];
    while ($row = $search_result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
    exit();
}

// Check or create conversation - FIXED TO PREVENT DUPLICATE CONVERSATIONS
if (isset($_GET['user_id'])) {
    $other_user_id = intval($_GET['user_id']);

    // Check if conversation exists - IMPROVED QUERY
    $check_conversation_query = "
        SELECT id 
        FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
        LIMIT 1";
    $stmt = $conn->prepare($check_conversation_query);
    $stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $conversation = $result->fetch_assoc();
        echo json_encode(['status' => 'existing', 'conversation_id' => $conversation['id']]);
    } else {
        // Create a new conversation - ADDED DUPLICATE CHECK BEFORE INSERT
        $check_again_sql = "SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
        $check_stmt = $conn->prepare($check_again_sql);
        $check_stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing = $check_result->fetch_assoc();
            echo json_encode(['status' => 'existing', 'conversation_id' => $existing['id']]);
        } else {
            $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $other_user_id);
            if ($stmt->execute()) {
                $new_conversation_id = $stmt->insert_id;
                echo json_encode(['status' => 'new', 'conversation_id' => $new_conversation_id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create conversation']);
            }
        }
        $check_stmt->close();
    }
    exit();
}

if (isset($_GET['conversation_id'])) {
    $conversation_id = intval($_GET['conversation_id']);

    $messages_query = "
        SELECT m.message_text, m.timestamp, 
               IF(m.sender_id = ?, 'You', u.username) AS sender,
               u.profile_pic,
               m.sender_id
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.timestamp ASC";
    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param("ii", $user_id, $conversation_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    $messages = [];
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode($messages);
    exit();
}

// Get user status
if (isset($_GET['get_status'])) {
    $other_user_id = intval($_GET['get_status']);
    $status_sql = "SELECT is_online, last_seen FROM users WHERE id = ?";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $other_user_id);
    $status_stmt->execute();
    $result = $status_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        echo json_encode([
            'is_online' => $user_data['is_online'],
            'last_seen' => $user_data['last_seen'],
            'status_text' => getStatusText($user_data['is_online'], $user_data['last_seen'])
        ]);
    }
    exit();
}

// Send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversation_id = intval($_POST['conversation_id']);
    $message_text = trim($_POST['message_text']);
    
    // FIXED: XSS Prevention - Sanitize message text
    $message_text = htmlspecialchars($message_text, ENT_QUOTES, 'UTF-8');
    
    // Validate message is not empty after sanitization
    if (empty($message_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $conversation_id, $user_id, $message_text);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    }
    $stmt->close();
    exit();
}

// Helper function to format time
function formatTime($timestamp) {
    if (empty($timestamp)) return 'No messages yet';
    
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    } else {
        return date('M j', $time);
    }
}

// Helper function to format last seen time
function formatLastSeen($last_seen) {
    if (empty($last_seen)) return 'Long time ago';
    
    $last_seen_time = strtotime($last_seen);
    $now = time();
    $diff = $now - $last_seen_time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' min ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

// Helper function to get status text
function getStatusText($is_online, $last_seen) {
    if ($is_online) {
        return 'Online';
    } else {
        return formatLastSeen($last_seen);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/modern-theme.css">
<title>MUFFEIA - Messages</title>
<style>
/* Additional mobile-specific fixes */
@media (max-width: 768px) {
    .menu-toggle {
        z-index: 1001;
        position: relative;
    }
    
    .sidebar.active {
        transform: translateX(0);
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
    
    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }
}
</style>
</head>
<body>
    <!-- Overlay for mobile menu -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../logo/m-blues.png" alt="MUFFEIA" class="logo-image">
                    <span>MUFFEIA</span>
                </div>
                <button class="sidebar-close" id="sidebarClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="theme-toggle">
                    <input type="checkbox" id="theme-toggle" />
                    <span class="slider">
                        <i class="fas fa-sun"></i>
                        <i class="fas fa-moon"></i>
                    </span>
                </label>
                <span class="theme-label">Light/Dark Mode</span>
            </div>
            
            <div class="nav-items">
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="message.php" class="nav-item active">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge" id="messageBadge" style="display: none;">0</span>
                </a>
                <a href="notifications.php" class="nav-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="badge" id="notificationBadge" style="display: none;">0</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation Bar -->
            <header class="top-nav">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="top-nav-content">
                    <h1>Messages</h1>
                    <div class="user-actions">
                       
                        <a href="notifications.php" class="icon-btn notification-btn" role="button">
                            <i class="fas fa-bell"></i>
                            <span class="notification-dot" style="display: none;"></span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Messages Container -->
            <div class="messages-container">
                <!-- Conversations Sidebar -->
                <div class="conversations-sidebar" id="conversationsSidebar">
                    <div class="conversations-header">
                        <h2>Conversations</h2>
                    </div>

                    <div class="search-container">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search users...">
                        </div>
                    </div>

                    <div class="search-results-container" id="searchResultsContainer">
                        <!-- Search results will appear here -->
                    </div>

                    <div class="conversations-list" id="conversationsList">
                        <?php if (empty($conversations)): ?>
                            <div class="empty-conversations">
                                <i class="fas fa-comments"></i>
                                <h3>No conversations yet</h3>
                                <p>Start a conversation by searching for users above</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            // FIXED: Use conversation_id as unique identifier instead of user_id
                            $displayed_conversations = [];
                            foreach ($conversations as $conversation): 
                                // Skip if we've already displayed this conversation
                                if (in_array($conversation['conversation_id'], $displayed_conversations)) {
                                    continue;
                                }
                                $displayed_conversations[] = $conversation['conversation_id'];
                                
                                $profile_pic_url = (!empty($conversation['other_user_pic']) && $conversation['other_user_pic'] != 'default.png') 
                                    ? "../" . $conversation['other_user_pic']
                                    : null;
                                
                                // Format last message time
                                $last_message_time = formatTime($conversation['last_message_time']);
                                
                                // Get status text
                                $status_text = $conversation['other_user_online'] ? 'Online' : formatLastSeen($conversation['other_user_last_seen']);
                            ?>
                                <div class="conversation-item" data-id="<?= htmlspecialchars($conversation['conversation_id']); ?>" data-user-id="<?= htmlspecialchars($conversation['other_user_id']); ?>">
                                    <div class="conversation-avatar">
                                        <?php if ($profile_pic_url): ?>
                                            <img src="<?= htmlspecialchars($profile_pic_url); ?>" alt="<?= htmlspecialchars($conversation['other_user']); ?>" onerror="handleImageError(this)">
                                            <div class="avatar-fallback">
                                                <?= strtoupper(substr(htmlspecialchars($conversation['other_user']), 0, 1)); ?>
                                            </div>
                                        <?php else: ?>
                                            <?= strtoupper(substr(htmlspecialchars($conversation['other_user']), 0, 1)); ?>
                                        <?php endif; ?>
                                        <?php if ($conversation['other_user_online']): ?>
                                            <div class="online-indicator"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-info">
                                        <h4><?= htmlspecialchars($conversation['other_user']); ?></h4>
                                        <p class="conversation-preview">
                                            <?= !empty($conversation['last_message']) ? htmlspecialchars($conversation['last_message']) : 'Start a conversation'; ?>
                                        </p>
                                        <div class="conversation-time"><?= $last_message_time; ?></div>
                                    </div>
                                    <div class="conversation-status">
                                        <span class="status-text <?= $conversation['other_user_online'] ? 'online' : 'offline'; ?>"><?= $status_text; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="chat-area" id="chatArea">
                    <!-- Mobile Chat Header -->
                    <div class="mobile-chat-header" id="mobileChatHeader" style="display: none;">
                        <button class="back-to-conversations" id="backToConversations">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div class="chat-header-info">
                            <h3 id="mobileChatName">Select a conversation</h3>
                        </div>
                    </div>

                    <!-- Desktop Chat Header -->
                    <div class="chat-header" id="chatHeader">
                        <div class="chat-header-avatar" id="headerAvatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="chat-header-info">
                            <h3 id="headerName">Select a conversation</h3>
                            <div class="chat-status" id="headerStatus">Start chatting</div>
                        </div>
                    </div>

                    <div class="messages-area" id="messagesArea">
                        <div class="empty-chat">
                            <i class="fas fa-comment-dots"></i>
                            <h3>No conversation selected</h3>
                            <p>Choose a conversation from the list to start messaging</p>
                        </div>
                    </div>

                    <div class="message-form" id="messageForm" style="display: none;">
                        <div class="message-input-wrapper">
                            <textarea class="message-input" id="messageText" placeholder="Type your message..." rows="1"></textarea>
                            <button class="send-button" id="sendMessage">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/mode.js"></script>
    <script>
    // Global function to handle broken images
    function handleImageError(img) {
        img.style.display = 'none';
        const fallback = img.nextElementSibling;
        if (fallback && fallback.classList.contains('avatar-fallback')) {
            fallback.style.display = 'flex';
        }
    }

    // Format last seen time for JavaScript
    function formatLastSeen(timestamp) {
        if (!timestamp) return 'a long time ago';
        
        const lastSeen = new Date(timestamp);
        const now = new Date();
        const diff = now - lastSeen;
        
        if (diff < 60000) {
            return 'just now';
        } else if (diff < 3600000) {
            return Math.floor(diff / 60000) + ' min ago';
        } else if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
        } else {
            const days = Math.floor(diff / 86400000);
            return days + ' day' + (days > 1 ? 's' : '') + ' ago';
        }
    }

    // Update message count function
    function updateMessageCount() {
        fetch('../api/get_message_count.php')
            .then(response => response.json())
            .then(data => {
                const messageBadge = document.getElementById('messageBadge');
                const sidebarMessageBadge = document.querySelector('.nav-item[href="message.php"] .badge');
                
                if (data.count > 0) {
                    if (messageBadge) messageBadge.textContent = data.count;
                    if (sidebarMessageBadge) sidebarMessageBadge.textContent = data.count;
                    
                    // Show badges
                    if (messageBadge) messageBadge.style.display = 'inline';
                    if (sidebarMessageBadge) sidebarMessageBadge.style.display = 'inline';
                } else {
                    // Hide badges
                    if (messageBadge) messageBadge.style.display = 'none';
                    if (sidebarMessageBadge) sidebarMessageBadge.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching message count:', error);
            });
    }

    // Update notification count function
    function updateNotificationCount() {
        fetch('../api/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const notificationBadge = document.getElementById('notificationBadge');
                const sidebarNotificationBadge = document.querySelector('.nav-item[href="notifications.php"] .badge');
                const topNavNotificationDot = document.querySelector('.notification-btn .notification-dot');
                
                if (data.count > 0) {
                    if (notificationBadge) notificationBadge.textContent = data.count;
                    if (sidebarNotificationBadge) sidebarNotificationBadge.textContent = data.count;
                    
                    // Show badges and dot
                    if (notificationBadge) notificationBadge.style.display = 'inline';
                    if (sidebarNotificationBadge) sidebarNotificationBadge.style.display = 'inline';
                    if (topNavNotificationDot) topNavNotificationDot.style.display = 'block';
                } else {
                    // Hide badges and dot
                    if (notificationBadge) notificationBadge.style.display = 'none';
                    if (sidebarNotificationBadge) sidebarNotificationBadge.style.display = 'none';
                    if (topNavNotificationDot) topNavNotificationDot.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching notification count:', error);
            });
    }

    // FIXED: XSS Prevention function
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const isMobile = window.innerWidth <= 768;
        const conversationsSidebar = document.getElementById('conversationsSidebar');
        const chatArea = document.getElementById('chatArea');
        const mobileChatHeader = document.getElementById('mobileChatHeader');
        const backToConversations = document.getElementById('backToConversations');
        const searchInput = document.getElementById('searchInput');
        const searchResultsContainer = document.getElementById('searchResultsContainer');
        const conversationsList = document.getElementById('conversationsList');
        const messagesArea = document.getElementById('messagesArea');
        const messageForm = document.getElementById('messageForm');
        const messageText = document.getElementById('messageText');
        const sendButton = document.getElementById('sendMessage');
        const headerName = document.getElementById('headerName');
        const headerAvatar = document.getElementById('headerAvatar');
        const headerStatus = document.getElementById('headerStatus');
        const mobileChatName = document.getElementById('mobileChatName');
        
        let activeConversationId = null;
        let activeUserId = null;

        // FIXED: Mobile menu functionality
        const menuToggle = document.getElementById('menuToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebar = document.getElementById('sidebar');

        // Initialize mobile menu event listeners
        function initMobileMenu() {
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    console.log('Menu toggle clicked');
                    sidebar.classList.add('active');
                    sidebarOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', function(e) {
                    e.stopPropagation();
                    console.log('Sidebar close clicked');
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.stopPropagation();
                    console.log('Overlay clicked');
                    sidebar.classList.remove('active');
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // Close sidebar when clicking on nav items (mobile)
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
        }

        // Initialize counts
        updateMessageCount();
        updateNotificationCount();
        
        // Update counts every 30 seconds
        setInterval(updateMessageCount, 30000);
        setInterval(updateNotificationCount, 30000);
        
        // Update counts when page becomes visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updateMessageCount();
                updateNotificationCount();
            }
        });

        // Update online status periodically
        function startOnlineStatusUpdater() {
            // Update user's own status every 2 minutes
            setInterval(() => {
                fetch('message.php?update_online_status=1')
                    .catch(err => console.log('Status update failed:', err));
            }, 120000);
            
            // Update status for active conversation every 30 seconds
            setInterval(updateActiveUserStatus, 30000);
        }

        // Update status for the currently active user
        async function updateActiveUserStatus() {
            if (activeUserId) {
                try {
                    const response = await fetch(`message.php?get_status=${activeUserId}`);
                    const statusData = await response.json();
                    
                    // Update chat header status
                    const statusElement = document.getElementById('headerStatus');
                    if (statusElement) {
                        statusElement.innerHTML = `
                            <span class="status-dot ${statusData.is_online ? 'online' : 'offline'}"></span>
                            ${escapeHtml(statusData.status_text)}
                        `;
                        statusElement.className = `chat-status ${statusData.is_online ? 'online' : 'offline'}`;
                    }
                    
                    // Update mobile header
                    const mobileHeader = document.getElementById('mobileChatHeader');
                    if (mobileHeader && mobileHeader.style.display !== 'none') {
                        const existingStatus = mobileHeader.querySelector('.mobile-status');
                        if (existingStatus) {
                            existingStatus.remove();
                        }
                        const statusDiv = document.createElement('div');
                        statusDiv.className = `mobile-status chat-status ${statusData.is_online ? 'online' : 'offline'}`;
                        statusDiv.style.fontSize = '0.8rem';
                        statusDiv.style.marginTop = '0.25rem';
                        statusDiv.innerHTML = `
                            <span class="status-dot ${statusData.is_online ? 'online' : 'offline'}"></span>
                            ${escapeHtml(statusData.status_text)}
                        `;
                        mobileHeader.querySelector('.chat-header-info').appendChild(statusDiv);
                    }
                    
                    // Update conversation list status
                    const conversationItem = document.querySelector(`.conversation-item[data-user-id="${activeUserId}"]`);
                    if (conversationItem) {
                        const statusElement = conversationItem.querySelector('.status-text');
                        if (statusElement) {
                            statusElement.textContent = statusData.status_text;
                            statusElement.className = `status-text ${statusData.is_online ? 'online' : 'offline'}`;
                        }
                        
                        // Update online indicator
                        const avatar = conversationItem.querySelector('.conversation-avatar');
                        let indicator = avatar.querySelector('.online-indicator');
                        if (statusData.is_online) {
                            if (!indicator) {
                                indicator = document.createElement('div');
                                indicator.className = 'online-indicator';
                                avatar.appendChild(indicator);
                            }
                        } else if (indicator) {
                            indicator.remove();
                        }
                    }
                    
                } catch (error) {
                    console.error('Error updating status:', error);
                }
            }
        }

        // Mobile navigation functions
        function showConversations() {
            if (isMobile) {
                conversationsSidebar.classList.add('active');
                mobileChatHeader.style.display = 'none';
            }
        }

        function showChat() {
            if (isMobile) {
                conversationsSidebar.classList.remove('active');
                mobileChatHeader.style.display = 'flex';
            }
        }

        // Set initial state
        if (isMobile) {
            showConversations();
        }

        // Back button handler
        backToConversations.addEventListener('click', showConversations);

        // Handle conversation selection
        conversationsList.addEventListener('click', function(e) {
            const conversationItem = e.target.closest('.conversation-item');
            if (conversationItem) {
                // Remove active class from all items
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to clicked item
                conversationItem.classList.add('active');
                
                // Update active conversation
                activeConversationId = conversationItem.getAttribute('data-id');
                activeUserId = conversationItem.getAttribute('data-user-id');
                
                // Update chat header
                const userName = conversationItem.querySelector('h4').textContent;
                const userAvatar = conversationItem.querySelector('.conversation-avatar').innerHTML;
                const statusText = conversationItem.querySelector('.status-text').textContent;
                const isOnline = conversationItem.querySelector('.status-text').classList.contains('online');
                
                headerName.textContent = userName;
                mobileChatName.textContent = userName;
                headerAvatar.innerHTML = userAvatar;
                headerStatus.innerHTML = `
                    <span class="status-dot ${isOnline ? 'online' : 'offline'}"></span>
                    ${escapeHtml(statusText)}
                `;
                headerStatus.className = `chat-status ${isOnline ? 'online' : 'offline'}`;
                
                // Show message form and hide empty state
                messageForm.style.display = 'block';
                messagesArea.innerHTML = '';
                
                // Handle mobile navigation
                showChat();
                
                // Load messages
                loadMessages(activeConversationId);
                
                // Start status updates for this user
                updateActiveUserStatus();
            }
        });

        // Load messages for a conversation
        async function loadMessages(conversationId) {
            try {
                messagesArea.innerHTML = `
                    <div class="empty-chat">
                        <i class="fas fa-spinner fa-spin"></i>
                        <h3>Loading messages...</h3>
                    </div>
                `;
                
                const response = await fetch(`message.php?conversation_id=${conversationId}`);
                const messages = await response.json();
                
                if (messages.length === 0) {
                    messagesArea.innerHTML = `
                        <div class="empty-chat">
                            <i class="fas fa-comments"></i>
                            <h3>No messages yet</h3>
                            <p>Start the conversation by sending a message</p>
                        </div>
                    `;
                    return;
                }
                
                messagesArea.innerHTML = '';
                messages.forEach(msg => {
                    const messageElement = document.createElement('div');
                    messageElement.className = `message ${msg.sender === 'You' ? 'you' : 'other'}`;
                    
                    const timestamp = new Date(msg.timestamp);
                    const timeString = formatTime(timestamp);
                    
                    // FIXED: XSS Prevention - Use textContent instead of innerHTML where possible
                    const avatarContent = msg.sender === 'You' 
                        ? '<i class="fas fa-user"></i>'
                        : (msg.profile_pic && msg.profile_pic !== 'default.png' 
                            ? `<img src="../${escapeHtml(msg.profile_pic)}" alt="${escapeHtml(msg.sender)}" onerror="handleImageError(this)"><div class="avatar-fallback">${escapeHtml(msg.sender.charAt(0).toUpperCase())}</div>`
                            : `<span>${escapeHtml(msg.sender.charAt(0).toUpperCase())}</span>`);
                    
                    messageElement.innerHTML = `
                        <div class="message-avatar">
                            ${avatarContent}
                        </div>
                        <div class="message-content">
                            ${msg.sender !== 'You' ? `<div class="message-sender">${escapeHtml(msg.sender)}</div>` : ''}
                            <div class="message-text">${escapeHtml(msg.message_text)}</div>
                            <div class="message-time">${escapeHtml(timeString)}</div>
                        </div>
                    `;
                    
                    messagesArea.appendChild(messageElement);
                });
                
                // Scroll to bottom
                messagesArea.scrollTop = messagesArea.scrollHeight;
            } catch (error) {
                console.error('Error loading messages:', error);
                messagesArea.innerHTML = `
                    <div class="empty-chat">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error loading messages</h3>
                        <p>Please try again later</p>
                    </div>
                `;
            }
        }

        // Format time for display
        function formatTime(timestamp) {
            const now = new Date();
            const diff = now - timestamp;
            
            if (diff < 60000) {
                return 'Just now';
            } else if (diff < 3600000) {
                return Math.floor(diff / 60000) + 'm ago';
            } else if (diff < 86400000) {
                return Math.floor(diff / 3600000) + 'h ago';
            } else {
                return timestamp.toLocaleDateString();
            }
        }

        // Handle sending messages
        sendButton.addEventListener('click', sendMessage);
        messageText.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        async function sendMessage() {
            const text = messageText.value.trim();
            if (text && activeConversationId) {
                try {
                    const formData = new URLSearchParams();
                    formData.append('conversation_id', activeConversationId);
                    formData.append('message_text', text);

                    const response = await fetch('message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        messageText.value = '';
                        messageText.style.height = 'auto';
                        
                        // Reload messages to show the new one
                        loadMessages(activeConversationId);
                        
                        // Update message count
                        updateMessageCount();
                    } else if (result.status === 'error') {
                        alert(result.message || 'Error sending message');
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Error sending message. Please try again.');
                }
            }
        }

        // Auto-resize textarea
        messageText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        async function performSearch() {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                searchResultsContainer.classList.remove('active');
                return;
            }

            try {
                searchResultsContainer.classList.add('loading');
                
                const response = await fetch(`message.php?search=${encodeURIComponent(query)}`);
                const users = await response.json();
                
                if (users.length === 0) {
                    searchResultsContainer.innerHTML = `
                        <div class="search-result-item">
                            <div class="search-result-info">
                                <span>No users found</span>
                            </div>
                        </div>
                    `;
                } else {
                    searchResultsContainer.innerHTML = users.map(user => `
                        <div class="search-result-item" data-user-id="${user.id}">
                            <div class="search-result-avatar">
                                ${user.profile_pic && user.profile_pic !== 'default.png' 
                                    ? `<img src="../${escapeHtml(user.profile_pic)}" alt="${escapeHtml(user.username)}" onerror="handleImageError(this)"><div class="avatar-fallback">${escapeHtml(user.username.charAt(0).toUpperCase())}</div>`
                                    : `<span>${escapeHtml(user.username.charAt(0).toUpperCase())}</span>`}
                                ${user.is_online ? '<div class="online-indicator"></div>' : ''}
                            </div>
                            <div class="search-result-info">
                                <span>${escapeHtml(user.username)}</span>
                                <div class="search-result-status ${user.is_online ? 'online' : 'offline'}">
                                    ${user.is_online ? 'Online' : 'Last seen ' + formatLastSeen(user.last_seen)}
                                </div>
                            </div>
                            <button class="start-conversation-btn">Message</button>
                        </div>
                    `).join('');
                }
                
                searchResultsContainer.classList.add('active');
                searchResultsContainer.classList.remove('loading');
            } catch (error) {
                console.error('Error searching users:', error);
                searchResultsContainer.innerHTML = `
                    <div class="search-result-item">
                        <div class="search-result-info">
                            <span>Error searching users</span>
                        </div>
                    </div>
                `;
                searchResultsContainer.classList.remove('loading');
            }
        }

        // Handle starting conversation from search results
        searchResultsContainer.addEventListener('click', async function(e) {
            if (e.target.classList.contains('start-conversation-btn')) {
                const searchItem = e.target.closest('.search-result-item');
                const userId = searchItem.getAttribute('data-user-id');
                const userName = searchItem.querySelector('span').textContent;
                const userAvatar = searchItem.querySelector('.search-result-avatar').innerHTML;
                
                try {
                    const response = await fetch(`message.php?user_id=${userId}`);
                    const result = await response.json();
                    
                    if (result.status === 'existing' || result.status === 'new') {
                        // FIXED: Check if conversation already exists by user ID, not conversation ID
                        let existingItem = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
                        
                        if (existingItem) {
                            // Conversation exists, just select it
                            existingItem.click();
                        } else {
                            // Create new conversation item
                            const conversationItem = document.createElement('div');
                            conversationItem.className = 'conversation-item active';
                            conversationItem.setAttribute('data-id', result.conversation_id);
                            conversationItem.setAttribute('data-user-id', userId);
                            
                            conversationItem.innerHTML = `
                                <div class="conversation-avatar">
                                    ${userAvatar}
                                </div>
                                <div class="conversation-info">
                                    <h4>${escapeHtml(userName)}</h4>
                                    <p class="conversation-preview">Start a conversation</p>
                                    <div class="conversation-time">Just now</div>
                                </div>
                                <div class="conversation-status">
                                    <span class="status-text offline">Online</span>
                                </div>
                            `;
                            
                            // Add to top of conversations list
                            const emptyState = conversationsList.querySelector('.empty-conversations');
                            if (emptyState) {
                                emptyState.remove();
                            }
                            
                            // FIXED: Remove any existing conversation with same user before adding new one
                            const existingByUser = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
                            if (existingByUser) {
                                existingByUser.remove();
                            }
                            
                            conversationsList.insertBefore(conversationItem, conversationsList.firstChild);
                            
                            // Select the new conversation
                            conversationItem.click();
                        }
                        
                        // Clear search
                        searchInput.value = '';
                        searchResultsContainer.classList.remove('active');
                    }
                } catch (error) {
                    console.error('Error starting conversation:', error);
                    alert('Error starting conversation. Please try again.');
                }
            }
        });

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResultsContainer.contains(e.target)) {
                searchResultsContainer.classList.remove('active');
            }
        });

        // FIXED: Initialize mobile menu
        initMobileMenu();

        // Start the online status updater
        startOnlineStatusUpdater();

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                // Reset mobile states when switching to desktop
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    </script>
</body>
</html>