<?php
session_start();
require 'db.php'; 
require 'send_email.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Verify user credentials
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // User found and password matches, generate OTP
            $user_id = $user['id'];
            $otp = rand(100000, 999999);
            
            
            $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, otp_code) VALUES (?, ?)");
            $stmt->execute([$user_id, $otp]);

            // Send OTP to the user
            if (sendEmail($email, $otp)) {
                $_SESSION['user_id'] = $user_id; 
                header("Location: verify_login_otp.php"); 
                exit();
            } else {
                $message = 'Failed to send OTP. Please try again.';
            }
        } else {
            $message = 'Invalid email or password.';
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
<div class="container mt-5">
    <h2>Login</h2>
    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>

        <p class="mt-3">Don't have an account? <a href="signup.php">Sign up</a></p>
    </form>
    <?php if ($message): ?>
        <div class="alert alert-danger mt-3">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
