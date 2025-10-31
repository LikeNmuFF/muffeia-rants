<?php
include '../includes/db.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
$sql = "SELECT username, email, created_at, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch posts created by the user with like counts
$sql_posts = "SELECT p.id, p.title, p.description, p.created_at, 
              (SELECT COUNT(*) FROM post_likes WHERE problem_id = p.id) as like_count
              FROM problems p 
              WHERE p.user_id = ? 
              ORDER BY p.created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

// Get user stats
$stats_sql = "SELECT 
              (SELECT COUNT(*) FROM problems WHERE user_id = ?) as post_count,
              (SELECT COUNT(*) FROM solutions WHERE user_id = ?) as solution_count,
              (SELECT COUNT(*) FROM post_likes WHERE problem_id IN (SELECT id FROM problems WHERE user_id = ?)) as total_likes";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Handle profile picture upload
$error_message = "";
$success_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    if ($_FILES["profile_pic"]["error"] == UPLOAD_ERR_NO_FILE) {
        $error_message = "No file selected. Please choose a picture to upload.";
    } else {
        $target_dir = "../uploads/profile_pics/";
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
        if ($check === false) {
            $error_message = "File is not an image.";
        } else {
            if (in_array(strtolower($file_extension), ["jpg", "jpeg", "png", "gif"])) {
                if ($_FILES["profile_pic"]["size"] > 5000000) {
                    $error_message = "Sorry, your file is too large. Maximum size is 5MB.";
                } else {
                    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                        // Delete old profile picture if it exists
                        if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) {
                            unlink($user['profile_pic']);
                        }
                        
                        $sql = "UPDATE users SET profile_pic = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $relative_path = "uploads/profile_pics/" . $new_filename;
                        $stmt->bind_param("si", $relative_path, $user_id);
                        if ($stmt->execute()) {
                            $user['profile_pic'] = $relative_path;
                            $success_message = "Profile picture updated successfully!";
                        } else {
                            $error_message = "Error updating profile picture in database.";
                        }
                    } else {
                        $error_message = "Error uploading the file.";
                    }
                }
            } else {
                $error_message = "Sorry, only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        }
    }
}

// Handle Post Deletion
if (isset($_GET['delete_post'])) {
    $post_id = $_GET['delete_post'];
    $sql_check_post = "SELECT user_id FROM problems WHERE id = ?";
    $stmt_check_post = $conn->prepare($sql_check_post);
    $stmt_check_post->bind_param("i", $post_id);
    $stmt_check_post->execute();
    $result_check_post = $stmt_check_post->get_result();

    if ($result_check_post->num_rows > 0) {
        $post = $result_check_post->fetch_assoc();
        if ($post['user_id'] == $user_id) {
            $sql_delete = "DELETE FROM problems WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $post_id);
            if ($stmt_delete->execute()) {
                $success_message = "Post deleted successfully!";
            } else {
                $error_message = "Error deleting post.";
            }
        } else {
            $error_message = "You do not have permission to delete this post.";
        }
    } else {
        $error_message = "Post not found.";
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/modern-theme.css">
    <title>MUFFEIA - Profile</title>
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
                <span class="theme-label">Dark Mode</span>
            </div>
            
            <div class="nav-items">
                <a href="../index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="profile.php" class="nav-item active">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="message.php" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge">3</span>
                </a>
                <a href="notifications.php" class="nav-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
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
                    <h1>Your Profile</h1>
                    <div class="user-actions">
                        <button class="icon-btn search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="notifications.php" class="icon-btn notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-dot"></span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content">
                <!-- Success/Error Messages -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="profile-header-card">
                    <div class="profile-background"></div>
                    <div class="profile-content">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar">
                                <?php if (!empty($user['profile_pic'])): ?>
                                    <img src="../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" class="avatar-image">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="avatar-overlay">
                                    <label for="profile-pic-upload" class="avatar-upload-btn">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                            </div>
                            <form action="profile.php" method="post" enctype="multipart/form-data" class="avatar-upload-form">
                                <input type="file" name="profile_pic" id="profile-pic-upload" accept="image/*" hidden>
                                <button type="submit" class="btn-primary" id="upload-submit" style="display: none;">Update Picture</button>
                            </form>
                        </div>
                        
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                            <p class="profile-email">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            <p class="profile-join-date">
                                <i class="fas fa-calendar-alt"></i>
                                Member since <?php echo date("F j, Y", strtotime($user['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon posts">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['post_count'] ?? 0; ?></h3>
                            <p>Problems Posted</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon solutions">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['solution_count'] ?? 0; ?></h3>
                            <p>Solutions Provided</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon likes">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_likes'] ?? 0; ?></h3>
                            <p>Total Likes</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon engagement">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo ($stats['post_count'] ?? 0) + ($stats['solution_count'] ?? 0); ?></h3>
                            <p>Total Engagement</p>
                        </div>
                    </div>
                </div>

                <!-- User Posts Section -->
                <div class="posts-section">
                    <div class="section-header">
                        <h3>
                            <i class="fas fa-file-alt"></i>
                            Your Problems (<?php echo $result_posts->num_rows; ?>)
                        </h3>
                    </div>

                    <?php if ($result_posts->num_rows > 0): ?>
                        <div class="posts-grid">
                            <?php while ($post = $result_posts->fetch_assoc()): ?>
                                <div class="post-card profile-post-card">
                                    <div class="post-header">
                                        <div class="post-meta">
                                            <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                            <span class="post-time">
                                                <i class="far fa-clock"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="post-actions-dropdown">
                                            <button class="post-options">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="../index.php?problem_id=<?php echo $post['id']; ?>" class="dropdown-item">
                                                    <i class="fas fa-eye"></i> View Post
                                                </a>
                                                <a href="edit_post.php?post_id=<?php echo $post['id']; ?>" class="dropdown-item">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="profile.php?delete_post=<?php echo $post['id']; ?>" class="dropdown-item delete" onclick="return confirm('Are you sure you want to delete this post?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="post-content">
                                        <p><?php echo htmlspecialchars($post['description']); ?></p>
                                    </div>

                                    <div class="post-stats">
                                        <div class="stat">
                                            <i class="fas fa-heart"></i>
                                            <span><?php echo $post['like_count'] ?? 0; ?> Likes</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-comments"></i>
                                            <span>View Solutions</span>
                                        </div>
                                    </div>

                                    <div class="post-actions">
                                        <a href="pages/view_problem.php?problem_id=<?php echo $post['id']; ?>" class="btn-view">
                                            <i class="fas fa-comments"></i> View Solutions
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>No problems posted yet</h3>
                            <p>Start sharing your problems to get help from the community!</p>
                            <a href="../index.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Create Your First Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> MUFFEIA @Muffy. All rights reserved.</p>
            <div class="footer-links">
                <a href="privacy.php">Privacy Policy</a>
                <span>•</span>
                <a href="terms.php">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script src="../js/mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function openSidebar() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebar() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            menuToggle.addEventListener('click', openSidebar);
            sidebarClose.addEventListener('click', closeSidebar);
            sidebarOverlay.addEventListener('click', closeSidebar);

            // Profile picture upload
            const profilePicUpload = document.getElementById('profile-pic-upload');
            const uploadSubmit = document.getElementById('upload-submit');
            const avatarImage = document.querySelector('.avatar-image');
            const avatarPlaceholder = document.querySelector('.avatar-placeholder');

            profilePicUpload.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        if (avatarImage) {
                            avatarImage.src = e.target.result;
                        } else if (avatarPlaceholder) {
                            // Replace placeholder with actual image
                            const newImg = document.createElement('img');
                            newImg.src = e.target.result;
                            newImg.alt = 'Profile Picture';
                            newImg.className = 'avatar-image';
                            avatarPlaceholder.parentNode.replaceChild(newImg, avatarPlaceholder);
                        }
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                    uploadSubmit.style.display = 'inline-block';
                }
            });

            // Dropdown menu functionality
            document.querySelectorAll('.post-options').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = this.nextElementSibling;
                    const isVisible = dropdown.style.display === 'block';
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                    
                    // Toggle current dropdown
                    dropdown.style.display = isVisible ? 'none' : 'block';
                });
            });

            // Close dropdowns when clicking elsewhere
            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            });

            // Confirm delete actions
            document.querySelectorAll('.delete').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>