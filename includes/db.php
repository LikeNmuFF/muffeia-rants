<?php
$servername = "localhost";
$username = "root"; // XAMPP default username
$password = ""; // XAMPP default password is empty
$dbname = "muffeia"; // Make sure this matches your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
