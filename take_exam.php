<?php
require_once 'includes/functions.php';
requireStudent();
$user = currentUser();
$pageTitle = 'Take Exam';
$exams = getExams();
$selectedExamId = $_GET['exam_id'] ?? ($_POST['exam_id'] ?? '');
$selectedExam = $selectedExamId ? getExam($selectedExamId) : null;
$error = '';

// Exam timer: initialize start time in session and compute remaining seconds
$remainingSeconds = null;
if ($selectedExam && $selectedExam['duration_minutes'] > 0) {
  if (!isset($_SESSION['exam_start'])) $_SESSION['exam_start'] = [];
  if (empty($_SESSION['exam_start'][$selectedExam['id']])) {
    $_SESSION['exam_start'][$selectedExam['id']] = time();
  }
  $durationSec = $selectedExam['duration_minutes'] * 60;
  $elapsed = time() - (int)$_SESSION['exam_start'][$selectedExam['id']];
  $remainingSeconds = max(0, $durationSec - $elapsed);
}

// Filter out already-attempted exams
$mySessions = getSessionsByUser($user['id']);
$attemptedIds = array_column($mySessions, 'exam_id');
$availableExams = array_filter($exams, fn($e) => !in_array($e['id'], $attemptedIds));

if ($selectedExam && in_array($selectedExamId, $attemptedIds)) {
    // Student already attempted — redirect to their result
    foreach ($mySessions as $s) {
        if ($s['exam_id'] === $selectedExamId) {
            header('Location: result_detail.php?id=' . $s['id']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $examId = $_POST['exam_id'] ?? '';
    $exam = getExam($examId);

    if (!$exam) {
        $error = 'Exam not found.';
    } elseif (hasStudentAttempted($user['id'], $examId)) {
        $error = 'You have already attempted this exam.';
    } else {
        $raw = $_POST['student_answers'] ?? [];
        $studentAnswers = [];
        for ($i = 1; $i <= $exam['q_count']; $i++) {
            $studentAnswers[$i] = strtoupper(trim($raw[$i] ?? ''));
        }

        // server-side timer enforcement: if exam has duration, check start time
        if ($exam['duration_minutes'] > 0) {
          $start = $_SESSION['exam_start'][$exam['id']] ?? null;
          if ($start) {
            $elapsed = time() - (int)$start;
            if ($elapsed > ($exam['duration_minutes'] * 60)) {
              // allow submission but mark as auto-submitted in flash
              flash('error', 'Exam time expired — answers submitted at timeout.');
            }
            // clear start time to prevent reuse
            unset($_SESSION['exam_start'][$exam['id']]);
          }
        }

        $result = gradeAnswers($exam['answer_key'], $studentAnswers);

        $session = [
            'id'           => generateId(),
            'exam_id'      => $examId,
            'user_id'      => $user['id'],
            'student_name' => $user['full_name'],
            'student_id'   => $user['student_id'] ?? '',
            'answers'      => $studentAnswers,
            'result'       => $result,
            'graded_at'    => date('Y-m-d H:i:s'),
        ];

        if (saveSession($session)) {
            header('Location: result_detail.php?id=' . $session['id'] . '&fresh=1');
            exit;
        }
        $error = 'Failed to submit exam. Please try again.';
    }
}

include 'includes/header.php';
?>

<main style="padding:40px 0 60px">
  <div class="wrapper">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:36px">
      <a href="student_dashboard.php" class="btn btn-secondary" style="padding:8px 14px">← Dashboard</a>
      <div>
        <h1 class="section-title" style="margin-bottom:2px">Take an Exam</h1>
        <p style="color:var(--text-muted);font-size:0.875rem">Select an exam, answer all questions, then submit for instant grading.</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($availableExams) && !$selectedExam): ?>
      <div class="card" style="text-align:center;padding:60px 32px">
        <div style="font-size:3rem;margin-bottom:16px">🎉</div>
        <h2 class="section-title" style="margin-bottom:8px">All Exams Completed!</h2>
        <p style="color:var(--text-muted);margin-bottom:24px">You've already attempted all available exams. Check your results below.</p>
        <a href="student_dashboard.php" class="btn btn-primary btn-lg">View My Results</a>
      </div>
    <?php else: ?>

    <?php if (!$selectedExam): ?>
      <!-- Exam Selection Cards -->
      <div style="margin-bottom:12px;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600"><?= count($availableExams) ?> Available</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
        <?php foreach ($availableExams as $exam): ?>
        <div class="card">
          <div class="card-header">
            <div class="card-icon">📋</div>
            <div>
              <div class="badge badge-accent" style="margin-bottom:4px"><?= htmlspecialchars($exam['subject'] ?: 'General') ?></div>
              <div class="card-title"><?= htmlspecialchars($exam['title']) ?></div>
            </div>
          </div>
          <?php if ($exam['description']): ?>
            <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:16px;line-height:1.5"><?= htmlspecialchars($exam['description']) ?></p>
          <?php endif; ?>
          <div style="display:flex;gap:16px;font-size:0.82rem;color:var(--text-secondary);margin-bottom:20px">
            <span>📝 <?= $exam['q_count'] ?> Questions</span>
            <span>🎯 Pass: <?= $exam['pass_threshold'] ?>%</span>
          </div>
          <div class="alert alert-warning" style="font-size:0.8rem;margin-bottom:16px">
            ⚠ You can only attempt this exam once.
          </div>
          <a href="take_exam.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary btn-full">Start Exam →</a>
        </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Active Exam Form -->
      <form method="POST" id="examForm" onsubmit="return confirmSubmit()">
        <input type="hidden" name="submit_exam" value="1">
        <input type="hidden" name="exam_id" value="<?= $selectedExam['id'] ?>">

        <div class="main-grid">
          <div style="display:flex;flex-direction:column;gap:24px">

            <!-- Exam Header Card -->
            <div class="card" style="background:linear-gradient(135deg,var(--bg-card) 0%,var(--bg-card-hover) 100%)">
              <div style="display:flex;align-items:flex-start;gap:16px">
                <div class="card-icon" style="width:52px;height:52px;font-size:24px">📋</div>
                <div style="flex:1">
                  <div class="badge badge-accent" style="margin-bottom:8px"><?= htmlspecialchars($selectedExam['subject'] ?: 'General') ?></div>
                  <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;margin-bottom:6px"><?= htmlspecialchars($selectedExam['title']) ?></h2>
                  <?php if ($selectedExam['description']): ?>
                    <p style="font-size:0.85rem;color:var(--text-muted)"><?= htmlspecialchars($selectedExam['description']) ?></p>
                  <?php endif; ?>
                  <?php if ($selectedExam['duration_minutes'] > 0): ?>
                    <div class="alert alert-info" style="margin-top:12px">⏱ Time Remaining: <strong id="examTimer">--:--</strong></div>
                  <?php endif; ?>
                  <div class="alert alert-warning" style="margin-top:14px;font-size:0.82rem">
                    ⚠ This is a one-time attempt. Once submitted, you cannot re-take this exam.
                  </div>
                </div>
              </div>
            </div>

            <!-- Questions Card -->
            <div class="card">
              <div class="card-header">
                <div class="card-icon">✏️</div>
                <div>
                  <div class="card-title">Answer Sheet</div>
                  <div class="card-subtitle"><?= $selectedExam['q_count'] ?> questions — select one option each</div>
                </div>
                <div id="progressBadge" style="margin-left:auto;font-family:'DM Mono',monospace;font-size:0.8rem;color:var(--text-muted)">0 / <?= $selectedExam['q_count'] ?> answered</div>
              </div>

              <div id="questionList" style="display:flex;flex-direction:column;gap:12px">
                <?php for ($i = 1; $i <= $selectedExam['q_count']; $i++): ?>
                <div class="q-row" id="qrow_<?= $i ?>" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:10px;padding:16px;transition:var(--transition)">
                  <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                      <div style="font-family:'DM Mono',monospace;font-size:0.8rem;font-weight:600;color:var(--text-muted);min-width:28px">Q<?= $i ?></div>
                      <div style="font-size:0.95rem;font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($selectedExam['questions'][$i] ?? 'Question text not provided') ?></div>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                      <?php foreach (['A','B','C','D','E'] as $opt): ?>
                      <label style="cursor:pointer">
                        <input type="radio" name="student_answers[<?= $i ?>]" value="<?= $opt ?>"
                          onchange="markAnswered(<?= $i ?>)"
                          style="display:none">
                        <span class="opt-btn" data-q="<?= $i ?>" data-opt="<?= $opt ?>"
                          style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;border:1.5px solid var(--border);font-family:'DM Mono',monospace;font-weight:600;font-size:0.9rem;color:var(--text-secondary);transition:var(--transition);cursor:pointer;user-select:none">
                          <?= $opt ?>
                        </span>
                      </label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div>
            <div class="card" style="position:sticky;top:88px">
              <div class="card-header">
                <div class="card-icon">⚡</div>
                <div><div class="card-title">Exam Status</div></div>
              </div>

              <div style="font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:14px;margin-bottom:24px">
                <div style="display:flex;justify-content:space-between">
                  <span>Total Questions</span>
                  <span style="font-family:'DM Mono',monospace;color:var(--text-primary)"><?= $selectedExam['q_count'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                  <span>Answered</span>
                  <span id="answeredCount" style="font-family:'DM Mono',monospace;color:var(--success)">0</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                  <span>Remaining</span>
                  <span id="remainingCount" style="font-family:'DM Mono',monospace;color:var(--warning)"><?= $selectedExam['q_count'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                  <span>Pass Threshold</span>
                  <span style="font-family:'DM Mono',monospace;color:var(--accent)"><?= $selectedExam['pass_threshold'] ?>%</span>
                </div>
              </div>

              <div class="progress-bar" style="margin-bottom:24px;height:8px">
                <div class="progress-fill success" id="progressFill" data-width="0%" style="width:0%"></div>
              </div>

              <!-- Answer map -->
              <div style="margin-bottom:20px">
                <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px">Question Map</div>
                <div id="qMap" style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px">
                  <?php for ($i = 1; $i <= $selectedExam['q_count']; $i++): ?>
                  <div id="qmap_<?= $i ?>" title="Q<?= $i ?>"
                    style="height:24px;border-radius:4px;background:var(--border);font-size:0.6rem;display:flex;align-items:center;justify-content:center;color:var(--text-muted);cursor:pointer;transition:var(--transition)"
                    onclick="document.getElementById('qrow_<?= $i ?>').scrollIntoView({behavior:'smooth',block:'center'})">
                    <?= $i ?>
                  </div>
                  <?php endfor; ?>
                </div>
              </div>

              <hr class="divider">
              <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
                Submit Exam ⚡
              </button>
              <a href="take_exam.php" class="btn btn-secondary btn-full" style="margin-top:10px">← Choose Different Exam</a>
            </div>
          </div>
        </div>
      </form>

      <script>
      const totalQ = <?= $selectedExam['q_count'] ?>;
      let answered = new Set();

      function markAnswered(qNum) {
        answered.add(qNum);
        const row = document.getElementById('qrow_' + qNum);
        row.style.borderColor = 'rgba(46,204,113,0.4)';
        row.style.background = 'rgba(46,204,113,0.04)';
        document.getElementById('qmap_' + qNum).style.background = 'var(--success)';
        document.getElementById('qmap_' + qNum).style.color = '#fff';
        updateProgress();
        // Highlight selected option
        row.querySelectorAll('.opt-btn').forEach(b => {
          b.style.background = '';
          b.style.borderColor = 'var(--border)';
          b.style.color = 'var(--text-secondary)';
        });
        const checked = row.querySelector('input[type=radio]:checked');
        if (checked) {
          const sel = row.querySelector(`[data-opt="${checked.value}"]`);
          if (sel) {
            sel.style.background = 'var(--success)';
            sel.style.borderColor = 'var(--success)';
            sel.style.color = '#fff';
          }
        }
      }

      // Re-highlight on any radio change
      document.querySelectorAll('input[type=radio]').forEach(r => {
        r.addEventListener('change', function() {
          const q = this.name.match(/\d+/)[0];
          markAnswered(parseInt(q));
        });
      });

      function updateProgress() {
        const pct = Math.round((answered.size / totalQ) * 100);
        document.getElementById('answeredCount').textContent = answered.size;
        document.getElementById('remainingCount').textContent = totalQ - answered.size;
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progressBadge').textContent = answered.size + ' / ' + totalQ + ' answered';
      }

      function confirmSubmit() {
        const unanswered = totalQ - answered.size;
        if (unanswered > 0) {
          return confirm(`You have ${unanswered} unanswered question(s). Unanswered questions will be marked as skipped.\n\nSubmit anyway?`);
        }
        return confirm('Submit your exam for grading? You cannot change your answers after submission.');
      }
      </script>
      <?php if ($remainingSeconds !== null): ?>
      <script>
      // Timer countdown + auto-submit
      (function(){
        let remaining = <?= (int)$remainingSeconds ?>;
        const el = document.getElementById('examTimer');
        function fmt(s){
          const m = Math.floor(s/60); const sec = s%60; return String(m).padStart(2,'0')+':' + String(sec).padStart(2,'0');
        }
        if (el) el.textContent = fmt(remaining);
        const tid = setInterval(()=>{
          remaining--; if (remaining < 0) remaining = 0;
          if (el) el.textContent = fmt(remaining);
          if (remaining <= 0) {
            clearInterval(tid);
            // auto-submit the form
            const form = document.getElementById('examForm');
            if (form) {
              // notify user then submit
              alert('Time is up — your answers will be submitted automatically.');
              form.submit();
            }
          }
        }, 1000);
      })();
      </script>
      <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
