<?php
require_once 'includes/functions.php';
requireLogin();
// Students can only view their own results
if (isStudent()) {
    $_checkId = $_GET['id'] ?? '';
    $_checkSess = getSession($_checkId);
    if (!$_checkSess || ($_checkSess['user_id'] ?? '') !== currentUserId()) {
        header('Location: student_dashboard.php'); exit;
    }
}
$id = $_GET['id'] ?? '';
$session = $id ? getSession($id) : null;
if (!$session) { header('Location: results.php'); exit; }
$exam = getExam($session['exam_id']);
$r = $session['result'];
$pageTitle = 'Report – ' . $session['student_name'];
$passClass = $r['passed'] ? 'pass' : 'fail';
include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <!-- Fresh submission banner for students -->
    <?php if (isset($_GET['fresh']) && isStudent()): ?>
    <div class="alert alert-success" style="margin-bottom:24px;font-size:0.95rem">
      🎉 <strong>Exam submitted successfully!</strong> Your answers have been graded. Here are your results.
    </div>
    <?php endif; ?>

    <!-- Back + Actions -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:36px" class="no-print">
      <?php if(isAdmin()): ?>
      <a href="results.php" class="btn btn-secondary" style="padding:8px 14px">← Results</a>
      <?php else: ?>
      <a href="student_dashboard.php" class="btn btn-secondary" style="padding:8px 14px">← Dashboard</a>
      <?php endif; ?>
      <div style="flex:1">
        <h1 class="section-title" style="margin-bottom:2px">Grading Report</h1>
        <p style="color:var(--text-muted);font-size:0.875rem">Generated <?= date('F j, Y \a\t g:i A', strtotime($session['graded_at'])) ?></p>
      </div>
      <button onclick="printReport()" class="btn btn-secondary">🖨 Print</button>
      <?php if(isAdmin()): ?><a href="grade.php?exam_id=<?= $session['exam_id'] ?>" class="btn btn-primary">Grade Another</a><?php else: ?><a href="student_dashboard.php" class="btn btn-primary">← My Dashboard</a><?php endif; ?>
    </div>

    <div class="main-grid">
      <!-- Left: Score + Breakdown + Review -->
      <div style="display:flex;flex-direction:column;gap:24px">

        <!-- Score Hero -->
        <div class="card result-hero <?= $passClass ?>" style="padding:48px 32px">
          <!-- Score Ring -->
          <div class="score-ring <?= $passClass ?>">
            <svg viewBox="0 0 140 140" width="160" height="160">
              <circle class="ring-track" cx="70" cy="70" r="60"/>
              <circle class="ring-fill" cx="70" cy="70" r="60"
                data-pct="<?= $r['percentage'] ?>"/>
            </svg>
            <div class="score-center">
              <div class="score-pct"><?= $r['percentage'] ?>%</div>
              <div class="score-label">Score</div>
            </div>
          </div>

          <div class="result-name"><?= htmlspecialchars($session['student_name']) ?></div>
          <div class="result-meta">
            <?php if ($session['student_id']): ?>Student ID: <?= htmlspecialchars($session['student_id']) ?> &mdash; <?php endif; ?>
            <?= htmlspecialchars($exam['title'] ?? 'Unknown Exam') ?>
          </div>

          <div style="margin-top:24px">
            <span class="badge <?= $r['passed'] ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.85rem;padding:8px 20px">
              <?= $r['passed'] ? '✓ PASSED' : '✕ FAILED' ?>
            </span>
          </div>
        </div>

        <!-- Score Breakdown -->
        <div class="card">
          <div class="card-header">
            <div class="card-icon">📊</div>
            <div>
              <div class="card-title">Score Breakdown</div>
              <div class="card-subtitle"><?= $r['total'] ?> questions total</div>
            </div>
          </div>

          <div class="breakdown-grid">
            <div class="breakdown-item">
              <div class="breakdown-value" style="color:var(--success)"><?= $r['correct'] ?></div>
              <div class="breakdown-key">Correct</div>
            </div>
            <div class="breakdown-item">
              <div class="breakdown-value" style="color:var(--danger)"><?= $r['wrong'] ?></div>
              <div class="breakdown-key">Wrong</div>
            </div>
            <div class="breakdown-item">
              <div class="breakdown-value" style="color:var(--warning)"><?= $r['skipped'] ?></div>
              <div class="breakdown-key">Skipped</div>
            </div>
          </div>

          <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
            <div>
              <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:6px">
                <span style="color:var(--success)">Correct</span>
                <span style="color:var(--text-muted)"><?= $r['correct'] ?> / <?= $r['total'] ?></span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill success" data-width="<?= round(($r['correct']/$r['total'])*100) ?>%"></div>
              </div>
            </div>
            <div>
              <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:6px">
                <span style="color:var(--danger)">Wrong</span>
                <span style="color:var(--text-muted)"><?= $r['wrong'] ?> / <?= $r['total'] ?></span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill danger" data-width="<?= round(($r['wrong']/$r['total'])*100) ?>%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Answer-by-Answer Review -->
        <div class="card">
          <div class="card-header">
            <div class="card-icon">🔍</div>
            <div>
              <div class="card-title">Answer Review</div>
              <div class="card-subtitle">Question-by-question feedback</div>
            </div>
          </div>

          <!-- Filter Tabs -->
          <div data-tabs>
            <div class="tabs" style="margin-bottom:20px">
              <button type="button" class="tab-btn active" data-tab="all-ans">All (<?= $r['total'] ?>)</button>
              <button type="button" class="tab-btn" data-tab="correct-ans">✓ Correct (<?= $r['correct'] ?>)</button>
              <button type="button" class="tab-btn" data-tab="wrong-ans">✕ Wrong (<?= $r['wrong'] ?>)</button>
              <button type="button" class="tab-btn" data-tab="skipped-ans">— Skipped (<?= $r['skipped'] ?>)</button>
            </div>

            <?php
            $groups = ['all' => $r['details'], 'correct' => [], 'wrong' => [], 'skipped' => []];
            foreach ($r['details'] as $d) { $groups[$d['status']][] = $d; }

            foreach (['all' => 'all-ans', 'correct' => 'correct-ans', 'wrong' => 'wrong-ans', 'skipped' => 'skipped-ans'] as $key => $tabId):
            ?>
            <div class="tab-content <?= $key === 'all' ? 'active' : '' ?>" data-tab-content="<?= $tabId ?>">
              <?php if (empty($groups[$key])): ?>
                <p style="color:var(--text-muted);text-align:center;padding:24px;font-size:0.875rem">No <?= $key ?> answers.</p>
              <?php else: ?>
              <div class="answer-review-grid">
                <?php foreach ($groups[$key] as $d): ?>
                <div class="answer-review-item <?= $d['status'] ?>">
                  <div class="qnum">Q<?= $d['question'] ?></div>
                  <div class="answer-details">
                    <?php if ($d['status'] === 'correct'): ?>
                      <div class="your-ans">✓ Correct: <strong style="color:var(--success)"><?= $d['student_answer'] ?></strong></div>
                    <?php elseif ($d['status'] === 'wrong'): ?>
                      <div class="your-ans">✕ You answered: <strong style="color:var(--danger)"><?= $d['student_answer'] ?: '—' ?></strong></div>
                      <div class="correct-ans">Correct: <strong><?= $d['correct_answer'] ?></strong></div>
                    <?php else: ?>
                      <div class="your-ans" style="color:var(--warning)">— Skipped</div>
                      <div class="correct-ans">Answer: <strong><?= $d['correct_answer'] ?></strong></div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Right Sidebar: Exam Info + Grading Details -->
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
          <div class="card-header">
            <div class="card-icon">📋</div>
            <div>
              <div class="card-title">Exam Details</div>
            </div>
          </div>
          <div style="font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:14px">
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Exam Title</div>
              <div style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($exam['title'] ?? '—') ?></div>
            </div>
            <?php if (!empty($exam['subject'])): ?>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Subject</div>
              <div><?= htmlspecialchars($exam['subject']) ?></div>
            </div>
            <?php endif; ?>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Questions</div>
              <div style="font-family:'DM Mono',monospace"><?= $r['total'] ?></div>
            </div>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Pass Threshold</div>
              <div style="font-family:'DM Mono',monospace"><?= $exam['pass_threshold'] ?? PASS_THRESHOLD ?>%</div>
            </div>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Raw Score</div>
              <div style="font-family:'DM Mono',monospace"><?= $r['correct'] ?> / <?= $r['total'] ?> pts</div>
            </div>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Percentage</div>
              <div style="font-family:'DM Mono',monospace;font-size:1.1rem;font-weight:700;color:<?= $r['passed'] ? 'var(--success)' : 'var(--danger)' ?>"><?= $r['percentage'] ?>%</div>
            </div>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Verdict</div>
              <span class="badge <?= $r['passed'] ? 'badge-success' : 'badge-danger' ?>"><?= $r['passed'] ? '✓ PASSED' : '✕ FAILED' ?></span>
            </div>
            <div>
              <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px">Graded At</div>
              <div style="font-size:0.82rem"><?= date('M j, Y g:i A', strtotime($session['graded_at'])) ?></div>
            </div>
          </div>
        </div>

        <?php
        // Quick grade letter
        $pct = $r['percentage'];
        $grade = $pct >= 90 ? 'A' : ($pct >= 80 ? 'B' : ($pct >= 70 ? 'C' : ($pct >= 60 ? 'D' : 'F')));
        $gradeColor = match($grade) { 'A' => 'var(--success)', 'B' => '#2ecc71', 'C' => 'var(--warning)', 'D' => 'var(--accent)', default => 'var(--danger)' };
        ?>
        <div class="card" style="text-align:center">
          <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:12px">Letter Grade</div>
          <div style="font-family:'Playfair Display',serif;font-size:5rem;font-weight:700;color:<?= $gradeColor ?>;line-height:1"><?= $grade ?></div>
          <div style="color:var(--text-muted);font-size:0.8rem;margin-top:8px"><?= $r['percentage'] ?>% — <?= $exam['q_count'] ?? $r['total'] ?> questions</div>
        </div>

        <div class="no-print" style="display:flex;flex-direction:column;gap:10px">
          <?php if(isAdmin()): ?><a href="grade.php?exam_id=<?= $session['exam_id'] ?>" class="btn btn-primary btn-full">Grade Another Student</a><?php else: ?><a href="take_exam.php" class="btn btn-primary btn-full">Take Another Exam</a><?php endif; ?>
          <?php if(isAdmin()): ?><a href="results.php?exam_id=<?= $session['exam_id'] ?>" class="btn btn-secondary btn-full">View All Results for Exam</a><?php endif; ?>
          <?php if(isAdmin()): ?><a href="view_exam.php?id=<?= $session['exam_id'] ?>" class="btn btn-secondary btn-full">🔑 View Answer Key</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
