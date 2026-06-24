<?php
require_once 'includes/db.php';
$page_title = 'Attendance Records';
$db = getDB();

$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_dept = $_GET['dept'] ?? '';
$filter_stat = $_GET['status'] ?? '';

// Build query
$where = ["a.att_date = ?"];
$params = [$filter_date];
$types  = 's';

if ($filter_dept) { $where[] = "e.department = ?"; $params[] = $filter_dept; $types .= 's'; }
if ($filter_stat) { $where[] = "a.status = ?";     $params[] = $filter_stat; $types .= 's'; }

$sql = "
    SELECT e.emp_id, e.name, e.department, e.shift,
           a.check_in, a.check_out, a.status, a.remarks
    FROM employees e
    LEFT JOIN attendance a ON a.emp_id=e.emp_id AND a.att_date=?
    WHERE e.status='Active'
    " . ($filter_dept ? " AND e.department=?" : "")
      . ($filter_stat ? " AND (a.status=? OR (a.status IS NULL AND ?='Absent'))" : "") . "
    ORDER BY e.name
";

// Simpler approach: pull all active employees then filter
$emp_q = "SELECT e.emp_id, e.name, e.department, e.shift,
                  a.check_in, a.check_out, a.status, a.remarks
           FROM employees e
           LEFT JOIN attendance a ON a.emp_id=e.emp_id AND a.att_date='$filter_date'
           WHERE e.status='Active'";
if ($filter_dept) $emp_q .= " AND e.department='" . $db->real_escape_string($filter_dept) . "'";
$emp_q .= " ORDER BY e.name";

$rows = $db->query($emp_q);
$all  = [];
while ($r = $rows->fetch_assoc()) {
    $r['status'] = $r['status'] ?? 'Absent';
    if ($filter_stat && $r['status'] !== $filter_stat) continue;
    $all[] = $r;
}

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="BLW_Attendance_' . $filter_date . '.csv"');
    echo "Employee ID,Name,Department,Shift,Check In,Check Out,Hours,Status\n";
    foreach ($all as $r) {
        $hrs = ($r['check_in'] && $r['check_out'])
             ? round((strtotime($r['check_out']) - strtotime($r['check_in'])) / 3600, 1)
             : '';
        echo implode(',', [
            $r['emp_id'],
            '"' . $r['name'] . '"',
            $r['department'],
            '"' . $r['shift'] . '"',
            $r['check_in']  ? date('h:i A', strtotime($r['check_in']))  : '',
            $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '',
            $hrs,
            $r['status']
        ]) . "\n";
    }
    exit;
}

$depts = $db->query("SELECT DISTINCT department FROM employees ORDER BY department");
require_once 'includes/header.php';
?>

<div class="page-head">
  <h2>Attendance Records</h2>
  <a href="?date=<?= $filter_date ?>&dept=<?= urlencode($filter_dept) ?>&status=<?= urlencode($filter_stat) ?>&export=1"
     class="btn btn-primary btn-sm">
    <i class="ti ti-download"></i> Export CSV
  </a>
</div>

<div class="card">
  <div class="card-body" style="padding:.875rem 1.25rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label>Date</label>
        <input type="date" name="date" value="<?= $filter_date ?>" style="max-width:180px">
      </div>
      <div class="form-group" style="margin:0">
        <label>Department</label>
        <select name="dept" style="max-width:200px">
          <option value="">All Departments</option>
          <?php while($d = $depts->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($d['department']) ?>" <?= $filter_dept===$d['department']?'selected':'' ?>>
              <?= htmlspecialchars($d['department']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>Status</label>
        <select name="status" style="max-width:160px">
          <option value="">All Status</option>
          <?php foreach(['Present','Absent','Late','Half Day','On Leave'] as $s): ?>
            <option value="<?= $s ?>" <?= $filter_stat===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-bottom:0">
        <i class="ti ti-search"></i> Filter
      </button>
      <a href="records.php" class="btn" style="margin-bottom:0">Reset</a>
    </form>
  </div>
</div>

<?php
$counts = array_count_values(array_column($all, 'status'));
$present_c = ($counts['Present'] ?? 0) + ($counts['Late'] ?? 0) + ($counts['Half Day'] ?? 0);
?>
<div class="stat-row">
  <div class="stat"><div class="val blue"><?= count($all) ?></div><div class="lbl">Total</div></div>
  <div class="stat"><div class="val green"><?= $present_c ?></div><div class="lbl">Present</div></div>
  <div class="stat"><div class="val red"><?= $counts['Absent'] ?? 0 ?></div><div class="lbl">Absent</div></div>
  <div class="stat"><div class="val amber"><?= $counts['Late'] ?? 0 ?></div><div class="lbl">Late</div></div>
  <div class="stat"><div class="val" style="color:#1565c0"><?= $counts['Half Day'] ?? 0 ?></div><div class="lbl">Half Day</div></div>
</div>

<div class="card" style="padding:0">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>Emp ID</th><th>Name</th><th>Department</th><th>Shift</th>
          <th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th><th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($all)): ?>
          <tr><td colspan="9">
            <div class="empty-state"><i class="ti ti-table-off"></i><p>No records found</p></div>
          </td></tr>
        <?php else: foreach($all as $r):
          $cls = strtolower(str_replace(' ','',$r['status']));
          $hrs = ($r['check_in'] && $r['check_out'])
               ? round((strtotime($r['check_out']) - strtotime($r['check_in'])) / 3600, 1) . 'h'
               : '—';
        ?>
          <tr>
            <td style="font-weight:600;color:var(--blue)"><?= htmlspecialchars($r['emp_id']) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="avatar"><?= strtoupper(substr($r['name'],0,1)) ?><?= strtoupper(substr(strrchr($r['name'],' '),1,1)) ?></div>
                <?= htmlspecialchars($r['name']) ?>
              </div>
            </td>
            <td><?= htmlspecialchars($r['department']) ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($r['shift']) ?></td>
            <td><?= $r['check_in']  ? date('h:i A', strtotime($r['check_in']))  : '—' ?></td>
            <td><?= $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '—' ?></td>
            <td><?= $hrs ?></td>
            <td><span class="badge badge-<?= $cls ?>"><?= $r['status'] ?></span></td>
            <td style="color:var(--text-muted);font-size:12px"><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
