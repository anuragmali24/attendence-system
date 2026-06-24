<?php
require_once 'includes/db.php';
$page_title = 'Employees';
$db  = getDB();
$msg = '';

// ── Handle actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $emp_id  = trim($_POST['emp_id'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $dept    = trim($_POST['department'] ?? '');
        $desig   = trim($_POST['designation'] ?? '');
        $shift   = $_POST['shift'] ?? 'General (9AM-5PM)';
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');

        if (!$emp_id || !$name || !$dept || !$desig) {
            $msg = 'error:Employee ID, Name, Department and Designation are required.';
        } elseif ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO employees (emp_id,name,department,designation,shift,phone,email) VALUES(?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssss', $emp_id, $name, $dept, $desig, $shift, $phone, $email);
            if ($stmt->execute()) $msg = 'success:Employee added successfully.';
            else $msg = 'error:Employee ID already exists or DB error.';
            $stmt->close();
        } else {
            $original_id = $_POST['original_id'] ?? $emp_id;
            $stmt = $db->prepare("UPDATE employees SET emp_id=?,name=?,department=?,designation=?,shift=?,phone=?,email=? WHERE emp_id=?");
            $stmt->bind_param('ssssssss', $emp_id, $name, $dept, $desig, $shift, $phone, $email, $original_id);
            $stmt->execute();
            $msg = 'success:Employee updated successfully.';
            $stmt->close();
        }
    } elseif ($action === 'toggle') {
        $emp_id = $_POST['emp_id'] ?? '';
        $db->query("UPDATE employees SET status=IF(status='Active','Inactive','Active') WHERE emp_id='".  $db->real_escape_string($emp_id) . "'");
        $msg = 'success:Employee status updated.';
    }
}

if (isset($_GET['delete'])) {
    $eid = $db->real_escape_string($_GET['delete']);
    $db->query("DELETE FROM employees WHERE emp_id='$eid'");
    header('Location: employees.php?msg=deleted');
    exit;
}

// ── Filters ─────────────────────────────────────────────────────────────
$search    = $_GET['q'] ?? '';
$dept_f    = $_GET['dept'] ?? '';
$status_f  = $_GET['status'] ?? 'Active';

$where = "WHERE 1=1";
if ($search) $where .= " AND (name LIKE '%" . $db->real_escape_string($search) . "%' OR emp_id LIKE '%" . $db->real_escape_string($search) . "%' OR designation LIKE '%" . $db->real_escape_string($search) . "%')";
if ($dept_f)  $where .= " AND department='" . $db->real_escape_string($dept_f) . "'";
if ($status_f) $where .= " AND status='" . $db->real_escape_string($status_f) . "'";

$employees = $db->query("SELECT * FROM employees $where ORDER BY name");
$depts = $db->query("SELECT DISTINCT department FROM employees ORDER BY department");

[$msg_type, $msg_text] = $msg ? explode(':', $msg, 2) : ($_GET['msg']==='deleted' ? ['success','Employee deleted.'] : ['','']);
require_once 'includes/header.php';
?>

<?php if($msg_text): ?>
<div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($msg_text) ?></div>
<?php endif; ?>

<div class="page-head">
  <h2>Employees</h2>
  <button class="btn btn-primary" onclick="openModal('addModal')">
    <i class="ti ti-plus"></i> Add Employee
  </button>
</div>

<!-- Filters -->
<div class="card">
  <div class="card-body" style="padding:.75rem 1.25rem">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:180px">
        <input type="text" name="q" placeholder="Search name, ID, designation…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div><select name="dept" style="max-width:200px">
        <option value="">All Departments</option>
        <?php $depts->data_seek(0); while($d=$depts->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($d['department']) ?>" <?= $dept_f===$d['department']?'selected':'' ?>><?= htmlspecialchars($d['department']) ?></option>
        <?php endwhile; ?>
      </select></div>
      <div><select name="status">
        <option value="">All Status</option>
        <option value="Active"   <?= $status_f==='Active'?'selected':'' ?>>Active</option>
        <option value="Inactive" <?= $status_f==='Inactive'?'selected':'' ?>>Inactive</option>
      </select></div>
      <button type="submit" class="btn btn-primary">Search</button>
      <a href="employees.php" class="btn">Reset</a>
    </form>
  </div>
</div>

<div class="card" style="padding:0">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Emp ID</th><th>Name</th><th>Department</th><th>Designation</th><th>Shift</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if(!$employees->num_rows): ?>
          <tr><td colspan="8"><div class="empty-state"><i class="ti ti-users-off"></i><p>No employees found</p></div></td></tr>
        <?php else: while($e = $employees->fetch_assoc()): ?>
          <tr>
            <td style="font-weight:600;color:var(--blue)"><?= htmlspecialchars($e['emp_id']) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="avatar"><?= strtoupper(substr($e['name'],0,1)) ?><?= strtoupper(substr(strrchr($e['name'],' '),1,1)) ?></div>
                <div>
                  <div><?= htmlspecialchars($e['name']) ?></div>
                  <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($e['email'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($e['department']) ?></td>
            <td><?= htmlspecialchars($e['designation']) ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($e['shift']) ?></td>
            <td><?= htmlspecialchars($e['phone'] ?? '—') ?></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="emp_id" value="<?= htmlspecialchars($e['emp_id']) ?>">
                <button type="submit" class="badge <?= $e['status']==='Active' ? 'badge-active' : 'badge-inactive' ?>" style="border:none;cursor:pointer">
                  <?= $e['status'] ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:6px">
                <button class="btn btn-sm btn-icon" title="Edit"
                  onclick="editEmployee(<?= htmlspecialchars(json_encode($e)) ?>)">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-danger" title="Delete"
                  onclick="confirmDelete('employees.php?delete=<?= urlencode($e['emp_id']) ?>','<?= htmlspecialchars($e['name']) ?>')">
                  <i class="ti ti-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Employee Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Employee</div>
      <button class="modal-close" onclick="closeModal('addModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Employee ID *</label><input name="emp_id" placeholder="BLW-009" required></div>
          <div class="form-group"><label>Full Name *</label><input name="name" placeholder="Ramesh Kumar" required></div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Department *</label>
            <select name="department" required>
              <option value="">Select…</option>
              <?php foreach(['Mechanical','Electrical','Civil','IT','Admin','Production','Quality Control','Finance','HR'] as $d): ?>
                <option><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Designation *</label><input name="designation" placeholder="Junior Engineer" required></div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Shift</label>
            <select name="shift">
              <?php foreach(['Morning (6AM-2PM)','Afternoon (2PM-10PM)','Night (10PM-6AM)','General (9AM-5PM)'] as $s): ?>
                <option><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Phone</label><input name="phone" placeholder="9876543210" maxlength="15"></div>
        </div>
        <div class="form-group"><label>Email</label><input name="email" type="email" placeholder="name@blw.indianrailways.gov.in"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save Employee</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Employee</div>
      <button class="modal-close" onclick="closeModal('editModal')">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="original_id" id="edit_original_id">
      <div class="modal-body">
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Employee ID *</label><input name="emp_id" id="edit_emp_id" required></div>
          <div class="form-group"><label>Full Name *</label><input name="name" id="edit_name" required></div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Department *</label>
            <select name="department" id="edit_dept" required>
              <?php foreach(['Mechanical','Electrical','Civil','IT','Admin','Production','Quality Control','Finance','HR'] as $d): ?>
                <option><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Designation *</label><input name="designation" id="edit_desig" required></div>
        </div>
        <div class="form-grid form-grid-2">
          <div class="form-group"><label>Shift</label>
            <select name="shift" id="edit_shift">
              <?php foreach(['Morning (6AM-2PM)','Afternoon (2PM-10PM)','Night (10PM-6AM)','General (9AM-5PM)'] as $s): ?>
                <option><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Phone</label><input name="phone" id="edit_phone" maxlength="15"></div>
        </div>
        <div class="form-group"><label>Email</label><input name="email" id="edit_email" type="email"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Update Employee</button>
      </div>
    </form>
  </div>
</div>

<script>
function editEmployee(e) {
  document.getElementById('edit_original_id').value = e.emp_id;
  document.getElementById('edit_emp_id').value = e.emp_id;
  document.getElementById('edit_name').value = e.name;
  document.getElementById('edit_dept').value = e.department;
  document.getElementById('edit_desig').value = e.designation;
  document.getElementById('edit_shift').value = e.shift;
  document.getElementById('edit_phone').value = e.phone || '';
  document.getElementById('edit_email').value = e.email || '';
  openModal('editModal');
}
</script>

<?php require_once 'includes/footer.php'; ?>
