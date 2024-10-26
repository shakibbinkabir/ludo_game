<?php
// login.php
session_start();
require_once 'includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email_or_username = trim($_POST['email_or_username']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email_or_username)) {
        $errors[] = "Email or Username is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Authenticate user
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email_or_username, $email_or_username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                // Authentication successful
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "No user found with that email or username.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Ludo Game</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="email_or_username">Email or Username:</label>
            <input type="text" name="email_or_username" id="email_or_username" required value="<?php echo isset($email_or_username) ? htmlspecialchars($email_or_username) : ''; ?>">

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
</body>
</html>
