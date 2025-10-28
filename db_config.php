<?php
// db_config.php
// Update these with your MySQL credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_certificates";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// set charset
$conn->set_charset("utf8mb4");
?>