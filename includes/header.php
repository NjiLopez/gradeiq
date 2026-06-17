<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎯</text></svg>">
</head>
<body>

<header class="site-header">
  <div class="wrapper">
    <nav class="nav">
      <a href="<?= isAdmin() ? 'index.php' : 'student_dashboard.php' ?>" class="nav-brand">
        <div class="logo-icon">🎯</div>
        <span class="brand-text">Grade<span>IQ</span></span>
      </a>

      <ul class="nav-links">
        <?php if (isAdmin()): ?>
          <li><a href="index.php">Dashboard</a></li>
          <li><a href="exams.php">Exam Bank</a></li>
          <li><a href="grade.php">Grade</a></li>
          <li><a href="results.php">Results</a></li>
          <li><a href="manage_users.php">Users</a></li>
          <li><a href="create_exam.php" style="background:var(--accent-glow);color:var(--accent);border:1px solid rgba(240,180,41,0.25)">+ New Exam</a></li>
        <?php elseif (isStudent()): ?>
          <li><a href="student_dashboard.php">My Dashboard</a></li>
          <li><a href="take_exam.php">Take Exam</a></li>
        <?php endif; ?>
      </ul>

      <?php
$u = currentUser();
if (isLoggedIn() && $u):
?>
      <div style="display:flex;align-items:center;gap:10px;margin-left:16px">
        <div style="text-align:right;display:none;line-height:1.3" class="nav-user-info">
          <div style="font-size:0.8rem;font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($u['full_name']) ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted)"><?= $u['role'] === 'admin' ? '🛡 Admin' : '👤 Student' ?></div>
        </div>
        <div style="width:36px;height:36px;border-radius:10px;background:var(--<?= $u['role'] === 'admin' ? 'accent' : 'bg-card-hover' ?>);border:1px solid var(--border-light);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:<?= $u['role'] === 'admin' ? '#000' : 'var(--text-primary)' ?>;flex-shrink:0">
          <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
        </div>
        <a href="logout.php" class="btn btn-secondary" style="padding:7px 14px;font-size:0.78rem">Sign Out</a>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div id="toastContainer" class="toast-container"></div>

<?php
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');
if ($flashSuccess): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($flashSuccess) ?>,'success'))</script>
<?php endif; ?>
<?php if ($flashError): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($flashError) ?>,'error'))</script>
<?php endif; ?>
