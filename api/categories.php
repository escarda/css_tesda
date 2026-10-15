<?php
// api/categories.php
// CSS TESDA Reviewer - Categories API

require_once '../config.php';

// Always return JSON
header('Content-Type: application/json; charset=utf-8');

// Only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
        'data' => []
    ]);
    exit;
}

// Only GET is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.',
        'data' => []
    ]);
    exit;
}

try {

    /*
     * Get categories from the existing categories table.
     * This follows the same database structure used
     * by your existing categories.php.
     */
    $sql = "
        SELECT
            c.id,
            c.name,
            c.description,
            c.icon,
            COUNT(q.id) AS question_count
        FROM categories c
        LEFT JOIN questions q
            ON c.id = q.category_id
        GROUP BY
            c.id,
            c.name,
            c.description,
            c.icon
        ORDER BY c.id ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $categories = [];

    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'question_count' => (int)$row['question_count']
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Categories loaded successfully.',
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load categories.',
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}