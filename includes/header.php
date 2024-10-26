<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <nav>
        <a href="index.php">Home</a>
        <a href="leaderboard.php">Leaderboard</a>
        <a href="matchmaking.php">Matchmaking</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>
