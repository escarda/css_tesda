<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$user_id = $_SESSION['user_id'];
$coc = intval($_GET['coc'] ?? 1);

// Clear session variables
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'answer_q') === 0 || strpos($key, 'show_feedback_q') === 0 || strpos($key, 'is_correct_q') === 0) {
        unset($_SESSION[$key]);
    }
}

// Clear database records
$delete_query = "DELETE FROM user_practice WHERE user_id = ? AND coc_number = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("ii", $user_id, $coc);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
?>