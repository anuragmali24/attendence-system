<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$today = date('l, d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BLW Attendance System <?= isset($page_title) ? '— '.$page_title : '' ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.33.0/tabler-icons.min.css">
</head>
<body>

<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-badge">BLW</div>
    <div>
      <div class="logo-title">Banaras Locomotive</div>
      <div class="logo-sub">Works — Attendance</div>
    </div>
  </div>

  <nav class="sidenav">
    <a href="index.php" class="nav-item <?= $current_page==='index'?'active':'' ?>">
      <i class="ti ti-layout-dashboard"></i> Dashboard
    </a>
    <a href="attendance.php" class="nav-item <?= $current_page==='attendance'?'active':'' ?>">
      <i class="ti ti-clock"></i> Mark Attendance
    </a>
    <a href="records.php" class="nav-item <?= $current_page==='records'?'active':'' ?>">
      <i class="ti ti-table"></i> Attendance Records
    </a>
    <a href="employees.php" class="nav-item <?= $current_page==='employees'?'active':'' ?>">
      <i class="ti ti-users"></i> Employees
    </a>
    <a href="leave.php" class="nav-item <?= $current_page==='leave'?'active':'' ?>">
      <i class="ti ti-calendar-off"></i> Leave Management
    </a>
    <a href="reports.php" class="nav-item <?= $current_page==='reports'?'active':'' ?>">
      <i class="ti ti-chart-bar"></i> Reports
    </a>
  </nav>

  <div class="sidebar-footer">
    <i class="ti ti-train"></i> Indian Railways
  </div>
</div>

<div class="main-wrap">
  <header class="topbar">
    <button class="menu-toggle" onclick="document.body.classList.toggle('sidebar-open')" aria-label="Toggle menu">
      <i class="ti ti-menu-2"></i>
    </button>
    <?php if(isset($page_title)): ?>
      <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
    <?php endif; ?>
    <div class="topbar-right">
      <span class="date-chip"><i class="ti ti-calendar"></i> <?= $today ?></span>
    </div>
  </header>
  <div class="content">
