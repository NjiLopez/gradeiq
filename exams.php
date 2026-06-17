<?php
require_once 'includes/functions.php';
requireAdmin();
$pageTitle = 'Exam Bank';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exam'])) {
    $id = $_POST['exam_id'] ?? '';
    if ($id && deleteExam($id)) {
        flash('success', 'Exam deleted successfully.');
    } else {
        flash('error', 'Failed to delete exam.');
    }
    header('Location: exams.php');
    exit;
}

$exams = getExams();
include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:36px">
      <div>
        <h1 class="section-title" style="margin-bottom:4px">Exam Bank</h1>
        <p style="color:var(--text-muted);font-size:0.875rem"><?= count($exams) ?> exam<?= count($exams) !== 1 ? 's' : '' ?> stored</p>
      </div>
      <a href="create_exam.php" class="btn btn-primary">+ New Exam</a>
    </div>

    <?php if (empty($exams)): ?>
      <div class="card" style="text-align:center;padding:60px 32px">
        <div style="font-size:3rem;margin-bottom:16px">📋</div>
        <h2 class="section-title" style="margin-bottom:8px">No Exams Yet</h2>
        <p style="color:var(--text-muted);max-width:400px;margin:0 auto 28px">Create your first exam bank to start grading student answer sheets.</p>
        <a href="create_exam.php" class="btn btn-primary btn-lg">Create Exam</a>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">
        <?php foreach ($exams as $exam):
          $sessions = getSessionsByExam($exam['id']);
          $sessCount = count($sessions);
          $avgPct = $sessCount ? round(array_sum(array_column(array_column($sessions,'result'),'percentage'))/$sessCount,1) : null;
          $passed = count(array_filter($sessions, fn($s) => $s['result']['passed']));
        ?>
        <div class="card">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px">
            <div>
              <div class="badge badge-accent" style="margin-bottom:8px"><?= htmlspecialchars($exam['subject'] ?: 'General') ?></div>
              <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:600;line-height:1.3"><?= htmlspecialchars($exam['title']) ?></h3>
            </div>
            <div class="card-icon" style="margin-top:4px;flex-shrink:0">📋</div>
          </div>

          <?php if ($exam['description']): ?>
            <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:16px;line-height:1.5"><?= htmlspecialchars($exam['description']) ?></p>
          <?php endif; ?>

          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px">
            <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:10px;text-align:center">
              <div style="font-family:'DM Mono',monospace;font-size:1.1rem;font-weight:600;color:var(--text-primary)"><?= $exam['q_count'] ?></div>
              <div style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:2px">Questions</div>
            </div>
            <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:10px;text-align:center">
              <div style="font-family:'DM Mono',monospace;font-size:1.1rem;font-weight:600;color:var(--text-primary)"><?= $sessCount ?></div>
              <div style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:2px">Graded</div>
            </div>
            <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:8px;padding:10px;text-align:center">
              <div style="font-family:'DM Mono',monospace;font-size:1.1rem;font-weight:600;color:var(--accent)"><?= $avgPct !== null ? $avgPct.'%' : '—' ?></div>
              <div style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:2px">Avg Score</div>
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:6px;font-size:0.75rem;color:var(--text-muted);margin-bottom:20px">
            <span>Pass threshold: <strong style="color:var(--text-secondary)"><?= $exam['pass_threshold'] ?>%</strong></span>
            <span style="margin-left:auto">Created <?= date('M j, Y', strtotime($exam['created_at'])) ?></span>
          </div>

          <div style="display:flex;gap:8px">
            <a href="grade.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary" style="flex:1;justify-content:center">Grade →</a>
            <a href="view_exam.php?id=<?= $exam['id'] ?>" class="btn btn-secondary">View Key</a>
            <a href="create_exam.php?id=<?= $exam['id'] ?>" class="btn btn-secondary">Edit</a>
            <form method="POST" style="margin:0">
              <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
              <button type="submit" name="delete_exam" class="btn btn-danger"
                onclick="return confirm('Delete this exam and all its sessions?')">🗑</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
