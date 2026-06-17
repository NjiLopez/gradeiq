<?php
require_once 'includes/functions.php';
requireAdmin();
$pageTitle = 'Results';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session'])) {
    $sid = $_POST['session_id'] ?? '';
    if ($sid && deleteSession($sid)) {
        flash('success', 'Session deleted.');
    }
    header('Location: results.php');
    exit;
}

$exams = getExams();
$filterExamId = $_GET['exam_id'] ?? '';
$sessions = $filterExamId ? getSessionsByExam($filterExamId) : getSessions();
$sessions = array_reverse($sessions);

// Stats for filtered set
$total = count($sessions);
$passed = count(array_filter($sessions, fn($s) => $s['result']['passed']));
$avgPct = $total ? round(array_sum(array_column(array_column($sessions,'result'),'percentage'))/$total,1) : 0;

include 'includes/header.php';

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="gradeiq_results.csv"');
  $out = fopen('php://output','w');
  fputcsv($out, ['session_id','student_name','student_id','exam_id','exam_title','percentage','correct','wrong','skipped','passed','graded_at']);
  foreach ($sessions as $s) {
    $exam = getExam($s['exam_id']);
    $r = $s['result'];
    fputcsv($out, [
      $s['id'], $s['student_name'], $s['student_id'] ?? '', $s['exam_id'], $exam['title'] ?? '',
      $r['percentage'], $r['correct'], $r['wrong'], $r['skipped'], $r['passed'] ? '1' : '0', $s['graded_at']
    ]);
  }
  fclose($out);
  exit;
}
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:36px">
      <div>
        <h1 class="section-title" style="margin-bottom:4px">Grading Results</h1>
        <p style="color:var(--text-muted);font-size:0.875rem"><?= $total ?> session<?= $total !== 1 ? 's' : '' ?> found</p>
      </div>
      <div style="display:flex;gap:10px;align-items:center">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <select name="exam_id" class="form-control" style="width:220px" onchange="this.form.submit()">
            <option value="">All Exams</option>
            <?php foreach ($exams as $exam): ?>
            <option value="<?= $exam['id'] ?>" <?= $filterExamId === $exam['id'] ? 'selected' : '' ?>><?= htmlspecialchars($exam['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($filterExamId): ?>
            <a href="results.php" class="btn btn-secondary" style="padding:8px 12px">✕ Clear</a>
          <?php endif; ?>
        </form>
        <a href="results.php?export=csv" class="btn btn-secondary" style="margin-right:8px">⬇ Export CSV</a>
        <a href="grade.php" class="btn btn-primary">+ Grade New</a>
      </div>
    </div>

    <?php if ($total > 0): ?>
    <!-- Mini Stats -->
    <div class="stats-bar" style="margin-bottom:28px">
      <div class="stat-item">
        <div class="stat-number"><?= $total ?></div>
        <div class="stat-label">Sessions</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $total > 0 ? round(($passed/$total)*100) : 0 ?>%</div>
        <div class="stat-label">Pass Rate</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $avgPct ?>%</div>
        <div class="stat-label">Avg Score</div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
      <div class="card" style="text-align:center;padding:60px 32px">
        <div style="font-size:3rem;margin-bottom:16px">📊</div>
        <h2 class="section-title" style="margin-bottom:8px">No Results Yet</h2>
        <p style="color:var(--text-muted);margin-bottom:24px">Start grading student answer sheets to see results here.</p>
        <a href="grade.php" class="btn btn-primary btn-lg">Grade Answer Sheet</a>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>ID</th>
                <th>Exam</th>
                <th>Score</th>
                <th>Correct</th>
                <th>Wrong</th>
                <th>Skipped</th>
                <th>Date</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sessions as $session):
                $exam = getExam($session['exam_id']);
                $r = $session['result'];
              ?>
              <tr>
                <td class="cell-primary"><?= htmlspecialchars($session['student_name']) ?></td>
                <td class="cell-mono" style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($session['student_id'] ?: '—') ?></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($exam['title'] ?? 'Unknown') ?></td>
                <td>
                  <span style="font-family:'DM Mono',monospace;font-weight:700;color:<?= $r['passed'] ? 'var(--success)' : 'var(--danger)' ?>">
                    <?= $r['percentage'] ?>%
                  </span>
                </td>
                <td class="cell-mono" style="color:var(--success)"><?= $r['correct'] ?></td>
                <td class="cell-mono" style="color:var(--danger)"><?= $r['wrong'] ?></td>
                <td class="cell-mono" style="color:var(--warning)"><?= $r['skipped'] ?></td>
                <td style="color:var(--text-muted);font-size:0.78rem;white-space:nowrap"><?= date('M j, Y', strtotime($session['graded_at'])) ?></td>
                <td>
                  <span class="badge <?= $r['passed'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $r['passed'] ? '✓ Pass' : '✕ Fail' ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="result_detail.php?id=<?= $session['id'] ?>" class="btn btn-secondary" style="padding:6px 12px;font-size:0.78rem">View</a>
                    <form method="POST" style="margin:0">
                      <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                      <button type="submit" name="delete_session" class="btn btn-danger" style="padding:6px 10px;font-size:0.78rem" onclick="return confirm('Delete this session?')">🗑</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
