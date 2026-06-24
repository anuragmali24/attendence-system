<?php
require_once 'includes/db.php';
$page_title = 'Dashboard';
$db = getDB();
$today = date('Y-m-d');

// Totals
$total_emp   = $db->query("SELECT COUNT(*) c FROM employees WHERE status='Active'")->fetch_assoc()['c'];
$present     = $db->query("SELECT COUNT(*) c FROM attendance WHERE att_date='$today' AND status IN('Present','Late','Half Day')")->fetch_assoc()['c'];
$absent      = $total_emp - $present;
$on_leave    = $db->query("SELECT COUNT(*) c FROM leaves WHERE from_date<='$today' AND to_date>='$today' AND status='Approved'")->fetch_assoc()['c'];
$pending_lv  = $db->query("SELECT COUNT(*) c FROM leaves WHERE status='Pending'")->fetch_assoc()['c'];

// Today's attendance with employee info
$today_rows = $db->query("
    SELECT e.emp_id, e.name, e.department,
           a.check_in, a.check_out, a.status
    FROM employees e
    LEFT JOIN attendance a ON a.emp_id=e.emp_id AND a.att_date='$today'
    WHERE e.status='Active'
    ORDER BY e.name
");

// Recent leaves
$recent_leaves = $db->query("
    SELECT l.*, e.name FROM leaves l
    JOIN employees e ON e.emp_id=l.emp_id
    ORDER BY l.created_at DESC LIMIT 6
");

require_once 'includes/header.php';
?>

<div class="metric-grid">
  <div class="metric-card blue-bg">
    <div class="metric-label">Total Employees</div>
    <div class="metric-value blue"><?= $total_emp ?></div>
    <div class="metric-sub">Active staff</div>
  </div>
  <div class="metric-card green-bg">
    <div class="metric-label">Present Today</div>
    <div class="metric-value green"><?= $present ?></div>
    <div class="metric-sub"><?= $total_emp ? round($present/$total_emp*100) : 0 ?>% attendance</div>
  </div>
  <div class="metric-card red-bg">
    <div class="metric-label">Absent Today</div>
    <div class="metric-value red"><?= $absent ?></div>
    <div class="metric-sub">Not marked</div>
  </div>
  <div class="metric-card amber-bg">
    <div class="metric-label">On Leave</div>
    <div class="metric-value amber"><?= $on_leave ?></div>
    <div class="metric-sub">Approved leaves</div>
  </div>
  <div class="metric-card" style="border-color:#d1c4e9;background:#ede7f6">
    <div class="metric-label">Pending Leaves</div>
    <div class="metric-value purple"><?= $pending_lv ?></div>
    <div class="metric-sub">Need approval</div>
  </div>
</div>

<div class="grid-2">

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ti ti-clock-check"></i> Today's Attendance</div>
    <a href="attendance.php" class="btn btn-sm btn-primary">Mark Now</a>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Employee</th><th>Dept</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
      <tbody>
        <?php while($r = $today_rows->fetch_assoc()):
          $st = $r['status'] ?? 'Absent';
          $cls = strtolower(str_replace(' ','',$st));
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="avatar"><?= strtoupper(substr($r['name'],0,1).substr(strrchr($r['name'],' '),1,1)) ?></div>
              <?= htmlspecialchars($r['name']) ?>
            </div>
          </td>
          <td><?= htmlspecialchars($r['department']) ?></td>
          <td><?= $r['check_in'] ? date('h:i A', strtotime($r['check_in'])) : '—' ?></td>
          <td><?= $r['check_out'] ? date('h:i A', strtotime($r['check_out'])) : '—' ?></td>
          <td><span class="badge badge-<?= $cls ?>"><?= $st ?></span></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ti ti-calendar-off"></i> Recent Leave Applications</div>
    <a href="leave.php" class="btn btn-sm">View All</a>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Status</th></tr></thead>
      <tbody>
        <?php if($recent_leaves->num_rows === 0): ?>
          <tr><td colspan="4" class="empty-state"><i class="ti ti-mood-happy"></i><p>No leave applications yet</p></td></tr>
        <?php else: while($l = $recent_leaves->fetch_assoc()):
          $cls = strtolower($l['status']); ?>
          <tr>
            <td><?= htmlspecialchars($l['name']) ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($l['leave_type']) ?></td>
            <td style="font-size:12px;white-space:nowrap"><?= date('d M', strtotime($l['from_date'])) ?> – <?= date('d M', strtotime($l['to_date'])) ?></td>
            <td><span class="badge badge-<?= $cls ?>"><?= $l['status'] ?></span></td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div><!-- /grid-2 -->

<?php require_once 'includes/footer.php'; ?>
