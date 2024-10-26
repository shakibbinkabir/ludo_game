<?php
// game_lobby.php
session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$errors = [];

// Handle game creation
if (isset($_POST['create_game'])) {
    // Generate a unique game code
    $game_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    // Insert into games table with status 'waiting'
    $stmt = $conn->prepare("INSERT INTO games (game_code, status) VALUES (?, 'waiting')");
    $stmt->bind_param("s", $game_code);
    if ($stmt->execute()) {
        $game_id = $stmt->insert_id;

        // Assign a color to the creator
        $color = 'red'; // Assign first color
        $user_id = $_SESSION['user_id'];

        $stmt_player = $conn->prepare("INSERT INTO game_players (game_id, user_id, color) VALUES (?, ?, ?)");
        $stmt_player->bind_param("iis", $game_id, $user_id, $color);
        $stmt_player->execute();
        $player_id = $stmt_player->insert_id;
        $stmt_player->close();

        // Initialize tokens for the player
        for ($i = 0; $i < 4; $i++) {
            $stmt_token = $conn->prepare("INSERT INTO game_tokens (game_id, player_id, color, position) VALUES (?, ?, ?, 0)");
            $stmt_token->bind_param("iis", $game_id, $player_id, $color);
            $stmt_token->execute();
            $stmt_token->close();
        }

        header("Location: game_room.php?game_code=" . $game_code);
        exit();
    }
    $stmt->close();
}

// Handle joining a game
if (isset($_POST['join_game'])) {
    $join_code = strtoupper(trim($_POST['game_code']));
    // Validate game code
    $stmt = $conn->prepare("SELECT id, status FROM games WHERE game_code = ?");
    $stmt->bind_param("s", $join_code);
    $stmt->execute();
    $stmt->bind_result($game_id, $status);
    if ($stmt->fetch()) {
        if ($status === 'waiting') {
            // Assign next available color
            $stmt->close();

            $colors = ['red', 'blue', 'green', 'yellow'];
            $stmt_colors = $conn->prepare("SELECT color FROM game_players WHERE game_id = ?");
            $stmt_colors->bind_param("i", $game_id);
            $stmt_colors->execute();
            $result = $stmt_colors->get_result();
            $taken_colors = [];
            while ($row = $result->fetch_assoc()) {
                $taken_colors[] = $row['color'];
            }
            $stmt_colors->close();

            $available_colors = array_diff($colors, $taken_colors);
            if (!empty($available_colors)) {
                $color = array_shift($available_colors);
                $user_id = $_SESSION['user_id'];

                $stmt_player = $conn->prepare("INSERT INTO game_players (game_id, user_id, color) VALUES (?, ?, ?)");
                $stmt_player->bind_param("iis", $game_id, $user_id, $color);
                if ($stmt_player->execute()) {
                    $player_id = $stmt_player->insert_id;
                    $stmt_player->close();

                    // Initialize tokens for the player
                    for ($i = 0; $i < 4; $i++) {
                        $stmt_token = $conn->prepare("INSERT INTO game_tokens (game_id, player_id, color, position) VALUES (?, ?, ?, 0)");
                        $stmt_token->bind_param("iis", $game_id, $player_id, $color);
                        $stmt_token->execute();
                        $stmt_token->close();
                    }

                    // Check if the number of players is between 2 and 4
                    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM game_players WHERE game_id = ?");
                    $stmt_count->bind_param("i", $game_id);
                    $stmt_count->execute();
                    $stmt_count->bind_result($player_count);
                    $stmt_count->fetch();
                    $stmt_count->close();

                    if ($player_count >= 2) {
                        // Automatically start the game
                        $stmt_start = $conn->prepare("UPDATE games SET status = 'ongoing', current_turn = (SELECT id FROM game_players WHERE game_id = ? ORDER BY id ASC LIMIT 1) WHERE id = ?");
                        $stmt_start->bind_param("ii", $game_id, $game_id);
                        $stmt_start->execute();
                        $stmt_start->close();

                        // Notify players via WebSockets (handled separately)
                    }

                    header("Location: game_room.php?game_code=" . $join_code);
                    exit();
                }
                $stmt_player->close();
            } else {
                $errors[] = "Game is full.";
            }
        } else {
            $errors[] = "Game has already started or completed.";
        }
    } else {
        $errors[] = "Invalid Game Code.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Lobby - Ludo Game</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Game Lobby</h2>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="lobby-options">
            <form action="game_lobby.php" method="POST">
                <button type="submit" name="create_game">Create New Game</button>
            </form>
            <form action="game_lobby.php" method="POST">
                <label for="game_code">Join Existing Game:</label>
                <input type="text" name="game_code" id="game_code" maxlength="6" required>
                <button type="submit" name="join_game">Join Game</button>
            </form>
        </div>
    </div>
</body>
</html>
