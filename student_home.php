<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.html');
    exit;
}
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Check for success message
$success_msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'request_submitted') {
    $request_id = $_GET['request_id'] ?? '';
    $success_msg = 'Your certificate request has been submitted successfully!' . ($request_id ? " (Request ID: #$request_id)" : '');
}

// Check for notifications
$notifications = [];
$notification_count = 0;

// Fetch unread notifications for this student
$notification_stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE student_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
if ($notification_stmt) {
    $notification_stmt->bind_param("i", $student_id);
    $notification_stmt->execute();
    $notifications_result = $notification_stmt->get_result();
    $notification_count = $notifications_result->num_rows;
    
    // Store notifications in array
    while ($notification = $notifications_result->fetch_assoc()) {
        $notifications[] = $notification;
    }
    
    // Mark notifications as read after displaying
    if ($notification_count > 0) {
        $mark_read_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0");
        if ($mark_read_stmt) {
            $mark_read_stmt->bind_param("i", $student_id);
            $mark_read_stmt->execute();
            $mark_read_stmt->close();
        }
    }
    $notification_stmt->close();
}

// fetch stats FOR THIS STUDENT ONLY
$pending = $approved = $rejected = $total = 0;
$stmt = $conn->prepare("SELECT 
    COALESCE(SUM(status = 'Pending'), 0) AS pending,
    COALESCE(SUM(status = 'Approved'), 0) AS approved,
    COALESCE(SUM(status = 'Rejected'), 0) AS rejected,
    COALESCE(COUNT(*), 0) AS total
    FROM certificate_requests WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($pending, $approved, $rejected, $total);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Student Dashboard - CertGenius</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="DT.html"><i class="fas fa-certificate me-2"></i>CertGenius</a>
      <div class="d-flex">
        <a class="btn btn-outline-light btn-sm me-2" href="DT.html"><i class="fas fa-home me-1"></i> Home</a>
        <div class="dropdown">
          <a class="nav-link dropdown-toggle text-white" id="userDropdown" data-bs-toggle="dropdown" href="#" role="button">
            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($student_name); ?>
            <?php if ($notification_count > 0): ?>
              <span class="badge bg-danger badge-sm"><?php echo $notification_count; ?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="student_requests.php"><i class="fas fa-history me-2"></i>Request History</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- Success Alert -->
  <?php if ($success_msg): ?>
  <div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
  <?php endif; ?>

  <!-- Notifications -->
  <?php if ($notification_count > 0): ?>
  <div class="container mt-3">
    <?php foreach ($notifications as $notification): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <i class="fas fa-bell me-2"></i>
      <strong>Update:</strong> <?php echo htmlspecialchars($notification['message']); ?>
      <small class="text-muted"> - <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></small>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Layout -->
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
        <div class="position-sticky pt-3">
          <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="#requestForm"><i class="fas fa-file-certificate"></i> Request Certificate</a></li>
            <li class="nav-item"><a class="nav-link" href="student_requests.php"><i class="fas fa-history"></i> Request History</a></li>
          </ul>
        </div>
      </div>

      <!-- Main -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
          <h1 class="h2">Student Dashboard</h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-sm btn-outline-secondary me-2" onclick="location.reload();"><i class="fas fa-sync-alt"></i> Refresh</button>
            <a href="#requestForm" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> New Certificate</a>
          </div>
        </div>

        <!-- Stats - SHOWING ONLY THIS STUDENT'S REQUESTS -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card dashboard-card">
              <div class="card-body stats-card">
                <div class="stats-number"><?php echo (int)$total; ?></div>
                <div class="stats-label">Total Requests</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card dashboard-card">
              <div class="card-body stats-card">
                <div class="stats-number text-warning"><?php echo (int)$pending; ?></div>
                <div class="stats-label">Pending Requests</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card dashboard-card">
              <div class="card-body stats-card">
                <div class="stats-number text-success"><?php echo (int)$approved; ?></div>
                <div class="stats-label">Approved Requests</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card dashboard-card">
              <div class="card-body stats-card">
                <div class="stats-number text-danger"><?php echo (int)$rejected; ?></div>
                <div class="stats-label">Rejected Requests</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Recent Activity - SHOWING ONLY THIS STUDENT'S ACTIVITY -->
          <div class="col-md-6">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span>My Recent Activity</span>
                <a href="student_requests.php" class="text-white">View All</a>
              </div>
              <div class="card-body">
                <div class="list-group list-group-flush">
                  <?php
                  // show last 5 requests FOR THIS STUDENT ONLY
                  $stmt = $conn->prepare("SELECT id, certificate_type, status, request_date FROM certificate_requests WHERE student_id = ? ORDER BY request_date DESC LIMIT 5");
                  $stmt->bind_param("i", $student_id);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  if ($res->num_rows === 0) {
                    echo "<div class='text-center py-3'>
                            <i class='fas fa-inbox fa-2x text-muted mb-2'></i>
                            <p class='text-muted mb-0'>No requests yet</p>
                            <small class='text-muted'>Submit your first certificate request!</small>
                          </div>";
                  } else {
                    while ($r = $res->fetch_assoc()) {
                      $badgeClass = $r['status'] === 'Approved' ? 'bg-success' : ($r['status'] === 'Rejected' ? 'bg-danger' : 'bg-warning');
                      $statusIcon = $r['status'] === 'Approved' ? 'fa-check' : ($r['status'] === 'Rejected' ? 'fa-times' : 'fa-clock');
                      echo "<div class='list-group-item d-flex justify-content-between align-items-center'>
                              <div>
                                <h6 class='mb-1'>" . htmlspecialchars($r['certificate_type']) . "</h6>
                                <small class='text-muted'>Request #" . $r['id'] . " • " . date('d M Y', strtotime($r['request_date'])) . "</small>
                              </div>
                              <span class='badge $badgeClass rounded-pill'>
                                <i class='fas $statusIcon me-1'></i>".htmlspecialchars($r['status'])."
                              </span>
                            </div>";
                    }
                  }
                  $stmt->close();
                  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Certificate Request Form -->
          <div class="col-md-6">
            <div class="card" id="requestForm">
              <div class="card-header">Request New Certificate</div>
              <div class="card-body">
                <form id="certificateRequestForm" action="student_request.php" method="POST">
                  <div class="mb-3">
                    <label for="certificateType" class="form-label">Certificate Type *</label>
                    <select class="form-select" name="certificate_type" id="certificateType" required>
                      <option value="" selected disabled>Select certificate type</option>
                      <option value="Enrollment Certificate">Enrollment Certificate</option>
                      <option value="Course Completion Certificate">Course Completion Certificate</option>
                      <option value="Degree Certificate">Degree Certificate</option>
                      <option value="Academic Transcript">Academic Transcript</option>
                      <option value="Bonafide Certificate">Bonafide Certificate</option>
                      <option value="Transfer Certificate">Transfer Certificate</option>
                      <option value="Character Certificate">Character Certificate</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="purpose" class="form-label">Purpose *</label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="3" placeholder="Explain the purpose of this certificate..." required></textarea>
                    <div class="form-text">Please provide detailed information about why you need this certificate.</div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Delivery Method *</label>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="delivery_method" id="digital" value="digital" checked>
                      <label class="form-check-label" for="digital">
                        <i class="fas fa-file-pdf me-1"></i>Digital Copy (PDF)
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="delivery_method" id="physical" value="physical">
                      <label class="form-check-label" for="physical">
                        <i class="fas fa-box me-1"></i>Physical Copy
                      </label>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label for="urgency" class="form-label">Urgency *</label>
                    <select class="form-select" id="urgency" name="urgency" required>
                      <option value="normal" selected>Normal (5-7 business days)</option>
                      <option value="urgent">Urgent (2-3 business days)</option>
                      <option value="express">Express (24 hours) - Additional fee applies</option>
                    </select>
                  </div>
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> All requests start as "Pending" and will be reviewed by the admin. You'll be notified once your request is processed.
                  </div>
                  <div class="d-grid">
                    <button type="submit" class="btn btn-primary w-100">
                      <i class="fas fa-paper-plane me-2"></i>Submit Request
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-refresh the page every 60 seconds to check for updates
    setTimeout(function() {
      window.location.reload();
    }, 60000);
  </script>
</body>
</html>
<?php
$conn->close();
?>