<?php
// index.php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Ludo Game</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Welcome to Online Multiplayer Ludo!</h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Ready to play? <a href="matchmaking.php">Find a Match</a> or <a href="game_lobby.php">Join a Game</a>.</p>
        <?php else: ?>
            <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to start playing.</p>
        <?php endif; ?>
    </div>
</body>
</html>
