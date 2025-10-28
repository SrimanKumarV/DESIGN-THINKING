<?php
// admin_login.php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo "Please provide username and password. <a href='admin_login.html'>Back</a>";
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "❌ Admin not found. <a href='admin_login.html'>Back</a>";
    exit;
}
$stmt->bind_result($id, $uname, $hash);
$stmt->fetch();

if (!password_verify($password, $hash)) {
    echo "❌ Invalid password. <a href='admin_login.html'>Back</a>";
    exit;
}

// success
session_regenerate_id(true);
$_SESSION['admin_id'] = $id;
$_SESSION['admin_username'] = $uname;
$_SESSION['role'] = 'admin';
$stmt->close();
$conn->close();

header('Location: admin_dashboard.php');
exit;
?>