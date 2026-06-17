<?php
require_once 'includes/functions.php';

// Redirect already-logged-in users
if (isLoggedIn()) {
    header(isAdmin() ? 'Location: index.php' : 'Location: student_dashboard.php');
    exit;
}

$error      = '';
$mode       = $_GET['mode'] ?? 'login';
$adminCount = countAdmins();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $res = loginUser(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
        if ($res['ok']) {
            $dest = $res['user']['role'] === 'admin' ? 'index.php' : 'student_dashboard.php';
            header("Location: $dest");
            exit;
        }
        $error = $res['msg'];
        $mode  = 'login';

    } elseif ($action === 'register') {
        $regRole = $_POST['role'] ?? 'student';
        $res = registerUser(
            trim($_POST['username']   ?? ''),
            trim($_POST['full_name']  ?? ''),
            $_POST['password']        ?? '',
            $regRole,
            trim($_POST['student_id'] ?? '')
        );
        if ($res['ok']) {
            loginUser(trim($_POST['username']), $_POST['password']);
            $dest = $regRole === 'admin' ? 'index.php' : 'student_dashboard.php';
            header("Location: $dest");
            exit;
        }
        $error = $res['msg'];
        $mode  = 'register';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — GradeIQ</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎯</text></svg>">
  <style>
    .auth-page {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }
    .auth-left {
      background: var(--bg-secondary);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 56px;
      position: relative;
      overflow: hidden;
    }
    .auth-left::before {
      content: '';
      position: absolute;
      top: -120px; left: -120px;
      width: 500px; height: 500px;
      background: radial-gradient(ellipse, rgba(240,180,41,0.07) 0%, transparent 70%);
      pointer-events: none;
    }
    .auth-left::after {
      content: '';
      position: absolute;
      bottom: -80px; right: -80px;
      width: 350px; height: 350px;
      background: radial-gradient(ellipse, rgba(52,152,219,0.05) 0%, transparent 70%);
      pointer-events: none;
    }
    .auth-right {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 48px;
    }
    .auth-form-box { width: 100%; max-width: 400px; }
    .auth-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 48px; }
    .auth-brand .logo-icon {
      width: 40px; height: 40px; background: var(--accent);
      border-radius: 12px; display: flex; align-items: center;
      justify-content: center; font-size: 20px;
    }
    .auth-brand .brand-text {
      font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700;
    }
    .auth-brand .brand-text span { color: var(--accent); }
    .auth-headline {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem; font-weight: 700; line-height: 1.2;
      margin-bottom: 16px; letter-spacing: -0.02em;
    }
    .auth-headline em { color: var(--accent); font-style: italic; }
    .auth-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.7; margin-bottom: 40px; max-width: 360px; }
    .feature-list { list-style: none; display: flex; flex-direction: column; gap: 14px; }
    .feature-list li { display: flex; align-items: center; gap: 12px; font-size: 0.875rem; color: var(--text-secondary); }
    .feature-list li .fi {
      width: 32px; height: 32px; background: var(--bg-card); border: 1px solid var(--border);
      border-radius: 8px; display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0;
    }
    .role-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 28px; }
    .role-tab {
      background: var(--bg-secondary); border: 1px solid var(--border);
      border-radius: 10px; padding: 14px; text-align: center; cursor: pointer;
      transition: var(--transition); display: flex; flex-direction: column;
      align-items: center; gap: 6px;
    }
    .role-tab .rt-icon { font-size: 1.5rem; }
    .role-tab .rt-label { font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.06em; }
    .role-tab.active { border-color: var(--accent); background: var(--accent-glow); }
    .role-tab.active .rt-label { color: var(--accent); }
    .auth-title { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; margin-bottom: 6px; }
    .auth-sub { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 28px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .divider-text {
      text-align: center; position: relative; margin: 20px 0;
      font-size: 0.78rem; color: var(--text-muted);
    }
    .divider-text::before, .divider-text::after {
      content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: var(--border);
    }
    .divider-text::before { left: 0; }
    .divider-text::after { right: 0; }
    @media (max-width: 768px) {
      .auth-page { grid-template-columns: 1fr; }
      .auth-left { display: none; }
    }
  </style>
</head>
<body>

<div class="auth-page">
  <!-- Left decorative panel -->
  <div class="auth-left">
    <div class="auth-brand">
      <div class="logo-icon">🎯</div>
      <span class="brand-text">Grade<span>IQ</span></span>
    </div>
    <h1 class="auth-headline">Smart grading for <em>modern</em> classrooms.</h1>
    <p class="auth-desc">GradeIQ automates MCQ assessment — instant scores, detailed reports, and performance insights for educators and students.</p>
    <ul class="feature-list">
      <li><div class="fi">⚡</div>Instant automated grading</li>
      <li><div class="fi">📊</div>Detailed answer-by-answer reports</li>
      <li><div class="fi">🖨</div>Printable result certificates</li>
      <li><div class="fi">🔒</div>Separate admin &amp; student portals</li>
    </ul>
  </div>

  <!-- Right: Form panel -->
  <div class="auth-right">
    <div class="auth-form-box">

      <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom:20px">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($mode === 'login'): ?>
      <!-- ── LOGIN ── -->
      <div class="auth-title">Welcome back</div>
      <div class="auth-sub">Sign in to your GradeIQ account</div>

      <div class="role-tabs">
        <div class="role-tab active" id="loginStudentTab" onclick="setLoginRole('student')">
          <span class="rt-icon">👤</span>
          <span class="rt-label">Student</span>
        </div>
        <div class="role-tab" id="loginAdminTab" onclick="setLoginRole('admin')">
          <span class="rt-icon">🛡</span>
          <span class="rt-label">Admin</span>
        </div>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control"
            placeholder="your_username" required autofocus
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">Sign In →</button>
      </form>

      <div class="divider-text">or</div>
      <a href="login.php?mode=register" class="btn btn-secondary btn-full">Create an Account</a>

      <?php else: ?>
      <!-- ── REGISTER ── -->
      <div class="auth-title">Create Account</div>
      <div class="auth-sub">Join GradeIQ — choose your role below</div>

      <div class="role-tabs">
        <div class="role-tab active" id="regStudentTab" onclick="setRegRole('student')">
          <span class="rt-icon">👤</span>
          <span class="rt-label">Student</span>
        </div>
        <div class="role-tab <?= $adminCount >= MAX_ADMINS ? 'disabled' : '' ?>"
          id="regAdminTab" onclick="setRegRole('admin')"
          title="<?= $adminCount >= MAX_ADMINS ? 'Maximum admins reached' : '' ?>">
          <span class="rt-icon">🛡</span>
          <span class="rt-label">Admin <?= $adminCount >= MAX_ADMINS ? '(Full)' : "($adminCount/" . MAX_ADMINS . ")" ?></span>
        </div>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="role" id="regRole" value="student">

        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control"
            placeholder="Your full name" required
            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control"
              placeholder="username" required minlength="3"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>
          <div class="form-group" id="studentIdGroup" style="margin-bottom:0">
            <label class="form-label">Student ID
              <span style="color:var(--text-muted);font-size:0.72rem;text-transform:none;letter-spacing:0;font-weight:400">(optional)</span>
            </label>
            <input type="text" name="student_id" class="form-control"
              placeholder="STU-0001"
              value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group" style="margin-top:16px">
          <label class="form-label">Password
            <span style="color:var(--text-muted);font-size:0.72rem;text-transform:none;letter-spacing:0;font-weight:400">min. 6 characters</span>
          </label>
          <input type="password" name="password" class="form-control"
            placeholder="••••••••" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">Create Account →</button>
      </form>

      <div class="divider-text">already have an account?</div>
      <a href="login.php?mode=login" class="btn btn-secondary btn-full">Sign In</a>
      <?php endif; ?>

    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="assets/js/app.js"></script>
<script>
function setLoginRole(role) {
  document.getElementById('loginStudentTab').classList.toggle('active', role === 'student');
  document.getElementById('loginAdminTab').classList.toggle('active', role === 'admin');
}
function setRegRole(role) {
  <?php if ($adminCount >= MAX_ADMINS): ?>
  if (role === 'admin') { showToast('Maximum admin limit (<?= MAX_ADMINS ?>) reached.', 'error'); return; }
  <?php endif; ?>
  document.getElementById('regRole').value = role;
  document.getElementById('regStudentTab').classList.toggle('active', role === 'student');
  document.getElementById('regAdminTab').classList.toggle('active', role === 'admin');
  document.getElementById('studentIdGroup').style.display = role === 'admin' ? 'none' : '';
}
</script>
</body>
</html>
