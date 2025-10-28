<?php
// create_student.php - Handle student account creation
session_start();
require_once 'db_config.php';

// Check if admin is logged in (REQUIRED SECURITY)
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_student.html');
    exit;
}

// Get form data
$name = trim($_POST['name'] ?? '');
$regno = trim($_POST['regno'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$department = trim($_POST['department'] ?? '');

// Validation
$errors = [];

if (empty($name)) $errors[] = "Name is required";
if (empty($regno)) $errors[] = "Register number is required";
if (empty($email)) $errors[] = "Email is required";
if (empty($password)) $errors[] = "Password is required";
if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";

// Check if regno or email already exists
$check_stmt = $conn->prepare("SELECT id FROM students WHERE regno = ? OR email = ?");
$check_stmt->bind_param("ss", $regno, $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $errors[] = "Register number or email already exists";
}
$check_stmt->close();

// If errors, show them
if (!empty($errors)) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error - Create Student</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    </head>
    <body>
        <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
            <div class='container'>
                <a class='navbar-brand' href='DT.html'><i class='fas fa-certificate me-2'></i>CertGenius Admin</a>
                <div class='d-flex'>
                    <a class='btn btn-outline-light btn-sm me-2' href='admin_dashboard.php'><i class='fas fa-arrow-left me-1'></i> Back to Dashboard</a>
                </div>
            </div>
        </nav>
        <div class='container mt-5'>
            <div class='alert alert-danger'>
                <h4><i class='fas fa-exclamation-triangle me-2'></i>Error creating student account:</h4>
                <ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "      </ul>
                <div class='mt-3'>
                    <a href='create_student.html' class='btn btn-secondary me-2'><i class='fas fa-arrow-left me-1'></i>Go Back</a>
                    <a href='admin_dashboard.php' class='btn btn-outline-primary'><i class='fas fa-cogs me-1'></i>Admin Dashboard</a>
                </div>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert student
$stmt = $conn->prepare("INSERT INTO students (name, regno, email, password, department) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $regno, $email, $hashed_password, $department);

if ($stmt->execute()) {
    // Success
    $student_id = $stmt->insert_id;
    $stmt->close();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Success - Student Created</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    </head>
    <body>
        <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
            <div class='container'>
                <a class='navbar-brand' href='DT.html'><i class='fas fa-certificate me-2'></i>CertGenius Admin</a>
                <div class='d-flex'>
                    <a class='btn btn-outline-light btn-sm me-2' href='admin_dashboard.php'><i class='fas fa-cogs me-1'></i> Admin Dashboard</a>
                </div>
            </div>
        </nav>
        <div class='container mt-5'>
            <div class='alert alert-success'>
                <h4><i class='fas fa-check-circle me-2'></i>Student Account Created Successfully!</h4>
                <p><strong>Student Details:</strong></p>
                <ul>
                    <li><strong>Name:</strong> " . htmlspecialchars($name) . "</li>
                    <li><strong>Register Number:</strong> " . htmlspecialchars($regno) . "</li>
                    <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                    <li><strong>Department:</strong> " . htmlspecialchars($department) . "</li>
                </ul>
                <p class='mb-0'>Student can now login using their register number and password.</p>
            </div>
            <div class='d-grid gap-2 d-md-flex justify-content-md-center'>
                <a href='create_student.html' class='btn btn-primary me-md-2'>
                    <i class='fas fa-user-plus me-1'></i>Create Another Student
                </a>
                <a href='admin_dashboard.php' class='btn btn-outline-primary me-md-2'>
                    <i class='fas fa-cogs me-1'></i>Admin Dashboard
                </a>
                <a href='DT.html' class='btn btn-outline-secondary'>
                    <i class='fas fa-home me-1'></i>Back to Home
                </a>
            </div>
        </div>
    </body>
    </html>";
} else {
    // Error
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error - Create Student</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    </head>
    <body>
        <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
            <div class='container'>
                <a class='navbar-brand' href='DT.html'><i class='fas fa-certificate me-2'></i>CertGenius Admin</a>
                <div class='d-flex'>
                    <a class='btn btn-outline-light btn-sm me-2' href='admin_dashboard.php'><i class='fas fa-arrow-left me-1'></i> Back to Dashboard</a>
                </div>
            </div>
        </nav>
        <div class='container mt-5'>
            <div class='alert alert-danger'>
                <h4><i class='fas fa-exclamation-triangle me-2'></i>Database Error:</h4>
                <p>" . htmlspecialchars($stmt->error) . "</p>
                <div class='mt-3'>
                    <a href='create_student.html' class='btn btn-secondary me-2'><i class='fas fa-arrow-left me-1'></i>Go Back</a>
                    <a href='admin_dashboard.php' class='btn btn-outline-primary'><i class='fas fa-cogs me-1'></i>Admin Dashboard</a>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

$stmt->close();
$conn->close();
?>