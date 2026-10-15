<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="questions_export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['ID', 'Category ID', 'Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer', 'Explanation', 'Difficulty']);

// Get all questions with category names
$questions = $conn->query("
    SELECT q.id, q.category_id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, 
           q.correct_answer, q.explanation, q.difficulty, c.name as category_name
    FROM questions q
    JOIN categories c ON q.category_id = c.id
    ORDER BY q.id
");

// Add data rows
while ($row = $questions->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['category_id'] . ' - ' . $row['category_name'],
        $row['question_text'],
        $row['option_a'],
        $row['option_b'],
        $row['option_c'],
        $row['option_d'],
        $row['correct_answer'],
        $row['explanation'],
        $row['difficulty']
    ]);
}

fclose($output);
?>