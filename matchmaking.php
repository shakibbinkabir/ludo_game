<?php
// matchmaking.php
session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Add user to matchmaking queue if not already in it
$stmt = $conn->prepare("SELECT id FROM matchmaking_queue WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows == 0) {
    // Add user to matchmaking queue
    $stmt_insert = $conn->prepare("INSERT INTO matchmaking_queue (user_id) VALUES (?)");
    $stmt_insert->bind_param("i", $user_id);
    $stmt_insert->execute();
    $stmt_insert->close();
}
$stmt->close();

// Simple matchmaking logic: pair first two users
$stmt = $conn->prepare("SELECT user_id FROM matchmaking_queue ORDER BY requested_at ASC LIMIT 2");
$stmt->execute();
$result = $stmt->get_result();
$matched_users = [];
while ($row = $result->fetch_assoc()) {
    $matched_users[] = $row['user_id'];
}
$stmt->close();

if (count($matched_users) == 2) {
    // Create a new game
    $game_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    $stmt = $conn->prepare("INSERT INTO games (game_code, status) VALUES (?, 'ongoing')");
    $stmt->bind_param("s", $game_code);
    $stmt->execute();
    $game_id = $stmt->insert_id;
    $stmt->close();

    // Assign colors and add players to game_players
    $colors = ['red', 'blue'];
    foreach ($matched_users as $index => $user_id_matched) {
        $stmt = $conn->prepare("INSERT INTO game_players (game_id, user_id, color) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $game_id, $user_id_matched, $colors[$index]);
        $stmt->execute();
        $player_id = $stmt->insert_id;
        $stmt->close();

        // Initialize tokens for the player
        for ($i = 0; $i < 4; $i++) {
            $stmt_token = $conn->prepare("INSERT INTO game_tokens (game_id, player_id, color, position) VALUES (?, ?, ?, 0)");
            $stmt_token->bind_param("iis", $game_id, $player_id, $colors[$index]);
            $stmt_token->execute();
            $stmt_token->close();
        }
    }

    // Remove matched users from matchmaking_queue
    $stmt = $conn->prepare("DELETE FROM matchmaking_queue WHERE user_id IN (?, ?)");
    $stmt->bind_param("ii", $matched_users[0], $matched_users[1]);
    $stmt->execute();
    $stmt->close();

    // Notify players via WebSockets to join game_room.php?game_code=XYZ123
    // This can be handled via client-side WebSocket listeners

    // Optionally, redirect one of the players to the game room
    // All players should be notified via WebSockets
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Matchmaking - Ludo Game</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        // Polling to check if the game has been created
        setInterval(() => {
            fetch('check_match.php')
                .then(response => response.json())
                .then(data => {
                    if (data.game_code) {
                        window.location.href = 'game_room.php?game_code=' + data.game_code;
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 3000);
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Matchmaking</h2>
        <p>Looking for a match...</p>
    </div>
</body>
</html>
