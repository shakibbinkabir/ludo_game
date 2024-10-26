<?php
// check_match.php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user is part of any ongoing game
$stmt = $conn->prepare("SELECT g.game_code FROM games g JOIN game_players gp ON g.id = gp.game_id WHERE gp.user_id = ? AND g.status = 'ongoing'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($game_code);
if ($stmt->fetch()) {
    echo json_encode(['game_code' => $game_code]);
} else {
    echo json_encode([]);
}
$stmt->close();
?>
