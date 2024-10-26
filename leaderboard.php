<?php
// leaderboard.php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard - Ludo Game</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Leaderboard</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Username</th>
                    <th>Wins</th>
                    <th>Losses</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT username, wins, losses FROM users ORDER BY wins DESC, losses ASC LIMIT 10");
                $stmt->execute();
                $result = $stmt->get_result();
                $rank = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $rank . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . $row['wins'] . "</td>";
                    echo "<td>" . $row['losses'] . "</td>";
                    echo "</tr>";
                    $rank++;
                }
                $stmt->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
