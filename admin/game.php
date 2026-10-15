<?php
// game.php - Redirect to COC Game Files

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get COC number from URL
$coc = isset($_GET['coc']) ? (int)$_GET['coc'] : 1;

// Redirect to the correct game file based on COC number
switch ($coc) {
    case 1:
        header("Location: coc1_game.php");
        break;
    case 2:
        header("Location: coc2_game.php");
        break;
    case 3:
        header("Location: coc3_game.php");
        break;
    case 4:
        header("Location: coc4_game.php");
        break;
    default:
        header("Location: coc1_game.php");
        break;
}
exit();
?>