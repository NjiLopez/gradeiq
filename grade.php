<?php
require_once 'includes/functions.php';
requireAdmin();
$pageTitle = 'Grade Answer Sheet';
$exams = getExams();
$selectedExamId = $_GET['exam_id'] ?? ($_POST['exam_id'] ?? '');
$selectedExam = $selectedExamId ? getExam($selectedExamId) : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $examId = $_POST['exam_id'] ?? '';
    $studentName = trim($_POST['student_name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $exam = getExam($examId);

    if (!$exam) {
        $error = 'Selected exam not found.';
    } elseif (!$studentName) {
        $error = 'Please enter the student name.';
    } else {
        $studentAnswers = [];

        // CSV upload takes priority
        if (!empty($_FILES['csv_answers']['name'])) {
            $csvPath = UPLOAD_DIR . uniqid('ans_') . '.csv';
            move_uploaded_file($_FILES['csv_answers']['tmp_name'], $csvPath);
            $studentAnswers = parseAnswerCSV($csvPath);
            unlink($csvPath);
        } else {
            $raw = $_POST['student_answers'] ?? [];
            for ($i = 1; $i <= $exam['q_count']; $i++) {
                $studentAnswers[$i] = strtoupper(trim($raw[$i] ?? ''));
            }
        }

        $result = gradeAnswers($exam['answer_key'], $studentAnswers);

        $session = [
            'id'           => generateId(),
            'exam_id'      => $examId,
            'student_name' => $studentName,
            'student_id'   => $studentId,
            'answers'      => $studentAnswers,
            'result'       => $result,
            'graded_at'    => date('Y-m-d H:i:s'),
        ];

        if (saveSession($session)) {
            header('Location: result_detail.php?id=' . $session['id']);
            exit;
        } else {
            $error = 'Failed to save grading session.';
        }
    }
}

include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="margin-bottom:36px">
      <h1 class="section-title" style="margin-bottom:4px">Grade Answer Sheet</h1>
      <p style="color:var(--text-muted);font-size:0.875rem">Select an exam, enter student details, then submit answers manually or via CSV upload.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($exams)): ?>
      <div class="card" style="text-align:center;padding:48px 32px">
        <div style="font-size:3rem;margin-bottom:16px">📋</div>
        <h2 class="section-title" style="margin-bottom:8px">No Exam Banks Found</h2>
        <p style="color:var(--text-muted);margin-bottom:24px">You need to create an exam with an answer key before grading.</p>
        <a href="create_exam.php" class="btn btn-primary btn-lg">Create Exam Bank</a>
      </div>
    <?php else: ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="grade" value="1">
        <div class="main-grid">

          <!-- Left: Exam Select + Student Info + Answers -->
          <div style="display:flex;flex-direction:column;gap:24px">

            <!-- Exam Selection -->
            <div class="card">
              <div class="card-header">
                <div class="card-icon">📋</div>
                <div>
                  <div class="card-title">Select Exam</div>
                  <div class="card-subtitle">Choose the exam to grade against</div>
                </div>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Exam *</label>
                <select name="exam_id" id="examSelect" class="form-control" required onchange="this.form.submit()">
                  <option value="">— Choose an exam —</option>
                  <?php foreach ($exams as $exam): ?>
                  <option value="<?= $exam['id'] ?>" <?= $selectedExamId === $exam['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($exam['title']) ?> (<?= $exam['q_count'] ?> Qs)
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <?php if ($selectedExam): ?>
            <!-- Student Info -->
            <div class="card">
              <div class="card-header">
                <div class="card-icon">👤</div>
                <div>
                  <div class="card-title">Student Information</div>
                  <div class="card-subtitle">Identify the student being graded</div>
                </div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group" style="margin:0">
                  <label class="form-label">Full Name *</label>
                  <input type="text" name="student_name" class="form-control" placeholder="e.g., Jane Smith" required value="<?= htmlspecialchars($_POST['student_name'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0">
                  <label class="form-label">Student ID <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
                  <input type="text" name="student_id" class="form-control" placeholder="e.g., STU-20250001" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Answers -->
            <div class="card">
              <div class="card-header">
                <div class="card-icon">✏️</div>
                <div>
                  <div class="card-title">Student Answers</div>
                  <div class="card-subtitle">Enter answers for <?= $selectedExam['q_count'] ?> questions</div>
                </div>
              </div>

              <div data-tabs>
                <div class="tabs">
                  <button type="button" class="tab-btn active" data-tab="manual-ans">Manual Entry</button>
                  <button type="button" class="tab-btn" data-tab="csv-ans">CSV Upload</button>
                </div>

                <div class="tab-content active" data-tab-content="manual-ans">
                  <div class="alert alert-info">ℹ Select the student's answer for each question. Leave blank if a question was skipped.</div>
                  <div id="studentAnswersGrid" class="answer-grid"></div>
                  <script>
                    document.addEventListener('DOMContentLoaded', () => {
                      buildAnswerGrid('studentAnswersGrid', <?= $selectedExam['q_count'] ?>, 'student_answers');
                      <?php
                        // Re-fill answers if form was re-submitted
                        if (!empty($_POST['student_answers'])):
                          foreach ($_POST['student_answers'] as $qi => $ans):
                            $ans = htmlspecialchars($ans);
                            echo "setTimeout(()=>{const s=document.querySelector('[name=\"student_answers[{$qi}]\"]');if(s)s.value='{$ans}';},50);";
                          endforeach;
                        endif;
                      ?>
                    });
                  </script>
                </div>

                <div class="tab-content" data-tab-content="csv-ans">
                  <div class="alert alert-info">
                    ℹ Upload a CSV where each row is: <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">question_number,answer</code><br>
                    <small>Example: <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">1,B</code> &nbsp; <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:0.82rem">2,A</code></small>
                  </div>
                  <div class="upload-zone">
                    <input type="file" name="csv_answers" id="csvFile" accept=".csv,.txt">
                    <span class="upload-icon">📄</span>
                    <div class="upload-text">
                      <strong>Click or drag student answer CSV here</strong>
                      <small>Accepts .csv or .txt files</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Right Sidebar -->
          <div>
            <div class="card" style="position:sticky;top:88px">
              <div class="card-header">
                <div class="card-icon">⚡</div>
                <div>
                  <div class="card-title">Grading Info</div>
                </div>
              </div>

              <?php if ($selectedExam): ?>
              <div style="font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:14px;margin-bottom:24px">
                <div style="display:flex;justify-content:space-between">
                  <span>Exam</span>
                  <span style="color:var(--text-primary);font-weight:500;text-align:right;max-width:160px"><?= htmlspecialchars($selectedExam['title']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                  <span>Questions</span>
                  <span style="font-family:'DM Mono',monospace;color:var(--text-primary)"><?= $selectedExam['q_count'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                  <span>Pass Threshold</span>
                  <span style="font-family:'DM Mono',monospace;color:var(--accent)"><?= $selectedExam['pass_threshold'] ?>%</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                  <span>Scoring</span>
                  <span style="font-family:'DM Mono',monospace;color:var(--text-primary)">1 pt per Q</span>
                </div>
              </div>
              <hr class="divider">
              <button type="submit" class="btn btn-primary btn-full btn-lg">
                ⚡ Grade Now
              </button>
              <a href="view_exam.php?id=<?= $selectedExam['id'] ?>" class="btn btn-secondary btn-full" style="margin-top:10px" target="_blank">
                🔑 View Answer Key
              </a>
              <?php else: ?>
              <p style="color:var(--text-muted);font-size:0.85rem">Select an exam above to see grading details and submit answers.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
