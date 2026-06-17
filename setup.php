<?php
require_once 'includes/functions.php';

if (countAdmins() > 0) {
    header('Location: login.php');
    exit;
}

$msg     = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $res = registerUser(
        trim($_POST['username']  ?? ''),
        trim($_POST['full_name'] ?? ''),
        $_POST['password']       ?? '',
        'admin'
    );
    if ($res['ok']) {
        $success = true;
        $msg     = 'Admin account created! You can now <a href="login.php">sign in</a>. Remember to delete setup.php.';
    } else {
        $msg = $res['msg'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GradeIQ Setup</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px">
<div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:40px;width:100%;max-width:420px">
  <div style="text-align:center;margin-bottom:32px">
    <div style="font-size:2.5rem;margin-bottom:12px">🎯</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;margin-bottom:6px">GradeIQ Setup</h1>
    <p style="color:var(--text-muted);font-size:0.875rem">Create the first administrator account</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>" style="margin-bottom:20px"><?= $msg ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <div class="alert alert-warning" style="margin-bottom:24px;font-size:0.82rem">
    ⚠ <strong>Security:</strong> Delete <code>setup.php</code> after creating your account.
  </div>
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Full Name</label>
      <input type="text" name="full_name" class="form-control" placeholder="Administrator Name" required>
    </div>
    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" placeholder="admin" required minlength="3">
    </div>
    <div class="form-group">
      <label class="form-label">Password
        <span style="color:var(--text-muted);font-size:0.72rem;text-transform:none;letter-spacing:0;font-weight:400">min. 6 characters</span>
      </label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
    </div>
    <button type="submit" name="create" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">Create Admin Account →</button>
  </form>
  <?php endif; ?>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
