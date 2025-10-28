<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.html');
    exit;
}
$admin_username = $_SESSION['admin_username'];

// Check for success/error messages
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
// Clear messages after displaying
unset($_SESSION['success_msg']);
unset($_SESSION['error_msg']);

// Get filter if any
$filter = $_GET['filter'] ?? '';

// Build query based on filter - DEFAULT SHOW ONLY PENDING REQUESTS
if (empty($filter)) {
    $filter = 'pending'; // Default to show pending requests
}

$where_clause = "";
if ($filter === 'pending') {
    $where_clause = "WHERE status = 'Pending'";
} elseif ($filter === 'approved') {
    $where_clause = "WHERE status = 'Approved'";
} elseif ($filter === 'rejected') {
    $where_clause = "WHERE status = 'Rejected'";
} elseif ($filter === 'all') {
    $where_clause = ""; // Show all requests
}

// fetch counts from certificate_requests table
$stmt = $conn->prepare("SELECT 
    COALESCE(SUM(status = 'Pending'), 0) AS pending,
    COALESCE(SUM(status = 'Approved'), 0) AS approved,
    COALESCE(SUM(status = 'Rejected'), 0) AS rejected,
    COALESCE(COUNT(*), 0) AS total
    FROM certificate_requests");
$stmt->execute();
$stmt->bind_result($pending, $approved, $rejected, $total);
$stmt->fetch();
$stmt->close();

// fetch all requests from certificate_requests table
$q = "SELECT id, student_id, student_name, student_regno, student_email, student_department, certificate_type, status, request_date, urgency, delivery_method, purpose
      FROM certificate_requests 
      $where_clause
      ORDER BY request_date DESC";
$res = $conn->query($q);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard - CertGenius</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="DT.html"><i class="fas fa-certificate me-2"></i>CertGenius Admin</a>
      <div class="d-flex">
        <span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
        <a class="btn btn-outline-light btn-sm me-2" href="DT.html"><i class="fas fa-home me-1"></i> Home</a>
        <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <!-- Success/Error Messages -->
    <?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Header with Create Student Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3>Admin Dashboard</h3>
      <div>
        <a href="create_student.html" class="btn btn-warning me-2">
          <i class="fas fa-user-plus me-1"></i>Create Student
        </a>
        <a href="DT.html" class="btn btn-outline-primary">
          <i class="fas fa-home me-1"></i> Home
        </a>
      </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card">
          <div class="card-body stats-card">
            <div class="stats-number"><?php echo (int)$total; ?></div>
            <div class="stats-label">Total Requests</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body stats-card">
            <div class="stats-number"><?php echo (int)$pending; ?></div>
            <div class="stats-label">Pending Requests</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body stats-card">
            <div class="stats-number"><?php echo (int)$approved; ?></div>
            <div class="stats-label">Approved Requests</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body stats-card">
            <div class="stats-number"><?php echo (int)$rejected; ?></div>
            <div class="stats-label">Rejected Requests</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Certificate Requests Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>
          Certificate Requests Management 
          <?php if ($filter === 'pending'): ?>
            - <span class="text-warning">Pending Requests (Action Required)</span>
          <?php elseif ($filter === 'approved'): ?>
            - <span class="text-success">Approved Requests</span>
          <?php elseif ($filter === 'rejected'): ?>
            - <span class="text-danger">Rejected Requests</span>
          <?php else: ?>
            - <span class="text-info">All Requests</span>
          <?php endif; ?>
        </span>
        <div>
          <a href="admin_dashboard.php?filter=pending" class="btn btn-sm <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?> me-1">
            <i class="fas fa-clock me-1"></i>Pending (<?php echo (int)$pending; ?>)
          </a>
          <a href="admin_dashboard.php?filter=approved" class="btn btn-sm <?php echo $filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?> me-1">
            <i class="fas fa-check me-1"></i>Approved (<?php echo (int)$approved; ?>)
          </a>
          <a href="admin_dashboard.php?filter=rejected" class="btn btn-sm <?php echo $filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?> me-1">
            <i class="fas fa-times me-1"></i>Rejected (<?php echo (int)$rejected; ?>)
          </a>
          <a href="admin_dashboard.php?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-info' : 'btn-outline-info'; ?>">
            <i class="fas fa-list me-1"></i>All Requests
          </a>
        </div>
      </div>
      <div class="card-body">
        <?php if ($res && $res->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="table-dark">
              <tr>
                <th>Request ID</th>
                <th>Student Details</th>
                <th>Certificate Type</th>
                <th>Purpose</th>
                <th>Delivery</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Request Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $res->fetch_assoc()) {
                $badge = $r['status'] === 'Approved' ? 'success' : ($r['status'] === 'Rejected' ? 'danger' : 'warning');
                $status_icon = $r['status'] === 'Approved' ? 'fa-check' : ($r['status'] === 'Rejected' ? 'fa-times' : 'fa-clock');
              ?>
              <tr>
                <td><strong>#<?php echo $r['id']; ?></strong></td>
                <td>
                  <div class="student-info">
                    <strong><?php echo htmlspecialchars($r['student_name']); ?></strong><br>
                    <small class="text-muted">
                      <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($r['student_regno']); ?><br>
                      <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($r['student_email']); ?><br>
                      <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($r['student_department']); ?>
                    </small>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($r['certificate_type']); ?></td>
                <td>
                  <span class="d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($r['purpose']); ?>">
                    <?php echo htmlspecialchars($r['purpose']); ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-info">
                    <i class="fas fa-<?php echo $r['delivery_method'] === 'digital' ? 'file-pdf' : 'box'; ?> me-1"></i>
                    <?php echo htmlspecialchars(ucfirst($r['delivery_method'])); ?>
                  </span>
                </td>
                <td>
                  <?php 
                  $urgency_badge = $r['urgency'] === 'urgent' ? 'danger' : ($r['urgency'] === 'express' ? 'warning' : 'secondary');
                  $urgency_icon = $r['urgency'] === 'urgent' ? 'fa-fire' : ($r['urgency'] === 'express' ? 'fa-bolt' : 'fa-clock');
                  ?>
                  <span class="badge bg-<?php echo $urgency_badge; ?>">
                    <i class="fas <?php echo $urgency_icon; ?> me-1"></i>
                    <?php echo htmlspecialchars(ucfirst($r['urgency'])); ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?php echo $badge; ?>">
                    <i class="fas <?php echo $status_icon; ?> me-1"></i>
                    <?php echo htmlspecialchars($r['status']); ?>
                  </span>
                </td>
                <td>
                  <small>
                    <?php echo date('d M Y H:i', strtotime($r['request_date'])); ?>
                  </small>
                </td>
                <td>
                  <?php if ($r['status'] === 'Pending') { ?>
                    <div class="btn-group btn-group-sm">
                      <a class="btn btn-success" href="approve_request.php?id=<?php echo $r['id']; ?>&student_id=<?php echo $r['student_id']; ?>" onclick="return confirm('Approve request #<?php echo $r['id']; ?> from <?php echo htmlspecialchars($r['student_name']); ?>?')" title="Approve">
                        <i class="fas fa-check"></i> Approve
                      </a>
                      <a class="btn btn-danger" href="reject_request.php?id=<?php echo $r['id']; ?>&student_id=<?php echo $r['student_id']; ?>" onclick="return confirm('Reject request #<?php echo $r['id']; ?> from <?php echo htmlspecialchars($r['student_name']); ?>?')" title="Reject">
                        <i class="fas fa-times"></i> Reject
                      </a>
                    </div>
                  <?php } else { 
                    echo '<span class="text-muted"><i class="fas fa-check-circle me-1"></i>Processed</span>';
                  } ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No Certificate Requests Found</h5>
            <p class="text-muted">
              <?php 
              if ($filter === 'pending') {
                echo "There are no pending certificate requests. All requests have been processed.";
              } elseif ($filter === 'approved') {
                echo "No approved certificate requests found.";
              } elseif ($filter === 'rejected') {
                echo "No rejected certificate requests found.";
              } else {
                echo "No certificate requests have been submitted yet.";
              }
              ?>
            </p>
            <?php if ($filter !== 'pending'): ?>
              <a href="admin_dashboard.php?filter=pending" class="btn btn-primary">
                <i class="fas fa-clock me-1"></i>View Pending Requests
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>