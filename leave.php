<?php
require_once 'includes/db.php';
$page_title = 'Leave Management';
$db  = getDB();
$msg = '';

// ── Handle apply leave ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        $emp_id  = trim($_POST['emp_id'] ?? '');
        $type    = $_POST['leave_type'] ?? '';
        $from    = $_POST['from_date'] ?? '';
        $to      = $_POST['to_date'] ?? '';
        $reason  = trim($_POST['reason'] ?? '');

        if (!$emp_id || !$type || !$from || !$to) {
            $msg = 'error:All fields except reason are required.';
        } elseif ($to < $from) {
            $msg = 'error:End date must be after start date.';
        } else {
            $days = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
            $today = date('Y-m-d');
            $stmt = $db->prepare("INSERT INTO leaves (emp_id,leave_type,from_date,to_date,days,reason,applied_on) VALUES(?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssiSs', $emp_id, $type, $from, $to, $days, $reason, $today);
            if ($stmt->execute()) $msg = 'success:Leave application submitted successfully.';
            else $msg = 'error:Failed to submit. Try again.';
            $stmt->close();
        }
    } elseif (in_array($action, ['approve','reject'])) {
        $id     = (int)($_POST['leave_id'] ?? 0);
        $status = $action === 'approve' ? 'Approved' : 'Rejected';
        $by     = 'Admin';
        $stmt = $db->prepare("UPDATE leaves SET status=?, approved_by=? WHERE id=?");
        $stmt->bind_param('ssi', $status, $by, $id);
        $stmt->execute();
        $msg = 'success:Leave ' . strtolower($status) . '.';
        $stmt->close();
    } elseif ($action === 'delete') {
        $id = (int)($_POST['leave_id'] ?? 0);
        $db->query("DELETE FROM leaves WHERE id=$id");
        $msg = 'success:Leave application deleted.';
    }
}

// ── Filters ───────────────────────────────────────────────────────────────
$f_status = $_GET['status'] ?? '';
$f_emp    = $_GET['emp'] ?? '';
$f_month  = $_GET['month'] ?? '';

$where = "WHERE 1=1";
if ($f_status) $where .= " AND l.status='" . $db->real_escape_string($f_status) . "'";
if ($f_emp)    $where .= " AND l.emp_id='" . $db->real_escape_string($f_emp) . "'";
if ($f_month)  $where .= " AND DATE_FORMAT(l.from_date,'%Y-%m')='" . $db->real_escape_string($f_month) . "'";

$leaves = $db->query("
    SELECT l.*, e.name, e.department
    FROM leaves l
    JOIN employees e ON e.emp_id=l.emp_id
    $where
    ORDER BY l.created_at DESC
");

$employees = $db->query("SELECT emp_id, name FROM employees WHERE status='Active' ORDER BY name");
[$msg_type, $msg_text] = $msg ? explode(':', $msg, 2) : ['',''];
require_once 'includes/header.php';
?>

<?php if($msg_text): ?>
<div class="alert alert-<?= $msg_type==='success'?'success':'danger' ?>"><?= htmlspecialchars($msg_text) ?></div>
<?php endif; ?>

<div class="page-head">
  <h2>Leave Management</h2>
  <button class="btn btn-primary" onclick="openModal('leaveModal')">
    <i class="ti ti-plus"></i> Apply Leave
  </button>
</div>

<!-- Filters -->
<div class="card">
  <div class="card-body" style="padding:.75rem 1.25rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label>Status</label>
        <select name="status" style="max-width:160px">
          <option value="">All</option>
          <?php foreach(['Pending','Approved','Rejected'] as $s): ?>
            <option <?= $f_status===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>Employee</label>
        <select name="emp" style="max-width:220px">
          <option value="">All Employees</option>
          <?php $employees->data_seek(0); while($e=$employees->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($e['emp_id']) ?>" <?= $f_emp===$e['emp_id']?'selected':'' ?>>
              <?= htmlspecialchars($e['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>Month</label>
        <input type="month" name="month" value="<?= $f_month ?>" style="max-width:160px">
      </div>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="leave.php" class="btn">Reset</a>
    </form>
  </div>
</div>

<div class="card" style="padding:0">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Employee</th><th>Department</th><th>Leave Type</th>
          <th>From</th><th>To</th><th>Days</th><th>Reason</th>
          <th>Applied On</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$leaves->num_rows): ?>
          <tr><td colspan="11"><div class="empty-state"><i class="ti ti-calendar-off"></i><p>No leave applications found</p></div></td></tr>
        <?php else: $i=1; while($l = $leaves->fetch_assoc()):
          $cls = strtolower($l['status']); ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $i++ ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="avatar"><?= strtoupper(substr($l['name'],0,1)) ?><?= strtoupper(substr(strrchr($l['name'],' '),1,1)) ?></div>
                <div>
                  <div><?= htmlspecialchars($l['name']) ?></div>
                  <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($l['emp_id']) ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($l['department']) ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($l['leave_type']) ?></td>
            <td style="white-space:nowrap"><?= date('d M Y', strtotime($l['from_date'])) ?></td>
            <td style="white-space:nowrap"><?= date('d M Y', strtotime($l['to_date'])) ?></td>
            <td style="text-align:center;font-weight:600"><?= $l['days'] ?></td>
            <td style="font-size:12px;color:var(--text-muted);max-width:130px">
              <?= htmlspecialchars(substr($l['reason'] ?? '—', 0, 60)) ?><?= strlen($l['reason']??'')>60?'…':'' ?>
            </td>
            <td style="font-size:12px;white-space:nowrap"><?= date('d M Y', strtotime($l['applied_on'])) ?></td>
            <td><span class="badge badge-<?= $cls ?>"><?= $l['status'] ?></span></td>
            <td>
              <?php if($l['status'] === 'Pending'): ?>
                <div style="display:flex;gap:5px">
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="ti ti-check"></i></button>
                  </form>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="ti ti-x"></i></button>
                  </form>
                </div>
              <?php else: ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-icon" title="Delete"
                    onclick="return confirm('Delete this leave record?')"><i class="ti ti-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal-overlay" id="leaveModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Apply Leave</div>
      <button class="modal-close" onclick="closeModal('leaveModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="apply">
      <div class="modal-body">
        <div class="form-group">
          <label>Employee *</label>
          <select name="emp_id" required>
            <option value="">-- Select Employee --</option>
            <?php $employees->data_seek(0); while($e=$employees->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($e['emp_id']) ?>"><?= htmlspecialchars($e['emp_id']) ?> — <?= htmlspecialchars($e['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Leave Type *</label>
          <select name="leave_type" required>
            <?php foreach(['Casual Leave','Medical Leave','Earned Leave','Maternity Leave','Paternity Leave','Emergency Leave'] as $t): ?>
              <option><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>From Date *</label><input type="date" name="from_date" required min="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label>To Date *</label><input type="date" name="to_date" required min="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="form-group">
          <label>Reason</label>
          <textarea name="reason" placeholder="Brief reason for leave…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('leaveModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-send"></i> Submit Application</button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
