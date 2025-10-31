<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    $user_id = $_SESSION['user_id']; // Assume the user is logged in

    // Insert the problem into the database
    $sql = "INSERT INTO problems (title, description, anonymous, user_id, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $title, $description, $anonymous, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Get the last inserted problem ID
        $problem_id = $stmt->insert_id;

        // Create the target URL
        $target_url = "view_problem.php?problem_id=" . $problem_id;

        // Add a notification for the user
        $notification_sql = "INSERT INTO notifications (user_id, message, target_url, created_at) VALUES (?, ?, ?, NOW())";
        $notification_stmt = $conn->prepare($notification_sql);
        $notification_message = "Your problem has been posted successfully!";
        $notification_stmt->bind_param("iss", $user_id, $notification_message, $target_url);
        $notification_stmt->execute();

        // Redirect to the home page or show a success message
        header("Location: index.php?success=1");
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
