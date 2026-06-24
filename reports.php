<?php
require_once 'includes/db.php';
$page_title = 'Reports';
$db = getDB();

$month  = $_GET['month'] ?? date('Y-m');
$dept_f = $_GET['dept'] ?? '';

[$yr, $mn] = explode('-', $month);
$days_in_month = cal_days_in_month(CAL_GREGORIAN, (int)$mn, (int)$yr);
$month_start = "$yr-$mn-01";
$month_end   = "$yr-$mn-$days_in_month";
$month_label = date('F Y', strtotime($month_start));

// Department-wise summary
$dept_where = $dept_f ? "AND e.department='" . $db->real_escape_string($dept_f) . "'" : '';
$dept_stats = $db->query("
    SELECT e.department,
           COUNT(DISTINCT e.emp_id) AS emp_count,
           SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) AS present,
           SUM(CASE WHEN a.status='Absent' OR a.status IS NULL THEN 1 ELSE 0 END) AS absent,
           SUM(CASE WHEN a.status='Late' THEN 1 ELSE 0 END) AS late,
           SUM(CASE WHEN a.status='Half Day' THEN 1 ELSE 0 END) AS half_day
    FROM employees e
    LEFT JOIN attendance a ON a.emp_id=e.emp_id AND a.att_date BETWEEN '$month_start' AND '$month_end'
    WHERE e.status='Active' $dept_where
    GROUP BY e.department
    ORDER BY e.department
");

// Employee monthly summary
$emp_summary = $db->query("
    SELECT e.emp_id, e.name, e.department,
           SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) AS present,
           SUM(CASE WHEN a.status='Absent' OR a.status IS NULL THEN 1 ELSE 0 END) AS absent,
           SUM(CASE WHEN a.status='Late' THEN 1 ELSE 0 END) AS late,
           SUM(CASE WHEN a.status='Half Day' THEN 1 ELSE 0 END) AS half_day,
           SUM(CASE WHEN a.status='On Leave' THEN 1 ELSE 0 END) AS on_leave
    FROM employees e
    LEFT JOIN attendance a ON a.emp_id=e.emp_id AND a.att_date BETWEEN '$month_start' AND '$month_end'
    WHERE e.status='Active' $dept_where
    GROUP BY e.emp_id, e.name, e.department
    ORDER BY e.name
");

// Overall totals
$totals = $db->query("
    SELECT
      SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
      SUM(CASE WHEN status='Absent'  THEN 1 ELSE 0 END) AS absent,
      SUM(CASE WHEN status='Late'    THEN 1 ELSE 0 END) AS late,
      SUM(CASE WHEN status='Half Day'THEN 1 ELSE 0 END) AS half_day
    FROM attendance
    WHERE att_date BETWEEN '$month_start' AND '$month_end'
")->fetch_assoc();

// Leave summary
$leave_sum = $db->query("
    SELECT leave_type, COUNT(*) cnt, SUM(days) total_days
    FROM leaves
    WHERE from_date BETWEEN '$month_start' AND '$month_end'
    AND status='Approved'
    GROUP BY leave_type
");

$depts = $db->query("SELECT DISTINCT department FROM employees ORDER BY department");
require_once 'includes/header.php';
?>

<div class="page-head">
  <h2>Reports — <?= $month_label ?></h2>
  <a href="records.php" class="btn"><i class="ti ti-table"></i> Detailed Records</a>
</div>

<!-- Filter -->
<div class="card">
  <div class="card-body" style="padding:.75rem 1.25rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label>Month</label>
        <input type="month" name="month" value="<?= $month ?>">
      </div>
      <div class="form-group" style="margin:0">
        <label>Department</label>
        <select name="dept" style="max-width:200px">
          <option value="">All Departments</option>
          <?php while($d=$depts->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($d['department']) ?>" <?= $dept_f===$d['department']?'selected':'' ?>><?= htmlspecialchars($d['department']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Generate</button>
      <a href="reports.php" class="btn">Reset</a>
      <a href="?month=<?= $month ?>&dept=<?= urlencode($dept_f) ?>&export=1" class="btn btn-primary">
        <i class="ti ti-download"></i> Export CSV
      </a>
    </form>
  </div>
</div>

<!-- Overall metrics -->
<div class="metric-grid">
  <div class="metric-card blue-bg">
    <div class="metric-label">Working Days</div>
    <div class="metric-value blue"><?= $days_in_month ?></div>
    <div class="metric-sub"><?= $month_label ?></div>
  </div>
  <div class="metric-card green-bg">
    <div class="metric-label">Total Present</div>
    <div class="metric-value green"><?= $totals['present'] ?? 0 ?></div>
    <div class="metric-sub">Entries</div>
  </div>
  <div class="metric-card red-bg">
    <div class="metric-label">Total Absent</div>
    <div class="metric-value red"><?= $totals['absent'] ?? 0 ?></div>
    <div class="metric-sub">Entries</div>
  </div>
  <div class="metric-card amber-bg">
    <div class="metric-label">Late Arrivals</div>
    <div class="metric-value amber"><?= $totals['late'] ?? 0 ?></div>
    <div class="metric-sub">Entries</div>
  </div>
</div>

<div class="grid-2">

<!-- Department-wise -->
<div class="card">
  <div class="card-header"><div class="card-title"><i class="ti ti-building"></i> Department Summary</div></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Department</th><th>Staff</th><th>Present</th><th>Absent</th><th>Late</th><th>Half Day</th></tr></thead>
      <tbody>
        <?php if(!$dept_stats->num_rows): ?>
          <tr><td colspan="6"><div class="empty-state"><i class="ti ti-chart-off"></i><p>No data</p></div></td></tr>
        <?php else: while($d = $dept_stats->fetch_assoc()): ?>
          <tr>
            <td style="font-weight:500"><?= htmlspecialchars($d['department']) ?></td>
            <td><?= $d['emp_count'] ?></td>
            <td style="color:var(--green);font-weight:600"><?= $d['present'] ?></td>
            <td style="color:var(--red)"><?= $d['absent'] ?></td>
            <td style="color:var(--amber)"><?= $d['late'] ?></td>
            <td style="color:#1565c0"><?= $d['half_day'] ?></td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Leave type summary -->
<div class="card">
  <div class="card-header"><div class="card-title"><i class="ti ti-calendar-stats"></i> Approved Leaves by Type</div></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Leave Type</th><th>Applications</th><th>Total Days</th></tr></thead>
      <tbody>
        <?php if(!$leave_sum->num_rows): ?>
          <tr><td colspan="3"><div class="empty-state"><i class="ti ti-calendar-off"></i><p>No approved leaves this month</p></div></td></tr>
        <?php else: while($l = $leave_sum->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($l['leave_type']) ?></td>
            <td style="text-align:center"><?= $l['cnt'] ?></td>
            <td style="text-align:center;font-weight:600;color:var(--blue)"><?= $l['total_days'] ?></td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div><!-- /grid-2 -->

<!-- Employee monthly summary -->
<div class="card">
  <div class="card-header"><div class="card-title"><i class="ti ti-user-check"></i> Employee Monthly Summary</div></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Present</th><th>Absent</th><th>Late</th><th>Half Day</th><th>On Leave</th><th>Attendance %</th></tr></thead>
      <tbody>
        <?php if(!$emp_summary->num_rows): ?>
          <tr><td colspan="9"><div class="empty-state"><i class="ti ti-users-off"></i><p>No employee data</p></div></td></tr>
        <?php else: while($e = $emp_summary->fetch_assoc()):
          $total = $e['present'] + $e['absent'] + $e['late'] + $e['half_day'] + $e['on_leave'];
          $pct   = $days_in_month > 0 ? round(($e['present'] + $e['late']) / $days_in_month * 100) : 0;
          $pct_color = $pct >= 80 ? 'var(--green)' : ($pct >= 60 ? 'var(--amber)' : 'var(--red)');
        ?>
          <tr>
            <td style="font-weight:600;color:var(--blue)"><?= htmlspecialchars($e['emp_id']) ?></td>
            <td><?= htmlspecialchars($e['name']) ?></td>
            <td><?= htmlspecialchars($e['department']) ?></td>
            <td style="color:var(--green);font-weight:600"><?= $e['present'] ?></td>
            <td style="color:var(--red)"><?= $e['absent'] ?></td>
            <td style="color:var(--amber)"><?= $e['late'] ?></td>
            <td style="color:#1565c0"><?= $e['half_day'] ?></td>
            <td style="color:var(--purple)"><?= $e['on_leave'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div style="flex:1;background:#eee;border-radius:4px;height:6px;min-width:60px">
                  <div style="width:<?= $pct ?>%;background:<?= $pct_color ?>;height:100%;border-radius:4px"></div>
                </div>
                <span style="font-weight:600;color:<?= $pct_color ?>;min-width:36px"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
// CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="BLW_Report_' . $month . '.csv"');
    echo "Employee ID,Name,Department,Present,Absent,Late,Half Day,On Leave,Attendance %\n";
    $emp_summary->data_seek(0);
    while($e = $emp_summary->fetch_assoc()) {
        $pct = $days_in_month > 0 ? round(($e['present'] + $e['late']) / $days_in_month * 100) : 0;
        echo implode(',', [$e['emp_id'],'"'.$e['name'].'"',$e['department'],
            $e['present'],$e['absent'],$e['late'],$e['half_day'],$e['on_leave'],$pct.'%']) . "\n";
    }
    exit;
}
?>

<?php require_once 'includes/footer.php'; ?>
