<?php
// create_admin.php - run once to create an admin user via password_hash
require_once 'db_config.php';

$username = 'admin';
$email = 'admin@example.com';
$password_plain = 'Admin@123'; // change before running if you want
$hashed = password_hash($password_plain, PASSWORD_DEFAULT);

// Prevent duplicate
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Admin user already exists. Remove this file after use.";
    exit;
}
$stmt->close();

$stmt = $conn->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashed);
if ($stmt->execute()) {
    echo "Admin created successfully. Username: <strong>$username</strong> Password: <strong>$password_plain</strong><br>";
    echo "Please delete create_admin.php now (for security).";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>