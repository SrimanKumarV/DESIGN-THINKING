<?php
// logout.php
session_start();
$_SESSION = [];
session_destroy();
header('Location: student_login.html');
exit;
?>