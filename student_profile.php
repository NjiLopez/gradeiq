<?php
require_once 'includes/functions.php';
requireAdmin();
$uid = $_GET['id'] ?? '';
$student = $uid ? getUserById($uid) : null;
if (!$student || $student['role'] !== 'student') { header('Location: manage_users.php'); exit; }

$sessions = array_reverse(getSessionsByUser($uid));
$total = count($sessions);
$passed = count(array_filter($sessions, fn($s) => $s['result']['passed']));
$avgPct = $total ? round(array_sum(array_column(array_column($sessions,'result'),'percentage'))/$total,1) : 0;
$pageTitle = 'Profile – ' . $student['full_name'];

include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:36px">
      <a href="manage_users.php" class="btn btn-secondary" style="padding:8px 14px">← Users</a>
      <div>
        <h1 class="section-title" style="margin-bottom:2px"><?= htmlspecialchars($student['full_name']) ?></h1>
        <p style="color:var(--text-muted);font-size:0.875rem">@<?= htmlspecialchars($student['username']) ?><?= $student['student_id'] ? ' — ' . htmlspecialchars($student['student_id']) : '' ?> — Registered <?= date('M j, Y', strtotime($student['created_at'])) ?></p>
      </div>
    </div>

    <div class="stats-bar" style="margin-bottom:32px">
      <div class="stat-item">
        <div class="stat-number"><?= $total ?></div>
        <div class="stat-label">Exams Taken</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" style="color:var(--success)"><?= $passed ?></div>
        <div class="stat-label">Passed</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $avgPct ?>%</div>
        <div class="stat-label">Avg Score</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-icon">📊</div>
        <div>
          <div class="card-title">Exam History</div>
          <div class="card-subtitle">All graded sessions for this student</div>
        </div>
      </div>

      <?php if (empty($sessions)): ?>
        <div style="text-align:center;padding:40px 0">
          <p style="color:var(--text-muted)">This student hasn't taken any exams yet.</p>
        </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Exam</th><th>Score</th><th>Correct</th><th>Wrong</th><th>Skipped</th><th>Grade</th><th>Date</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($sessions as $s):
              $exam = getExam($s['exam_id']);
              $r = $s['result'];
              $pct = $r['percentage'];
              $grade = $pct >= 90 ? 'A' : ($pct >= 80 ? 'B' : ($pct >= 70 ? 'C' : ($pct >= 60 ? 'D' : 'F')));
            ?>
            <tr>
              <td class="cell-primary"><?= htmlspecialchars($exam['title'] ?? 'Unknown') ?></td>
              <td><span style="font-family:'DM Mono',monospace;font-weight:700;color:<?= $r['passed']?'var(--success)':'var(--danger)' ?>"><?= $r['percentage'] ?>%</span></td>
              <td class="cell-mono" style="color:var(--success)"><?= $r['correct'] ?></td>
              <td class="cell-mono" style="color:var(--danger)"><?= $r['wrong'] ?></td>
              <td class="cell-mono" style="color:var(--warning)"><?= $r['skipped'] ?></td>
              <td><span style="font-family:'DM Mono',monospace;font-weight:700;font-size:1rem"><?= $grade ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-muted)"><?= date('M j, Y g:i A', strtotime($s['graded_at'])) ?></td>
              <td><span class="badge <?= $r['passed']?'badge-success':'badge-danger' ?>"><?= $r['passed']?'✓ Pass':'✕ Fail' ?></span></td>
              <td><a href="result_detail.php?id=<?= $s['id'] ?>" class="btn btn-secondary" style="padding:5px 12px;font-size:0.78rem">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
