<?php
require_once 'includes/db.php';
$page_title = 'Mark Attendance';
$db  = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = trim($_POST['emp_id'] ?? '');
    $action = $_POST['action'] ?? '';
    $today  = date('Y-m-d');
    $now    = date('H:i:s');

    if ($emp_id && in_array($action, ['checkin','checkout'])) {
        if ($action === 'checkin') {
            $stmt = $db->prepare("
                INSERT INTO attendance (emp_id, att_date, check_in, status)
                VALUES (?, ?, ?, 'Present')
                ON DUPLICATE KEY UPDATE
                  check_in = IF(check_in IS NULL, VALUES(check_in), check_in),
                  status   = IF(check_in IS NULL, 'Present', status)
            ");
            $stmt->bind_param('sss', $emp_id, $today, $now);
            $stmt->execute();
            $msg = $stmt->affected_rows > 0 ? 'success:Check-in recorded at ' . date('h:i A') : 'error:Already checked in today';
            $stmt->close();
        } else {
            $stmt = $db->prepare("
                UPDATE attendance SET check_out=?, status=
                  CASE WHEN TIMESTAMPDIFF(HOUR, check_in, ?) < 4 THEN 'Half Day' ELSE status END
                WHERE emp_id=? AND att_date=? AND check_in IS NOT NULL AND check_out IS NULL
            ");
            $stmt->bind_param('ssss', $now, $now, $emp_id, $today);
            $stmt->execute();
            $msg = $stmt->affected_rows > 0 ? 'success:Check-out recorded at ' . date('h:i A') : 'error:No check-in found or already checked out';
            $stmt->close();
        }
    }
}

[$msg_type, $msg_text] = $msg ? explode(':', $msg, 2) : ['', ''];

$employees = $db->query("SELECT emp_id, name, department FROM employees WHERE status='Active' ORDER BY name");
require_once 'includes/header.php';
?>

<?php if($msg_text): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'danger' ?>">
  <?= htmlspecialchars($msg_text) ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:560px;margin:0 auto">
  <div class="card-body">
    <div class="clock-section">
      <div class="clock-display" id="liveClock">00:00:00</div>
      <div class="clock-date-str"><?= date('l, d F Y') ?></div>

      <form method="POST" id="attForm">
        <div class="clock-select">
          <label>Select Employee</label>
          <select name="emp_id" id="empSelect" required>
            <option value="">-- Choose Employee --</option>
            <?php while($e = $employees->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($e['emp_id']) ?>">
                <?= htmlspecialchars($e['emp_id']) ?> — <?= htmlspecialchars($e['name']) ?>
                (<?= htmlspecialchars($e['department']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="clock-btns">
          <button type="submit" name="action" value="checkin"
            class="btn btn-success clock-btn" onclick="return confirmAction('in')">
            <i class="ti ti-login"></i> Check In
          </button>
          <button type="submit" name="action" value="checkout"
            class="btn btn-danger clock-btn" onclick="return confirmAction('out')">
            <i class="ti ti-logout"></i> Check Out
          </button>
        </div>

        <div class="att-info" id="attInfo"></div>
      </form>
    </div>
  </div>
</div>

<!-- Today's summary -->
<div class="card" style="margin-top:1.5rem">
  <div class="card-header">
    <div class="card-title"><i class="ti ti-table"></i> Today's Log — <?= date('d M Y') ?></div>
    <a href="records.php" class="btn btn-sm">Full Records</a>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Employee</th><th>Department</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead>
      <tbody>
        <?php
        $rows = $db->query("
            SELECT e.emp_id, e.name, e.department, a.check_in, a.check_out, a.status
            FROM employees e
            LEFT JOIN attendance a ON a.emp_id=e.emp_id AND a.att_date='" . date('Y-m-d') . "'
            WHERE e.status='Active'
            ORDER BY e.name
        ");
        while($r = $rows->fetch_assoc()):
          $st  = $r['status'] ?? 'Absent';
          $cls = strtolower(str_replace(' ','',$st));
          $hrs = ($r['check_in'] && $r['check_out'])
               ? round((strtotime($r['check_out']) - strtotime($r['check_in'])) / 3600, 1) . 'h'
               : '—';
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="avatar"><?= strtoupper(substr($r['name'],0,1)) ?><?= strtoupper(substr(strrchr($r['name'],' '),1,1)) ?></div>
              <?= htmlspecialchars($r['name']) ?>
            </div>
          </td>
          <td><?= htmlspecialchars($r['department']) ?></td>
          <td><?= $r['check_in']  ? date('h:i A', strtotime($r['check_in']))  : '—' ?></td>
          <td><?= $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '—' ?></td>
          <td><?= $hrs ?></td>
          <td><span class="badge badge-<?= $cls ?>"><?= $st ?></span></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function confirmAction(type) {
  const emp = document.getElementById('empSelect');
  if (!emp.value) { alert('Please select an employee first.'); return false; }
  return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
