<?php
// game_room.php
session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get game code from URL
if (!isset($_GET['game_code'])) {
    header("Location: game_lobby.php");
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
    header("Location: game_lobby.php");
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
    $players[] = $row;
}
$stmt->close();

// Determine if the user is part of the game
$user_in_game = false;
$user_color = '';
$user_name = '';
$player_id = 0;
foreach ($players as $player) {
    if ($player['username'] === $_SESSION['username']) {
        $user_in_game = true;
        $user_color = $player['color'];
        $user_name = $player['username'];
        $player_id = $player['id'];
        break;
    }
}

if (!$user_in_game) {
    header("Location: game_lobby.php");
    exit();
}

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

// Prepare data for initial game state
$game_state = [
    'players' => $players,
    'tokens' => $tokens,
    'current_turn' => $current_turn,
    'current_turn_username' => '',
    'status' => $status
];

// Identify the current turn player's username
if ($current_turn) {
    foreach ($players as $player) {
        if ($player['id'] == $current_turn) {
            $game_state['current_turn_username'] = $player['username'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Room - Ludo Game</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        const gameCode = "<?php echo $game_code; ?>";
        const userColor = "<?php echo $user_color; ?>";
        const userName = "<?php echo htmlspecialchars($user_name); ?>"; // Passed to JS
        const status = "<?php echo $status; ?>";
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Game Room: <?php echo htmlspecialchars($game_code); ?></h2>
        <div class="players">
            <h3>Players:</h3>
            <ul id="player-list">
                <?php foreach ($players as $player): ?>
                    <li><?php echo ucfirst($player['color']) . ": " . htmlspecialchars($player['username']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php if ($status === 'waiting'): ?>
            <p>Waiting for more players to join...</p>
        <?php else: ?>
            <div id="current-turn">Current Turn: <?php echo htmlspecialchars($game_state['current_turn_username']); ?></div>
            <canvas id="game-board" width="600" height="600"></canvas>
            <div class="controls">
                <button id="roll-dice">Roll Dice</button>
                <p id="dice-result"></p>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
