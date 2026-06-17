<?php
require_once 'includes/functions.php';
requireAdmin();
$id = $_GET['id'] ?? '';
$exam = $id ? getExam($id) : null;
if (!$exam) { header('Location: exams.php'); exit; }
$pageTitle = 'Answer Key – ' . $exam['title'];
include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:36px">
      <a href="exams.php" class="btn btn-secondary" style="padding:8px 14px">← Exams</a>
      <div style="flex:1">
        <h1 class="section-title" style="margin-bottom:2px"><?= htmlspecialchars($exam['title']) ?></h1>
        <p style="color:var(--text-muted);font-size:0.875rem"><?= htmlspecialchars($exam['subject'] ?: 'No subject') ?> &mdash; <?= $exam['q_count'] ?> questions &mdash; Pass: <?= $exam['pass_threshold'] ?>%</p>
      </div>
      <a href="grade.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary">Grade with this Exam →</a>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-icon">🔑</div>
        <div>
          <div class="card-title">Answer Key</div>
          <div class="card-subtitle"><?= $exam['q_count'] ?> questions &mdash; Options A through E</div>
        </div>
        <button onclick="window.print()" class="btn btn-secondary no-print" style="margin-left:auto">🖨 Print Key</button>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px">
        <?php for ($i = 1; $i <= $exam['q_count']; $i++):
          $ans = $exam['answer_key'][$i] ?? '?';
          $qtext = $exam['questions'][$i] ?? '';
        ?>
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:10px;padding:14px 10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><div style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em">Q<?= $i ?></div><div style="font-family:'DM Mono',monospace;font-size:1.1rem;font-weight:700;color:var(--accent)"><?= htmlspecialchars($ans ?: '—') ?></div></div>
          <div style="font-size:0.9rem;color:var(--text-secondary)"><?= htmlspecialchars($qtext ?: '—') ?></div>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
