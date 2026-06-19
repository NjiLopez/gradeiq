<?php
require_once 'includes/functions.php';
requireAdmin();

$examId = $_GET['id'] ?? '';
if (!$examId) {
    http_response_code(400);
    exit('Missing exam ID');
}

$exam = getExam($examId);
if (!$exam) {
    http_response_code(404);
    exit('Exam not found');
}

// Prepare data for download
$downloadData = [
    'title' => $exam['title'],
    'subject' => $exam['subject'],
    'description' => $exam['description'],
    'total_questions' => $exam['q_count'],
    'pass_threshold' => $exam['pass_threshold'],
    'created_at' => $exam['created_at'],
    'questions' => $exam['questions'] ?? [],
    'answer_key' => $exam['answer_key'] ?? []
];

// Format questions with answers
$questionsWithAnswers = [];
foreach ($downloadData['questions'] as $index => $question) {
    $qNum = $index + 1;
    $questionsWithAnswers[] = [
        'question_number' => $qNum,
        'question_text' => $question['text'] ?? '',
        'question_type' => $question['type'] ?? 'multiple_choice',
        'options' => $question['options'] ?? [],
        'correct_answer' => $downloadData['answer_key'][$qNum] ?? ''
    ];
}

$downloadData['questions_with_answers'] = $questionsWithAnswers;

$format = $_GET['format'] ?? 'json';

if ($format === 'csv') {
    // CSV format
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($exam['title']) . '_questions_answers.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header info
    fputcsv($output, ['Exam Title:', $exam['title']]);
    fputcsv($output, ['Subject:', $exam['subject']]);
    fputcsv($output, ['Description:', $exam['description']]);
    fputcsv($output, ['Total Questions:', $exam['q_count']]);
    fputcsv($output, ['Pass Threshold:', $exam['pass_threshold'] . '%']);
    fputcsv($output, ['Created:', $exam['created_at']]);
    fputcsv($output, []);
    
    // Questions header
    fputcsv($output, ['Q#', 'Question', 'Type', 'Options', 'Correct Answer']);
    
    // Questions data
    foreach ($questionsWithAnswers as $q) {
        $options = is_array($q['options']) ? implode(' | ', $q['options']) : '';
        fputcsv($output, [
            $q['question_number'],
            $q['question_text'],
            $q['question_type'],
            $options,
            $q['correct_answer']
        ]);
    }
    
    fclose($output);
} else {
    // JSON format (default)
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($exam['title']) . '_questions_answers.json"');
    
    echo json_encode($downloadData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

exit;

function sanitizeFilename(string $filename): string {
    $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', $filename);
    return trim($filename ?: 'exam') . '_' . date('Y-m-d-His');
}
?>
