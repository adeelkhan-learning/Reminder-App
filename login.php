<?php
session_start();
require 'db.php';

$error = "";
if (isset($_POST['login'])) {
    $user_input = $_POST['user'];
    $pass_input = $_POST['pass'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$user_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass_input, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="apple-touch-icon" href="apple-touch-icon.png?v=2">
    <link rel="icon" type="image/png" sizes="192x192" href="apple-touch-icon.png?v=2">
    <link rel="manifest" href="manifest.json">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reminders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h3 class="text-center mb-4 fw-bold text-dark">Family Reminder Login</h3>
                <?php if($error) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Username</label>
                        <input type="text" name="user" class="form-control form-control-lg" placeholder="Enter username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Password</label>
                        <input type="password" name="pass" class="form-control form-control-lg" placeholder="Enter password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary btn-lg w-100 shadow-sm">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>