<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('error');
}

$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo 'success';
} else {
    echo 'error';
}
?>