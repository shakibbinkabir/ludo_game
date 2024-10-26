<?php
// game_action.php
session_start();
require_once 'includes/db.php';

use Ratchet\Client\Connector;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Connector as ReactConnector;

require __DIR__ . '/vendor/autoload.php';

// Set Content-Type to JSON for all responses
header('Content-Type: application/json');

// Check if the request is via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['action']) || !isset($input['game_code']) || !isset($input['color'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid parameters.']);
    exit();
}

$action = $input['action'];
$game_code = strtoupper(trim($input['game_code']));
$color = strtolower(trim($input['color']));
$user_id = $_SESSION['user_id'];

// Fetch game details
$stmt = $conn->prepare("SELECT id, status, current_turn FROM games WHERE game_code = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("s", $game_code);
$stmt->execute();
$stmt->bind_result($game_id, $status, $current_turn);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Game not found.']);
    exit();
}
$stmt->close();

// Check if game is ongoing
if ($status !== 'ongoing') {
    http_response_code(400);
    echo json_encode(['error' => 'Game is not ongoing.']);
    exit();
}

// Fetch player details
$stmt = $conn->prepare("SELECT id FROM game_players WHERE game_id = ? AND color = ? AND user_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("isi", $game_id, $color, $user_id);
$stmt->execute();
$stmt->bind_result($player_id);
if (!$stmt->fetch()) {
    $stmt->close();
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You are not part of this game.']);
    exit();
}
$stmt->close();

// Check if it's the player's turn
if ($current_turn !== $player_id) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'It is not your turn.']);
    exit();
}

if ($action === 'roll_dice') {
    if (!isset($input['dice'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dice value missing.']);
        exit();
    }
    $dice = intval($input['dice']);

    // Fetch all tokens for the player
    $stmt = $conn->prepare("SELECT id, position FROM game_tokens WHERE player_id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tokens = [];
    while ($row = $result->fetch_assoc()) {
        $tokens[] = $row;
    }
    $stmt->close();

    // Determine which token to move
    $token_to_move = null;
    if ($dice == 6) {
        // Check if any token is in the base (position = 0)
        foreach ($tokens as $token) {
            if ($token['position'] == 0) {
                $token_to_move = $token;
                break;
            }
        }
    }

    if (!$token_to_move) {
        // Move the first token that can be moved
        foreach ($tokens as $token) {
            if ($token['position'] + $dice <= 57) { // Assuming 57 is the home position
                $token_to_move = $token;
                break;
            }
        }
    }

    if ($token_to_move) {
        $current_position = $token_to_move['position'];
        $new_position = $current_position + $dice;

        if ($new_position > 57) { // Cannot move beyond home
            echo json_encode(['error' => 'Invalid move.']);
            exit();
        }

        // Update token position
        $stmt = $conn->prepare("UPDATE game_tokens SET position = ? WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("ii", $new_position, $token_to_move['id']);
        $stmt->execute();
        $stmt->close();

        // Handle safe zones
        $safe_zones = [1, 14, 27, 40, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60];
        $is_safe = in_array($new_position, $safe_zones);

        if (!$is_safe) {
            // Check for capturing opponents
            $stmt = $conn->prepare("SELECT gt.id, gp.color FROM game_tokens gt JOIN game_players gp ON gt.player_id = gp.id WHERE gt.game_id = ? AND gt.position = ? AND gp.color != ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("iis", $game_id, $new_position, $color);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                // Send opponent's token back to start
                $stmt_reset = $conn->prepare("UPDATE game_tokens SET position = 0 WHERE id = ?");
                if (!$stmt_reset) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . $conn->error]);
                    exit();
                }
                $stmt_reset->bind_param("i", $row['id']);
                $stmt_reset->execute();
                $stmt_reset->close();

                // Log the capture
                $move_description = "Captured opponent's token at position $new_position";
                $stmt_log = $conn->prepare("INSERT INTO game_moves (game_id, player_id, move) VALUES (?, ?, ?)");
                if (!$stmt_log) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . $conn->error]);
                    exit();
                }
                $stmt_log->bind_param("iis", $game_id, $player_id, $move_description);
                $stmt_log->execute();
                $stmt_log->close();
            }
            $stmt->close();
        }

        // Log the movement
        if ($dice == 6 && $current_position == 0) {
            $move_description = "Entered token to starting position $new_position";
        } else {
            $move_description = "Moved token from $current_position to $new_position";
        }

        $stmt = $conn->prepare("INSERT INTO game_moves (game_id, player_id, move) VALUES (?, ?, ?)");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("iis", $game_id, $player_id, $move_description);
        $stmt->execute();
        $stmt->close();

        // Check if the player has won
        $stmt = $conn->prepare("SELECT COUNT(*) FROM game_tokens WHERE player_id = ? AND position = 57");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $stmt->bind_result($home_count);
        $stmt->fetch();
        $stmt->close();

        if ($home_count == 4) { // Assuming 4 tokens per player
            // Player has won
            $stmt = $conn->prepare("UPDATE games SET status = 'completed' WHERE id = ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $stmt->close();

            // Log the win
            $move_description = "Player has won the game!";
            $stmt = $conn->prepare("INSERT INTO game_moves (game_id, player_id, move) VALUES (?, ?, ?)");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("iis", $game_id, $player_id, $move_description);
            $stmt->execute();
            $stmt->close();

            // Update player statistics for leaderboard
            $stmt = $conn->prepare("UPDATE users SET wins = wins + 1 WHERE id = ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Update other players' losses
            $stmt = $conn->prepare("UPDATE users SET losses = losses + 1 WHERE id IN (SELECT user_id FROM game_players WHERE game_id = ?) AND id != ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("ii", $game_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // Notify players via WebSockets (handled separately)

            echo json_encode(['success' => 'You have won the game!']);
            exit();
        }

        // Determine if the player gets another turn (e.g., rolled a 6)
        if ($dice != 6) {
            // Fetch the list of players in turn order
            $stmt = $conn->prepare("SELECT id FROM game_players WHERE game_id = ? ORDER BY id ASC");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $player_ids = [];
            while ($row = $result->fetch_assoc()) {
                $player_ids[] = $row['id'];
            }
            $stmt->close();

            // Find the current player's index
            $current_index = array_search($player_id, $player_ids);
            $next_index = ($current_index + 1) % count($player_ids);
            $next_player_id = $player_ids[$next_index];

            // Update the current_turn in the 'games' table
            $stmt = $conn->prepare("UPDATE games SET current_turn = ? WHERE id = ?");
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("ii", $next_player_id, $game_id);
            $stmt->execute();
            $stmt->close();

            // Notify players of the turn change via WebSockets
            // (Implementation via WebSockets in server.php)
        }

        // Update last_roll
        $stmt = $conn->prepare("UPDATE games SET last_roll = ? WHERE id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("ii", $dice, $game_id);
        $stmt->execute();
        $stmt->close();

        // Notify via WebSockets
        // Establish a WebSocket connection to send the update
        $loop = LoopFactory::create();
        $connector = new Connector($loop);

        $connector('ws://localhost:8080')->then(function(Ratchet\Client\WebSocket $conn_ws) use ($game_code, $player_id, $dice, $game_id) {
            // Prepare the payload
            $payload = [
                'type' => 'update',
                'payload' => [
                    'action' => 'dice_roll',
                    'game_code' => $game_code,
                    'player_id' => $player_id,
                    'dice' => $dice,
                    // Include additional game state as needed
                ]
            ];
            $conn_ws->send(json_encode($payload));
            $conn_ws->close();
        }, function(\Exception $e) use ($game_code) {
            // Log the error; in production, consider logging to a file instead
            error_log("Could not connect to WebSocket server for game {$game_code}: {$e->getMessage()}");
        });

        $loop->run();

        echo json_encode(['success' => 'Move recorded.', 'extra_turn' => ($dice == 6)]);
        exit();
    }

// Handle other actions as needed
http_response_code(400);
echo json_encode(['error' => 'Invalid action.']);
exit();}
?>
