<?php
// reject_request.php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.html');
    exit;
}

$request_id = intval($_GET['id'] ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);

if ($request_id <= 0 || $student_id <= 0) {
    header('Location: admin_dashboard.php');
    exit;
}

// Get request details from certificate_requests table
$stmt = $conn->prepare("SELECT certificate_type, student_name FROM certificate_requests WHERE id = ? AND student_id = ?");
$stmt->bind_param("ii", $request_id, $student_id);
$stmt->execute();
$stmt->bind_result($certificate_type, $student_name);
$stmt->fetch();
$stmt->close();

// Update status to Rejected in certificate_requests table
$stmt = $conn->prepare("UPDATE certificate_requests SET status = 'Rejected' WHERE id = ? AND student_id = ?");
$stmt->bind_param("ii", $request_id, $student_id);

if ($stmt->execute()) {
    // Create notification for student
    $notification_msg = "Your $certificate_type request (#$request_id) has been REJECTED. Please contact administration for more details.";
    
    // Insert notification
    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, is_read) VALUES (?, ?, 0)");
    if ($notif_stmt) {
        $notif_stmt->bind_param("is", $student_id, $notification_msg);
        $notif_stmt->execute();
        $notif_stmt->close();
    }
    
    // Success message
    $_SESSION['success_msg'] = "Request #$request_id from $student_name has been rejected successfully! Student has been notified.";
} else {
    $_SESSION['error_msg'] = "Error rejecting request: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to pending requests
header('Location: admin_dashboard.php?filter=pending');
exit;
?>