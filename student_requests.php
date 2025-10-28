<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.html');
    exit;
}
$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// fetch stats FOR THIS STUDENT ONLY
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

// fetch all requests FOR THIS STUDENT ONLY
$stmt = $conn->prepare("SELECT id, certificate_type, status, request_date, purpose, delivery_method, urgency FROM certificate_requests WHERE student_id = ? ORDER BY request_date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Requests - CertGenius</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="DT.html"><i class="fas fa-certificate me-2"></i>CertGenius</a>
      <div class="d-flex">
        <a class="btn btn-outline-light btn-sm me-2" href="student_home.php"><i class="fas fa-home me-1"></i> Dashboard</a>
        <a class="btn btn-outline-light btn-sm me-2" href="DT.html"><i class="fas fa-home me-1"></i> Home</a>
        <span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($student_name); ?></span>
        <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3>My Certificate Requests</h3>
      <div>
        <a href="student_home.php" class="btn btn-primary me-2">
          <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
        <a href="#requestForm" class="btn btn-success">
          <i class="fas fa-plus me-1"></i>New Request
        </a>
      </div>
    </div>

    <!-- Stats Cards - SHOWING ONLY THIS STUDENT'S REQUESTS -->
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
            <div class="stats-number text-warning"><?php echo (int)$pending; ?></div>
            <div class="stats-label">Pending Requests</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body stats-card">
            <div class="stats-number text-success"><?php echo (int)$approved; ?></div>
            <div class="stats-label">Approved Requests</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body stats-card">
            <div class="stats-number text-danger"><?php echo (int)$rejected; ?></div>
            <div class="stats-label">Rejected Requests</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Request History</span>
        <span class="badge bg-primary"><?php echo (int)$total; ?> Total Requests</span>
      </div>
      <div class="card-body">
        <?php if ($res->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="table-dark">
              <tr>
                <th>Request ID</th>
                <th>Certificate Type</th>
                <th>Purpose</th>
                <th>Delivery</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Request Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $res->fetch_assoc()) {
                $badge = $r['status'] === 'Approved' ? 'success' : ($r['status'] === 'Rejected' ? 'danger' : 'warning');
                $status_icon = $r['status'] === 'Approved' ? 'fa-check' : ($r['status'] === 'Rejected' ? 'fa-times' : 'fa-clock');
              ?>
              <tr>
                <td><strong>#<?php echo $r['id']; ?></strong></td>
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
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No Certificate Requests Found</h5>
            <p class="text-muted">You haven't submitted any certificate requests yet.</p>
            <a href="#requestForm" class="btn btn-primary">
              <i class="fas fa-plus me-1"></i>Submit Your First Request
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Request Form -->
    <div class="card mt-4" id="requestForm">
      <div class="card-header bg-success text-white">
        <i class="fas fa-plus me-2"></i>New Certificate Request
      </div>
      <div class="card-body">
        <form id="certificateRequestForm" action="student_request.php" method="POST">
          <div class="row">
            <div class="col-md-6">
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
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="urgency" class="form-label">Urgency *</label>
                <select class="form-select" id="urgency" name="urgency" required>
                  <option value="normal" selected>Normal (5-7 business days)</option>
                  <option value="urgent">Urgent (2-3 business days)</option>
                  <option value="express">Express (24 hours) - Additional fee applies</option>
                </select>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label for="purpose" class="form-label">Purpose *</label>
            <textarea class="form-control" id="purpose" name="purpose" rows="3" placeholder="Explain the purpose of this certificate..." required></textarea>
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
          <div class="d-grid">
            <button type="submit" class="btn btn-success w-100">
              <i class="fas fa-paper-plane me-2"></i>Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>