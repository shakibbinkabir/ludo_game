<?php
// get_game_state.php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized.']);
    exit();
}

// Get game code from GET parameters
if (!isset($_GET['game_code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Game code missing.']);
    exit();
}

$game_code = strtoupper(trim($_GET['game_code']));

// Fetch game details
$stmt = $conn->prepare("SELECT id, status, current_turn FROM games WHERE game_code = ?");
$stmt->bind_param("s", $game_code);
$stmt->execute();
$stmt->bind_result($game_id, $status, $current_turn);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error' => 'Game not found.']);
    exit();
}
$stmt->close();

// Fetch players in the game
$stmt = $conn->prepare("SELECT gp.id, u.username, gp.color, gp.position FROM game_players gp JOIN users u ON gp.user_id = u.id WHERE gp.game_id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = [
        'id' => $row['id'],
        'username' => $row['username'],
        'color' => $row['color'],
        'position' => $row['position']
    ];
}
$stmt->close();

// Fetch tokens for the game
$stmt = $conn->prepare("SELECT gt.id, gp.color, gt.position, gp.user_id FROM game_tokens gt JOIN game_players gp ON gt.player_id = gp.id WHERE gt.game_id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$tokens = [];
while ($row = $result->fetch_assoc()) {
    $tokens[] = [
        'id' => $row['id'],
        'color' => $row['color'],
        'position' => $row['position'],
        'user_id' => $row['user_id']
    ];
}
$stmt->close();

// Identify the current turn player's username
$current_turn_username = '';
if ($current_turn) {
    foreach ($players as $player) {
        if ($player['id'] == $current_turn) {
            $current_turn_username = $player['username'];
            break;
        }
    }
}

$game_state = [
    'players' => $players,
    'tokens' => $tokens,
    'current_turn' => $current_turn,
    'current_turn_username' => $current_turn_username,
    'status' => $status
];

echo json_encode($game_state);
?>
