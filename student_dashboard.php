<?php
require_once 'includes/functions.php';
requireStudent();
$user = currentUser();
$mySessions = array_reverse(getSessionsByUser($user['id']));
$exams = getExams();
$pageTitle = 'My Dashboard';

// Stats
$myTotal = count($mySessions);
$myPassed = count(array_filter($mySessions, fn($s) => $s['result']['passed']));
$myAvg = $myTotal ? round(array_sum(array_column(array_column($mySessions,'result'),'percentage'))/$myTotal,1) : 0;

// Which exams are available (not yet attempted)
$attemptedExamIds = array_column($mySessions, 'exam_id');
$availableExams = array_filter($exams, fn($e) => !in_array($e['id'], $attemptedExamIds));

include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">

    <!-- Student Welcome -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:40px">
      <div>
        <div class="hero-label" style="margin-bottom:10px">👤 Student Portal</div>
        <h1 style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;margin-bottom:4px">
          Welcome, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>
        </h1>
        <p style="color:var(--text-muted);font-size:0.875rem">
          @<?= htmlspecialchars($user['username']) ?>
          <?php if ($user['student_id']): ?> &mdash; <?= htmlspecialchars($user['student_id']) ?><?php endif; ?>
        </p>
      </div>
      <a href="take_exam.php" class="btn btn-primary btn-lg">✏️ Take an Exam</a>
    </div>

    <!-- Stats -->
    <div class="stats-bar" style="margin-bottom:36px">
      <div class="stat-item">
        <div class="stat-number"><?= $myTotal ?></div>
        <div class="stat-label">Exams Taken</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" style="color:var(--success)"><?= $myPassed ?></div>
        <div class="stat-label">Passed</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $myAvg ?>%</div>
        <div class="stat-label">Avg Score</div>
      </div>
    </div>

    <div class="main-grid">
      <!-- Left: History -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="card-icon">📊</div>
            <div>
              <div class="card-title">My Results</div>
              <div class="card-subtitle">All exams you've submitted</div>
            </div>
          </div>

          <?php if (empty($mySessions)): ?>
            <div style="text-align:center;padding:40px 0">
              <div style="font-size:2.5rem;margin-bottom:12px">📝</div>
              <p style="color:var(--text-muted);font-size:0.875rem">You haven't taken any exams yet.</p>
              <a href="take_exam.php" class="btn btn-primary" style="margin-top:16px">Take Your First Exam →</a>
            </div>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Exam</th><th>Score</th><th>Correct/Total</th><th>Date</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                  <?php foreach ($mySessions as $s):
                    $exam = getExam($s['exam_id']);
                    $r = $s['result'];
                  ?>
                  <tr>
                    <td class="cell-primary"><?= htmlspecialchars($exam['title'] ?? 'Unknown') ?></td>
                    <td><span style="font-family:'DM Mono',monospace;font-weight:700;color:<?= $r['passed']?'var(--success)':'var(--danger)' ?>"><?= $r['percentage'] ?>%</span></td>
                    <td class="cell-mono"><?= $r['correct'] ?>/<?= $r['total'] ?></td>
                    <td style="color:var(--text-muted);font-size:0.78rem"><?= date('M j, Y', strtotime($s['graded_at'])) ?></td>
                    <td><span class="badge <?= $r['passed']?'badge-success':'badge-danger' ?>"><?= $r['passed']?'✓ Pass':'✕ Fail' ?></span></td>
                    <td><a href="result_detail.php?id=<?= $s['id'] ?>" class="btn btn-secondary" style="padding:5px 12px;font-size:0.78rem">View →</a></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: Available Exams -->
      <div>
        <div class="card">
          <div class="card-header">
            <div class="card-icon">📋</div>
            <div>
              <div class="card-title">Available Exams</div>
              <div class="card-subtitle"><?= count($availableExams) ?> waiting for you</div>
            </div>
          </div>

          <?php if (empty($availableExams)): ?>
            <div style="text-align:center;padding:32px 0">
              <div style="font-size:2rem;margin-bottom:10px">🎉</div>
              <p style="color:var(--text-muted);font-size:0.85rem">You've completed all available exams!</p>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px">
              <?php foreach ($availableExams as $exam): ?>
              <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:10px;padding:16px">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
                  <div>
                    <div class="badge badge-accent" style="margin-bottom:6px;font-size:0.68rem"><?= htmlspecialchars($exam['subject'] ?: 'General') ?></div>
                    <div style="font-weight:600;font-size:0.9rem;color:var(--text-primary);margin-bottom:4px"><?= htmlspecialchars($exam['title']) ?></div>
                    <div style="font-size:0.78rem;color:var(--text-muted)"><?= $exam['q_count'] ?> questions &mdash; Pass: <?= $exam['pass_threshold'] ?>%</div>
                  </div>
                  <a href="take_exam.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary" style="padding:8px 14px;font-size:0.8rem;white-space:nowrap;flex-shrink:0">Start →</a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($mySessions)):
          // Mini performance chart
          $latest = $mySessions[0];
          $exam = getExam($latest['exam_id']);
          $r = $latest['result'];
        ?>
        <div class="card" style="margin-top:16px">
          <div class="card-header">
            <div class="card-icon">🏆</div>
            <div>
              <div class="card-title">Latest Result</div>
              <div class="card-subtitle"><?= htmlspecialchars($exam['title'] ?? '') ?></div>
            </div>
          </div>
          <div style="text-align:center;padding:16px 0">
            <?php
            $pct = $r['percentage'];
            $grade = $pct >= 90 ? 'A' : ($pct >= 80 ? 'B' : ($pct >= 70 ? 'C' : ($pct >= 60 ? 'D' : 'F')));
            $gc = match($grade) { 'A','B' => 'var(--success)', 'C' => 'var(--warning)', 'D' => 'var(--accent)', default => 'var(--danger)' };
            ?>
            <div style="font-family:'Playfair Display',serif;font-size:4rem;font-weight:700;color:<?= $gc ?>;line-height:1"><?= $grade ?></div>
            <div style="font-size:0.85rem;color:var(--text-muted);margin-top:6px"><?= $r['percentage'] ?>% — <?= $r['correct'] ?>/<?= $r['total'] ?> correct</div>
            <div style="margin-top:12px"><span class="badge <?= $r['passed']?'badge-success':'badge-danger' ?>"><?= $r['passed']?'✓ PASSED':'✕ FAILED' ?></span></div>
          </div>
          <a href="result_detail.php?id=<?= $latest['id'] ?>" class="btn btn-secondary btn-full" style="margin-top:8px">Full Report →</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
