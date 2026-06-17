<?php
// Output buffering — prevents "headers already sent" errors
if (!ob_get_level()) ob_start();

//  Bootstrap 
require_once __DIR__ . '/../config/config.php';

define('UPLOAD_DIR',     __DIR__ . '/../uploads/');
define('APP_NAME',       'GradeIQ');
define('APP_VERSION',    '2.2');
define('PASS_THRESHOLD', 50);
define('MAX_ADMINS',     3);

//Session — start exactly once 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Utilities 
function generateId(): string {
    return bin2hex(random_bytes(6));
}

//  User / Auth Functions 

function getUsers(): array {
    return db()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

function getUserByUsername(string $username): ?array {
    $st = db()->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $st->execute([$username]);
    $row = $st->fetch();
    return $row ?: null;
}

function getUserById(string $id): ?array {
    $st = db()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function countAdmins(): int {
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}

function registerUser(string $username, string $fullName, string $password, string $role, string $studentId = ''): array {
    if (!in_array($role, ['admin', 'student']))  return ['ok' => false, 'msg' => 'Invalid role.'];
    if (strlen(trim($username)) < 3)             return ['ok' => false, 'msg' => 'Username must be at least 3 characters.'];
    if (strlen(trim($fullName)) < 2)             return ['ok' => false, 'msg' => 'Please enter your full name.'];
    if (strlen($password) < 6)                   return ['ok' => false, 'msg' => 'Password must be at least 6 characters.'];
    if (getUserByUsername($username))             return ['ok' => false, 'msg' => 'Username already taken.'];
    if ($role === 'admin' && countAdmins() >= MAX_ADMINS)
        return ['ok' => false, 'msg' => 'Maximum admin limit (' . MAX_ADMINS . ') reached.'];

    $user = [
        'id'         => generateId(),
        'username'   => trim($username),
        'full_name'  => trim($fullName),
        'password'   => password_hash($password, PASSWORD_DEFAULT),
        'role'       => $role,
        'student_id' => trim($studentId),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $st = db()->prepare("
        INSERT INTO users (id, username, full_name, password, role, student_id, created_at)
        VALUES (:id, :username, :full_name, :password, :role, :student_id, :created_at)
    ");
    $st->execute($user);
    return ['ok' => true, 'user' => $user];
}

function loginUser(string $username, string $password): array {
    $user = getUserByUsername($username);
    if (!$user)                                         return ['ok' => false, 'msg' => 'Invalid username or password.'];
    if (!password_verify($password, $user['password'])) return ['ok' => false, 'msg' => 'Invalid username or password.'];
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    return ['ok' => true, 'user' => $user];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function isLoggedIn(): bool      { return !empty($_SESSION['user_id']); }
function isAdmin(): bool         { return ($_SESSION['user_role'] ?? '') === 'admin'; }
function isStudent(): bool       { return ($_SESSION['user_role'] ?? '') === 'student'; }
function currentUserId(): string { return $_SESSION['user_id'] ?? ''; }
function currentUser(): ?array   { return currentUserId() ? getUserById(currentUserId()) : null; }

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
}

function requireAdmin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
    if (!isAdmin())    { header('Location: student_dashboard.php'); exit; }
}

function requireStudent(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
    if (!isStudent())  { header('Location: index.php'); exit; }
}

function deleteUser(string $id): bool {
    $st = db()->prepare("DELETE FROM users WHERE id = ?");
    return $st->execute([$id]);
}

function updateUserPassword(string $id, string $newPassword): bool {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $st   = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $st->execute([$hash, $id]);
}

//  Exam Functions 
function getExams(): array {
    $rows = db()->query("SELECT * FROM exams ORDER BY created_at DESC")->fetchAll();
    return array_map('decodeExam', $rows);
}

function getExam(string $id): ?array {
    $st = db()->prepare("SELECT * FROM exams WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ? decodeExam($row) : null;
}

function decodeExam(array $row): array {
    $row['answer_key'] = json_decode($row['answer_key'], true) ?? [];
    $row['questions']  = isset($row['questions']) ? (json_decode($row['questions'], true) ?? []) : [];
    $row['duration_minutes'] = isset($row['duration_minutes']) ? (int)$row['duration_minutes'] : 0;
    return $row;
}

// Ensure exams table has the new columns (runs once if needed)
function ensureExamColumns(): void {
    try {
        $pdo = db();
        $st = $pdo->query("SHOW COLUMNS FROM exams LIKE 'questions'");
        $hasQuestions = (bool)$st->fetch();
        if (!$hasQuestions) {
            $pdo->exec("ALTER TABLE exams ADD COLUMN questions TEXT NULL AFTER answer_key, ADD COLUMN duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER questions");
        }
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../logs/migration.log', date('c') . " - migration failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
}

function examColumnsExist(): bool {
    try {
        $pdo = db();
        $st = $pdo->query("SHOW COLUMNS FROM exams LIKE 'questions'");
        return (bool)$st->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function saveExam(array $exam): bool {
    // Try to ensure the new columns exist, but allow old table setups to continue.
    ensureExamColumns();
    $answerKeyJson = json_encode($exam['answer_key'], JSON_UNESCAPED_UNICODE);
    $questionsJson = json_encode($exam['questions'] ?? [], JSON_UNESCAPED_UNICODE);
    $existing      = getExam($exam['id']);
    $hasColumns    = examColumnsExist();

    if ($existing) {
        if ($hasColumns) {
            $st = db()->prepare("
                UPDATE exams
                SET title=:title, subject=:subject, description=:description,
                    q_count=:q_count, answer_key=:answer_key, questions=:questions, duration_minutes=:duration_minutes, pass_threshold=:pass_threshold
                WHERE id=:id
            ");
            return $st->execute([
                ':id'               => $exam['id'],
                ':title'            => $exam['title'],
                ':subject'          => $exam['subject']         ?? '',
                ':description'      => $exam['description']     ?? '',
                ':q_count'          => $exam['q_count'],
                ':answer_key'       => $answerKeyJson,
                ':questions'        => $questionsJson,
                ':duration_minutes' => $exam['duration_minutes'] ?? 0,
                ':pass_threshold'   => $exam['pass_threshold']  ?? PASS_THRESHOLD,
            ]);
        }

        $st = db()->prepare("
            UPDATE exams
            SET title=:title, subject=:subject, description=:description,
                q_count=:q_count, answer_key=:answer_key, pass_threshold=:pass_threshold
            WHERE id=:id
        ");
        return $st->execute([
            ':id'             => $exam['id'],
            ':title'          => $exam['title'],
            ':subject'        => $exam['subject']         ?? '',
            ':description'    => $exam['description']     ?? '',
            ':q_count'        => $exam['q_count'],
            ':answer_key'     => $answerKeyJson,
            ':pass_threshold' => $exam['pass_threshold']  ?? PASS_THRESHOLD,
        ]);
    }

    if ($hasColumns) {
        $st = db()->prepare("
            INSERT INTO exams (id, title, subject, description, q_count, answer_key, questions, duration_minutes, pass_threshold, created_by, created_at)
            VALUES (:id, :title, :subject, :description, :q_count, :answer_key, :questions, :duration_minutes, :pass_threshold, :created_by, :created_at)
        ");
        return $st->execute([
            ':id'               => $exam['id'],
            ':title'            => $exam['title'],
            ':subject'          => $exam['subject']        ?? '',
            ':description'      => $exam['description']    ?? '',
            ':q_count'          => $exam['q_count'],
            ':answer_key'       => $answerKeyJson,
            ':questions'        => $questionsJson,
            ':duration_minutes' => $exam['duration_minutes'] ?? 0,
            ':pass_threshold'   => $exam['pass_threshold'] ?? PASS_THRESHOLD,
            ':created_by'       => currentUserId(),
            ':created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    $st = db()->prepare("
        INSERT INTO exams (id, title, subject, description, q_count, answer_key, pass_threshold, created_by, created_at)
        VALUES (:id, :title, :subject, :description, :q_count, :answer_key, :pass_threshold, :created_by, :created_at)
    ");
    return $st->execute([
        ':id'             => $exam['id'],
        ':title'          => $exam['title'],
        ':subject'        => $exam['subject']        ?? '',
        ':description'    => $exam['description']    ?? '',
        ':q_count'        => $exam['q_count'],
        ':answer_key'     => $answerKeyJson,
        ':pass_threshold' => $exam['pass_threshold'] ?? PASS_THRESHOLD,
        ':created_by'     => currentUserId(),
        ':created_at'     => date('Y-m-d H:i:s'),
    ]);
}

function deleteExam(string $id): bool {
    $st = db()->prepare("DELETE FROM exams WHERE id = ?");
    return $st->execute([$id]);
}

// Grading Logic (pure PHP — no DB needed) 

function gradeAnswers(array $answerKey, array $studentAnswers): array {
    $total   = count($answerKey);
    $correct = $wrong = $skipped = 0;
    $details = [];

    for ($i = 1; $i <= $total; $i++) {
        $key     = strtoupper(trim($answerKey[$i] ?? ''));
        $student = strtoupper(trim($studentAnswers[$i] ?? ''));

        if ($student === '' || $student === '-') {
            $status = 'skipped'; $skipped++;
        } elseif ($student === $key) {
            $status = 'correct'; $correct++;
        } else {
            $status = 'wrong'; $wrong++;
        }

        $details[$i] = [
            'question'       => $i,
            'correct_answer' => $key,
            'student_answer' => $student,
            'status'         => $status,
        ];
    }

    $pct = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    return [
        'total'      => $total,
        'correct'    => $correct,
        'wrong'      => $wrong,
        'skipped'    => $skipped,
        'percentage' => $pct,
        'passed'     => $pct >= PASS_THRESHOLD,
        'details'    => $details,
    ];
}

// Exam Session (attempt) Functions

function getSessions(): array {
    $rows = db()->query("SELECT * FROM sessions ORDER BY graded_at DESC")->fetchAll();
    return array_map('decodeSession', $rows);
}

function getSession(string $id): ?array {
    $st = db()->prepare("SELECT * FROM sessions WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ? decodeSession($row) : null;
}

function decodeSession(array $row): array {
    $row['answers']    = json_decode($row['answers'], true) ?? [];
    $row['result']     = json_decode($row['result'],  true) ?? [];
    // alias so templates using $s['student_id'] still work
    $row['student_id'] = $row['student_id_ref'] ?? '';
    return $row;
}

function saveSession(array $session): bool {
    $st = db()->prepare("
        INSERT INTO sessions (id, exam_id, user_id, student_name, student_id_ref, answers, result, graded_at)
        VALUES (:id, :exam_id, :user_id, :student_name, :student_id_ref, :answers, :result, :graded_at)
        ON DUPLICATE KEY UPDATE
            answers   = VALUES(answers),
            result    = VALUES(result),
            graded_at = VALUES(graded_at)
    ");
    return $st->execute([
        ':id'             => $session['id'],
        ':exam_id'        => $session['exam_id'],
        ':user_id'        => $session['user_id']    ?? '',
        ':student_name'   => $session['student_name'],
        ':student_id_ref' => $session['student_id'] ?? '',
        ':answers'        => json_encode($session['answers'], JSON_UNESCAPED_UNICODE),
        ':result'         => json_encode($session['result'],  JSON_UNESCAPED_UNICODE),
        ':graded_at'      => $session['graded_at']  ?? date('Y-m-d H:i:s'),
    ]);
}

function deleteSession(string $id): bool {
    $st = db()->prepare("DELETE FROM sessions WHERE id = ?");
    return $st->execute([$id]);
}

function getSessionsByExam(string $examId): array {
    $st = db()->prepare("SELECT * FROM sessions WHERE exam_id = ? ORDER BY graded_at DESC");
    $st->execute([$examId]);
    return array_map('decodeSession', $st->fetchAll());
}

function getSessionsByUser(string $userId): array {
    $st = db()->prepare("SELECT * FROM sessions WHERE user_id = ? ORDER BY graded_at DESC");
    $st->execute([$userId]);
    return array_map('decodeSession', $st->fetchAll());
}

function hasStudentAttempted(string $userId, string $examId): bool {
    $st = db()->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = ? AND exam_id = ?");
    $st->execute([$userId, $examId]);
    return (int)$st->fetchColumn() > 0;
}

// Dashboard Stats

function getGlobalStats(): array {
    $pdo = db();

    $totalSessions = (int)$pdo->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
    $passed = 0;
    $avgScore = 0.0;
    if ($totalSessions > 0) {
        $sessions = getSessions();
        $passed = count(array_filter($sessions, fn($s) => ($s['result']['passed'] ?? false)));
        $avgScore = round(array_sum(array_column(array_column($sessions, 'result'), 'percentage')) / $totalSessions, 1);
    }

    return [
        'exams'     => (int)$pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn(),
        'students'  => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
        'graded'    => $totalSessions,
        'pass_rate' => $totalSessions > 0 ? round(($passed / $totalSessions) * 100) : 0,
        'avg_score' => round($avgScore, 1),
    ];
}

// CSV Parser

function parseAnswerCSV(string $filePath): array {
    $answers = [];
    if (!file_exists($filePath)) return $answers;
    foreach (array_map('str_getcsv', file($filePath)) as $row) {
        if (count($row) >= 2) {
            $q = (int)trim($row[0]);
            $a = strtoupper(trim($row[1]));
            if ($q > 0) $answers[$q] = $a;
        }
    }
    return $answers;
}

// Flash Messages

function flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// Uploads dir
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);