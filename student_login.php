<?php
// student_login.php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student_login.html');
    exit;
}

$regno = trim($_POST['regno'] ?? '');
$password = $_POST['password'] ?? '';

if ($regno === '' || $password === '') {
    echo "Please provide both register number and password. <a href='student_login.html'>Back</a>";
    exit;
}

$stmt = $conn->prepare("SELECT id, name, password FROM students WHERE regno = ?");
$stmt->bind_param("s", $regno);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    echo "❌ No student found with that register number. <a href='student_login.html'>Back</a>";
    exit;
}
$stmt->bind_result($id, $name, $hash);
$stmt->fetch();

if (!password_verify($password, $hash)) {
    echo "❌ Invalid password. <a href='student_login.html'>Back</a>";
    exit;
}

// success
session_regenerate_id(true);
$_SESSION['student_id'] = $id;
$_SESSION['student_name'] = $name;
$_SESSION['role'] = 'student';
$stmt->close();
$conn->close();

header('Location: student_home.php');
exit;
?>