<?php
require_once 'includes/functions.php';
requireAdmin();
$pageTitle = 'Create Exam';
$error = '';
$success = '';

// Support edit 
$editing = false;
$existing = null;
$examId = $_GET['id'] ?? '';
if ($examId) {
  $existing = getExam($examId);
  if ($existing) $editing = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $qCount = (int)($_POST['q_count'] ?? 0);
  $answers = $_POST['answers'] ?? [];
  $questions = $_POST['questions'] ?? [];
  $duration = (int)($_POST['duration_minutes'] ?? 0);
  $passThreshold = (int)($_POST['pass_threshold'] ?? PASS_THRESHOLD);

  if (!$title || $qCount < 1 || $qCount > 500) {
    $error = 'Please provide a valid title and question count (1–500).';
  } else {
    $answerKey = [];
    for ($i = 1; $i <= $qCount; $i++) {
      $answerKey[$i] = strtoupper(trim($answers[$i] ?? ''));
      $questions[$i] = trim($questions[$i] ?? '');
    }

    // Check for CSV upload for answer key
    if (!empty($_FILES['csv_key']['name'])) {
      $csvPath = UPLOAD_DIR . uniqid('key_') . '.csv';
      move_uploaded_file($_FILES['csv_key']['tmp_name'], $csvPath);
      $csvAnswers = parseAnswerCSV($csvPath);
      unlink($csvPath);
      if (!empty($csvAnswers)) $answerKey = $csvAnswers;
    }

    $exam = [
      'id'             => $editing && $existing ? $existing['id'] : generateId(),
      'title'          => $title,
      'subject'        => $subject,
      'description'    => $description,
      'q_count'        => $qCount,
      'answer_key'     => $answerKey,
      'questions'      => $questions,
      'duration_minutes'=> $duration,
      'pass_threshold' => $passThreshold,
      'created_at'     => date('Y-m-d H:i:s'),
    ];

    if (saveExam($exam)) {
      flash('success', "Exam {$title} saved successfully!");
      header('Location: exams.php');
      exit;
    } else {
      $error = 'Failed to save the exam. Please check file permissions.';
    }
  }
}

include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:36px">
      <a href="exams.php" class="btn btn-secondary" style="padding:8px 14px">← Back</a>
      <div>
        <h1 class="section-title" style="margin-bottom:2px">Create New Exam</h1>
        <p class="section-sub" style="margin:0">Define the answer key and exam settings</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="main-grid">

        <!-- Left: Exam Details + Answer Key -->
        <div style="display:flex;flex-direction:column;gap:24px">
          <!-- Details Card -->
          <div class="card">
            <div class="card-header">
              <div class="card-icon">📋</div>
              <div>
                <div class="card-title">Exam Details</div>
                <div class="card-subtitle">Basic information and settings</div>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
              <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Exam Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g., Biology Midterm 2025" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Subject / Course</label>
                <input type="text" name="subject" class="form-control" placeholder="e.g., Biology 101" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Pass Threshold (%)</label>
                <input type="number" name="pass_threshold" class="form-control" min="0" max="100" value="<?= (int)($_POST['pass_threshold'] ?? ($existing['pass_threshold'] ?? PASS_THRESHOLD)) ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Duration (minutes) <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">(0 = no timer)</span></label>
                <input type="number" name="duration_minutes" class="form-control" min="0" max="1440" value="<?= (int)($_POST['duration_minutes'] ?? ($existing['duration_minutes'] ?? 0)) ?>">
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Description <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
                <textarea name="description" class="form-control" rows="2" placeholder="Any notes about this exam…" style="resize:vertical"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Number of Questions *</label>
                <input type="number" id="qCount" name="q_count" class="form-control" min="1" max="100" placeholder="e.g., 50" required value="<?= (int)($_POST['q_count'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- Questions Card -->
          <div class="card">
            <div class="card-header">
              <div class="card-icon">❓</div>
              <div>
                <div class="card-title">Questions</div>
                <div class="card-subtitle">Optional: enter the question text for each item</div>
              </div>
            </div>
            <div>
              <div id="questionsGrid" class="answer-grid">
                <p style="color:var(--text-muted);font-size:0.85rem;grid-column:1/-1">Enter the question count above to generate question fields.</p>
              </div>
            </div>
          </div>

          <!-- Answer Key Card -->
          <div class="card">
            <div class="card-header">
              <div class="card-icon">🔑</div>
              <div>
                <div class="card-title">Answer Key</div>
                <div class="card-subtitle">Set the correct answers for each question</div>
              </div>
            </div>

            <div data-tabs>
              <div class="tabs">
                <button type="button" class="tab-btn active" data-tab="manual">Manual Entry</button>
                <button type="button" class="tab-btn" data-tab="csv">CSV Upload</button>
              </div>

              <div class="tab-content active" data-tab-content="manual">
                <div class="alert alert-info">ℹ Enter the number of questions above, then select the correct answer (A–E) for each question below.</div>
                <div id="answersGrid" class="answer-grid">
                  <p style="color:var(--text-muted);font-size:0.85rem;grid-column:1/-1">Enter the question count above to generate the answer grid.</p>
                </div>
              </div>

              <div class="tab-content" data-tab-content="csv">
                <div class="alert alert-info">
                  ℹ Upload a CSV file where each row has: <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">question_number,answer</code>
                  <br><small>Example: <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">1,A</code> &nbsp; <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">2,C</code> &nbsp; <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">3,B</code></small>
                </div>
                <div class="upload-zone">
                  <input type="file" name="csv_key" accept=".csv,.txt">
                  <span class="upload-icon">📄</span>
                  <div class="upload-text">
                    <strong>Click or drag CSV file here</strong>
                    <small>Accepts .csv or .txt files</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Summary Sidebar -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div class="card" style="position:sticky;top:88px">
            <div class="card-header">
              <div class="card-icon">✦</div>
              <div>
                <div class="card-title">Exam Summary</div>
                <div class="card-subtitle">Review before saving</div>
              </div>
            </div>
            <div style="font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:14px">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <span>Questions</span>
                <span id="summaryCount" style="font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">—</span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center">
                <span>Pass Threshold</span>
                <span style="font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500"><?= (int)($_POST['pass_threshold'] ?? PASS_THRESHOLD) ?>%</span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center">
                <span>Options per Q</span>
                <span style="font-family:'DM Mono',monospace;color:var(--text-primary);font-weight:500">A – E</span>
              </div>
              <hr class="divider" style="margin:4px 0">
              <div class="alert alert-info" style="font-size:0.8rem">
                💡 After saving, students can be graded against this exam's answer key from the <strong>Grade</strong> page.
              </div>
            </div>
            <hr class="divider">
            <button type="submit" class="btn btn-primary btn-full btn-lg">
              Save Exam Bank
            </button>
            <a href="exams.php" class="btn btn-secondary btn-full" style="margin-top:10px">Cancel</a>
          </div>
        </div>
      </div>
    </form>
  </div>
</main>

<script>
// Update summary count live
document.getElementById('qCount').addEventListener('input', function() {
  document.getElementById('summaryCount').textContent = this.value || '—';
});
// Trigger on load if value exists
const qv = document.getElementById('qCount').value;
if (qv) document.getElementById('summaryCount').textContent = qv;
</script>

<?php
// Prefill answers/questions when editing or after a validation error
$prefKey = [];
$prefQues = [];
if (!empty($_POST['answers'])) $prefKey = $_POST['answers'];
elseif ($editing && !empty($existing['answer_key'])) $prefKey = $existing['answer_key'];

if (!empty($_POST['questions'])) $prefQues = $_POST['questions'];
elseif ($editing && !empty($existing['questions'])) $prefQues = $existing['questions'];
?>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
  const key = <?= json_encode($prefKey) ?> || {};
  const ques = <?= json_encode($prefQues) ?> || {};
  const n = parseInt(document.getElementById('qCount').value) || 0;
  if (n > 0) {
    if (document.getElementById('answersGrid')) buildAnswerGrid('answersGrid', n, 'answers');
    if (document.getElementById('questionsGrid')) buildQuestionsGrid('questionsGrid', n, 'questions');
    // set values after a small delay to allow elements to be created
    setTimeout(()=>{
      for (let i=1;i<=n;i++){
        const s = document.querySelector(`[name="answers[${i}]"]`);
        if (s && key[i]) s.value = key[i];
        const t = document.querySelector(`[name="questions[${i}]"]`);
        if (t && ques[i]) t.value = ques[i];
      }
    },100);
  }
});
</script>

<?php include 'includes/footer.php'; ?>
