<?php
// ── BLW Attendance System — One-click installer ──────────────────────────
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die('<p style="color:red">Connection failed: ' . $conn->connect_error . '</p>');
}

$sqls = [
    "CREATE DATABASE IF NOT EXISTS blw_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "USE blw_attendance",

    "CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )",

    "CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        department VARCHAR(100) NOT NULL,
        designation VARCHAR(120) NOT NULL,
        shift ENUM('Morning (6AM-2PM)','Afternoon (2PM-10PM)','Night (10PM-6AM)','General (9AM-5PM)') DEFAULT 'General (9AM-5PM)',
        phone VARCHAR(15),
        email VARCHAR(150),
        status ENUM('Active','Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id VARCHAR(20) NOT NULL,
        att_date DATE NOT NULL,
        check_in TIME,
        check_out TIME,
        status ENUM('Present','Absent','Half Day','Late','On Leave') DEFAULT 'Absent',
        remarks VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_att (emp_id, att_date)
    )",

    "CREATE TABLE IF NOT EXISTS leaves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_id VARCHAR(20) NOT NULL,
        leave_type ENUM('Casual Leave','Medical Leave','Earned Leave','Maternity Leave','Paternity Leave','Emergency Leave') NOT NULL,
        from_date DATE NOT NULL,
        to_date DATE NOT NULL,
        days INT NOT NULL DEFAULT 1,
        reason TEXT,
        status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
        applied_on DATE NOT NULL,
        approved_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Seed departments
    "INSERT IGNORE INTO departments (name) VALUES
        ('Mechanical'),('Electrical'),('Civil'),('IT'),
        ('Admin'),('Production'),('Quality Control'),('Finance'),('HR')",

    // Seed employees
    "INSERT IGNORE INTO employees (emp_id,name,department,designation,shift,phone,email) VALUES
        ('BLW-001','Rajesh Kumar Singh','Mechanical','Senior Engineer','Morning (6AM-2PM)','9876543210','rks@blw.indianrailways.gov.in'),
        ('BLW-002','Sunita Devi','Admin','Administrative Officer','General (9AM-5PM)','9823456781','sd@blw.indianrailways.gov.in'),
        ('BLW-003','Amit Verma','Electrical','Junior Engineer','Afternoon (2PM-10PM)','9812345678','av@blw.indianrailways.gov.in'),
        ('BLW-004','Priya Sharma','IT','IT Officer','General (9AM-5PM)','9898989898','ps@blw.indianrailways.gov.in'),
        ('BLW-005','Mohd. Irfan Khan','Production','Foreman','Night (10PM-6AM)','9765432109','mik@blw.indianrailways.gov.in'),
        ('BLW-006','Kavita Tripathi','Quality Control','QC Inspector','Morning (6AM-2PM)','9900112233','kt@blw.indianrailways.gov.in'),
        ('BLW-007','Deepak Mishra','Mechanical','Technician','Morning (6AM-2PM)','9811223344','dm@blw.indianrailways.gov.in'),
        ('BLW-008','Aarti Singh','HR','HR Manager','General (9AM-5PM)','9900998877','as@blw.indianrailways.gov.in')"
];

$errors = [];
$done = [];
foreach ($sqls as $sql) {
    if (!$conn->query($sql)) {
        $errors[] = $conn->error . ' → ' . substr($sql, 0, 80);
    } else {
        $done[] = substr($sql, 0, 80) . '…';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BLW Attendance — Setup</title>
<style>
  body{font-family:system-ui,sans-serif;background:#f4f6fb;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .box{background:#fff;border-radius:12px;border:1px solid #e0e0e0;padding:2rem;max-width:600px;width:90%}
  h2{color:#1a4fa0;margin-bottom:1rem}
  .ok{color:#2e7d32;font-size:13px;margin:3px 0}
  .err{color:#c00;font-size:13px;margin:3px 0;background:#fff0f0;padding:4px 8px;border-radius:4px}
  .btn{display:inline-block;margin-top:1.5rem;padding:10px 24px;background:#1a4fa0;color:#fff;border-radius:8px;text-decoration:none;font-weight:500}
  .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600}
  .badge.success{background:#e8f5e9;color:#2e7d32}
  .badge.fail{background:#fff0f0;color:#c00}
</style>
</head>
<body>
<div class="box">
  <h2>🚂 BLW Attendance System — Setup</h2>
  <?php if(empty($errors)): ?>
    <p><span class="badge success">✓ Setup Complete</span> Database and tables created successfully.</p>
  <?php else: ?>
    <p><span class="badge fail">✗ Errors Occurred</span></p>
    <?php foreach($errors as $e): ?><div class="err">✗ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  <?php endif; ?>

  <hr style="margin:1rem 0;border:none;border-top:1px solid #eee">
  <p style="font-size:13px;color:#555">Steps completed:</p>
  <?php foreach($done as $d): ?><div class="ok">✓ <?= htmlspecialchars($d) ?></div><?php endforeach; ?>

  <?php if(empty($errors)): ?>
    <a class="btn" href="index.php">Go to Dashboard →</a>
  <?php endif; ?>
</div>
</body>
</html>
