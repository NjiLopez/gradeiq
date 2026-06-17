<?php
require_once 'includes/functions.php';
requireAdmin();
$pageTitle = 'Manage Users';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_user') {
        $uid = $_POST['user_id'] ?? '';
        if ($uid === currentUserId()) {
            flash('error', 'You cannot delete your own account.');
        } elseif (deleteUser($uid)) {
            flash('success', 'User deleted successfully.');
        } else {
            flash('error', 'Failed to delete user.');
        }
    }

    if ($action === 'reset_password') {
        $uid = $_POST['user_id'] ?? '';
        $np  = trim($_POST['new_password'] ?? '');
        if (strlen($np) < 6) {
            flash('error', 'Password must be at least 6 characters.');
        } elseif (updateUserPassword($uid, $np)) {
            flash('success', 'Password reset successfully.');
        } else {
            flash('error', 'Failed to reset password.');
        }
    }

    header('Location: manage_users.php');
    exit;
}

$users = getUsers();
$students = array_values(array_filter($users, fn($u) => $u['role'] === 'student'));
$admins   = array_values(array_filter($users, fn($u) => $u['role'] === 'admin'));
$adminCount = count($admins);

include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:36px">
      <div>
        <h1 class="section-title" style="margin-bottom:4px">Manage Users</h1>
        <p style="color:var(--text-muted);font-size:0.875rem"><?= count($users) ?> total users — <?= $adminCount ?>/<?= MAX_ADMINS ?> admins</p>
      </div>
      <a href="login.php?mode=register" class="btn btn-primary" target="_blank">+ Add User</a>
    </div>

    <!-- Admins -->
    <div class="card" style="margin-bottom:24px">
      <div class="card-header">
        <div class="card-icon">🛡</div>
        <div>
          <div class="card-title">Administrators</div>
          <div class="card-subtitle"><?= $adminCount ?> of <?= MAX_ADMINS ?> admin slots used</div>
        </div>
        <div style="margin-left:auto;display:flex;gap:4px">
          <?php for ($i = 0; $i < MAX_ADMINS; $i++): ?>
          <div style="width:14px;height:14px;border-radius:50%;background:<?= $i < $adminCount ? 'var(--accent)' : 'var(--border)' ?>"></div>
          <?php endfor; ?>
        </div>
      </div>

      <?php if (empty($admins)): ?>
        <p style="color:var(--text-muted);font-size:0.875rem">No admins found.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>Username</th><th>Registered</th><th>Role</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($admins as $u): ?>
            <tr>
              <td class="cell-primary"><?= htmlspecialchars($u['full_name']) ?><?= $u['id'] === currentUserId() ? ' <span class="badge badge-accent" style="font-size:0.65rem">You</span>' : '' ?></td>
              <td class="cell-mono">@<?= htmlspecialchars($u['username']) ?></td>
              <td style="font-size:0.8rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td><span class="badge badge-warning">🛡 Admin</span></td>
              <td>
                <div style="display:flex;gap:6px">
                  <?php if ($u['id'] !== currentUserId()): ?>
                  <button onclick="showResetModal('<?= $u['id'] ?>','<?= htmlspecialchars($u['full_name']) ?>')" class="btn btn-secondary" style="padding:5px 10px;font-size:0.75rem">Reset PW</button>
                  <form method="POST" style="margin:0">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:0.75rem" onclick="return confirm('Delete this admin?')">🗑</button>
                  </form>
                  <?php else: ?>
                  <span style="color:var(--text-muted);font-size:0.8rem;padding:5px 0">Current session</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Students -->
    <div class="card">
      <div class="card-header">
        <div class="card-icon">👥</div>
        <div>
          <div class="card-title">Students</div>
          <div class="card-subtitle"><?= count($students) ?> registered students</div>
        </div>
      </div>

      <?php if (empty($students)): ?>
        <div style="text-align:center;padding:40px 0">
          <div style="font-size:2.5rem;margin-bottom:12px">👤</div>
          <p style="color:var(--text-muted);font-size:0.875rem">No students registered yet.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Name</th><th>Username</th><th>Student ID</th><th>Exams Taken</th><th>Avg Score</th><th>Registered</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($students as $u):
              $userSessions = getSessionsByUser($u['id']);
              $taken = count($userSessions);
              $avg = $taken ? round(array_sum(array_column(array_column($userSessions,'result'),'percentage'))/$taken,1) : null;
            ?>
            <tr>
              <td class="cell-primary"><?= htmlspecialchars($u['full_name']) ?></td>
              <td class="cell-mono">@<?= htmlspecialchars($u['username']) ?></td>
              <td style="color:var(--text-muted);font-size:0.82rem"><?= htmlspecialchars($u['student_id'] ?: '—') ?></td>
              <td class="cell-mono"><?= $taken ?></td>
              <td>
                <?php if ($avg !== null): ?>
                <span style="font-family:'DM Mono',monospace;font-weight:600;color:<?= $avg >= 50 ? 'var(--success)' : 'var(--danger)' ?>"><?= $avg ?>%</span>
                <?php else: ?>
                <span style="color:var(--text-muted)">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.8rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="student_profile.php?id=<?= $u['id'] ?>" class="btn btn-secondary" style="padding:5px 10px;font-size:0.75rem">Profile</a>
                  <button onclick="showResetModal('<?= $u['id'] ?>','<?= htmlspecialchars($u['full_name']) ?>')" class="btn btn-secondary" style="padding:5px 10px;font-size:0.75rem">Reset PW</button>
                  <form method="POST" style="margin:0">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-danger" style="padding:5px 10px;font-size:0.75rem" onclick="return confirm('Delete <?= htmlspecialchars($u['full_name']) ?> and all their data?')">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Reset Password Modal -->
<div id="resetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:36px;width:100%;max-width:400px;margin:20px">
    <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:6px">Reset Password</h3>
    <p id="resetModalName" style="color:var(--text-muted);font-size:0.85rem;margin-bottom:24px"></p>
    <form method="POST" id="resetForm">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="resetUserId">
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" minlength="6" required>
      </div>
      <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary" style="flex:1">Reset Password</button>
        <button type="button" onclick="hideResetModal()" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showResetModal(uid, name) {
  document.getElementById('resetUserId').value = uid;
  document.getElementById('resetModalName').textContent = 'Resetting password for: ' + name;
  document.getElementById('resetModal').style.display = 'flex';
}
function hideResetModal() {
  document.getElementById('resetModal').style.display = 'none';
}
document.getElementById('resetModal').addEventListener('click', function(e) {
  if (e.target === this) hideResetModal();
});
</script>

<?php include 'includes/footer.php'; ?>
