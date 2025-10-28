<?php
// student_request.php - handles certificate request submission
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_home.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Get student details from database
$student_stmt = $conn->prepare("SELECT regno, email, department FROM students WHERE id = ?");
if (!$student_stmt) {
    error_log("Student prepare failed: " . $conn->error);
    echo "<script>alert('Database error. Please try again.'); window.history.back();</script>";
    exit;
}

$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student_stmt->bind_result($student_regno, $student_email, $student_department);
$student_stmt->fetch();
$student_stmt->close();

$certificate_type = trim($_POST['certificate_type'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');
$delivery_method = $_POST['delivery_method'] ?? 'digital';
$urgency = $_POST['urgency'] ?? 'normal';

if ($certificate_type === '' || $purpose === '') {
    echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
    exit;
}

// Insert into certificate_requests table with all student info
$stmt = $conn->prepare("INSERT INTO certificate_requests (student_id, student_name, student_regno, student_email, student_department, certificate_type, purpose, delivery_method, urgency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");

if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    echo "<script>alert('Database error. Please try again.'); window.history.back();</script>";
    exit;
}

$stmt->bind_param("issssssss", $student_id, $student_name, $student_regno, $student_email, $student_department, $certificate_type, $purpose, $delivery_method, $urgency);

if ($stmt->execute()) {
    // Get the inserted request ID
    $request_id = $stmt->insert_id;
    $stmt->close();
    
    // Success: redirect with success message
    header("Location: student_home.php?msg=request_submitted&request_id=$request_id");
    exit;
} else {
    error_log("Database Error: " . $stmt->error);
    $stmt->close();
    echo "<script>alert('Error submitting request. Please try again.'); window.history.back();</script>";
}

$conn->close();
?>