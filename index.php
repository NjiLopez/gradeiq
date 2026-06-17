<?php
require_once 'includes/functions.php';
requireAdmin();
$pageTitle = 'Dashboard';
$stats = getGlobalStats();
$recentSessions = array_slice(array_reverse(getSessions()), 0, 6);
include 'includes/header.php';
?>

<main>
  <!-- Hero -->
  <section class="hero">
    <div class="wrapper">
      <div class="hero-label">✦ Automated Grading System</div>
      <h1>Assess. Grade. <em>Understand.</em></h1>
      <p>Upload answer sheets, define exam keys, and generate detailed performance reports in seconds. Built for educators who value speed and precision.</p>
    </div>
  </section>

  <div class="wrapper">
    <!-- Stats Bar -->
    <div class="stats-bar" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-item">
        <div class="stat-number"><?= $stats['exams'] ?></div>
        <div class="stat-label">Exam Banks</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $stats['students'] ?></div>
        <div class="stat-label">Students</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $stats['graded'] ?></div>
        <div class="stat-label">Papers Graded</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $stats['pass_rate'] ?>%</div>
        <div class="stat-label">Pass Rate</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:40px">
      <a href="create_exam.php" class="card" style="text-decoration:none;cursor:pointer">
        <div class="card-header">
          <div class="card-icon">📋</div>
          <div>
            <div class="card-title">Create Exam</div>
            <div class="card-subtitle">Define answer key & settings</div>
          </div>
        </div>
        <p style="font-size:0.85rem;color:var(--text-muted)">Set up a new exam bank with answer keys, point values, and metadata for future grading sessions.</p>
        <div style="margin-top:20px"><span class="btn btn-primary">Get Started →</span></div>
      </a>

      <a href="grade.php" class="card" style="text-decoration:none;cursor:pointer">
        <div class="card-header">
          <div class="card-icon">⚡</div>
          <div>
            <div class="card-title">Grade Papers</div>
            <div class="card-subtitle">Upload or enter answers</div>
          </div>
        </div>
        <p style="font-size:0.85rem;color:var(--text-muted)">Submit student answers manually or via CSV upload for instant automated scoring and feedback generation.</p>
        <div style="margin-top:20px"><span class="btn btn-secondary">Grade Now →</span></div>
      </a>

      <a href="results.php" class="card" style="text-decoration:none;cursor:pointer">
        <div class="card-header">
          <div class="card-icon">📊</div>
          <div>
            <div class="card-title">View Reports</div>
            <div class="card-subtitle">Scores & analytics</div>
          </div>
        </div>
        <p style="font-size:0.85rem;color:var(--text-muted)">Browse all graded sessions, review detailed breakdowns, identify weak areas, and export reports.</p>
        <div style="margin-top:20px"><span class="btn btn-secondary">Browse →</span></div>
      </a>
    </div>
    <!-- Manage Users shortcut -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
      <a href="manage_users.php" class="card" style="text-decoration:none">
        <div class="card-header">
          <div class="card-icon">👥</div>
          <div>
            <div class="card-title">Manage Users</div>
            <div class="card-subtitle">Admins & students</div>
          </div>
        </div>
        <p style="font-size:0.85rem;color:var(--text-muted)">View all registered students, reset passwords, and manage admin accounts.</p>
        <div style="margin-top:20px"><span class="btn btn-secondary">Manage →</span></div>
      </a>
      <div class="card">
        <div class="card-header">
          <div class="card-icon">📈</div>
          <div>
            <div class="card-title">Quick Stats</div>
            <div class="card-subtitle">System overview</div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;font-size:0.875rem">
          <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Total Students</span><span style="font-family:'DM Mono',monospace;color:var(--text-primary)"><?= $stats['students'] ?></span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Avg Score</span><span style="font-family:'DM Mono',monospace;color:var(--accent)"><?= $stats['avg_score'] ?>%</span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Pass Rate</span><span style="font-family:'DM Mono',monospace;color:var(--success)"><?= $stats['pass_rate'] ?>%</span></div>
          <div style="display:flex;justify-content:space-between"><span style="color:var(--text-muted)">Exam Banks</span><span style="font-family:'DM Mono',monospace;color:var(--text-primary)"><?= $stats['exams'] ?></span></div>
        </div>
      </div>
    </div>

    <!-- Recent Results -->
    <?php if (!empty($recentSessions)): ?>
    <div class="card" style="margin-bottom:48px">
      <div class="card-header">
        <div class="card-icon">🕐</div>
        <div>
          <div class="card-title">Recent Grading Sessions</div>
          <div class="card-subtitle">Latest submitted answer sheets</div>
        </div>
        <a href="results.php" class="btn btn-secondary" style="margin-left:auto">View All</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Exam</th>
              <th>Score</th>
              <th>Correct / Total</th>
              <th>Date</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentSessions as $session):
              $exam = getExam($session['exam_id']);
              $r = $session['result'];
            ?>
            <tr>
              <td class="cell-primary"><?= htmlspecialchars($session['student_name']) ?></td>
              <td><?= htmlspecialchars($exam['title'] ?? 'Unknown Exam') ?></td>
              <td>
                <span style="font-family:'DM Mono',monospace;font-weight:600;color:<?= $r['passed'] ? 'var(--success)' : 'var(--danger)' ?>">
                  <?= $r['percentage'] ?>%
                </span>
              </td>
              <td class="cell-mono"><?= $r['correct'] ?> / <?= $r['total'] ?></td>
              <td style="color:var(--text-muted);font-size:0.8rem"><?= date('M j, Y', strtotime($session['graded_at'])) ?></td>
              <td>
                <span class="badge <?= $r['passed'] ? 'badge-success' : 'badge-danger' ?>">
                  <?= $r['passed'] ? '✓ Pass' : '✕ Fail' ?>
                </span>
              </td>
              <td><a href="result_detail.php?id=<?= $session['id'] ?>" class="btn btn-secondary" style="padding:6px 14px;font-size:0.78rem">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
    <div class="card" style="text-align:center;padding:60px 32px;margin-bottom:48px">
      <div style="font-size:3rem;margin-bottom:16px">🎯</div>
      <h2 class="section-title" style="margin-bottom:8px">No Sessions Yet</h2>
      <p style="color:var(--text-muted);max-width:400px;margin:0 auto 28px">Start by creating an exam bank and grading your first answer sheet.</p>
      <a href="create_exam.php" class="btn btn-primary btn-lg">Create Your First Exam</a>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
