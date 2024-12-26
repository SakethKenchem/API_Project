<?php
session_start();
require 'db.php';
require 'send_email.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email']; // Email input
    $username = $_POST['username']; // Username input
    $password = $_POST['password'];

    try {
        // Check if both email and username match a valid user
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND username = ?");
        $stmt->execute([$email, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists and match password
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                // Both email/username and password are valid
                $user_id = $user['id'];
                $otp = rand(100000, 999999);

                // Insert OTP into the database
                $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, otp_code) VALUES (?, ?)");
                $stmt->execute([$user_id, $otp]);

                // Send OTP to the user email
                if (sendEmail($user['email'], $otp)) {
                    $_SESSION['user_id'] = $user_id;
                    header("Location: verify_login_otp.php");
                    exit();
                } else {
                    $message = 'Failed to send OTP. Please try again.';
                }
            } else {
                $message = 'Invalid password.';
            }
        } else {
            // If no match for both email and username
            $message = 'Invalid email or username.';
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        function validateForm() {
            let isValid = true;

            // Validate email
            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            if (email.value.trim() === '') {
                emailError.textContent = 'Email is required.';
                isValid = false;
            } else {
                emailError.textContent = '';
            }

            // Validate username
            const username = document.getElementById('username');
            const usernameError = document.getElementById('usernameError');
            if (username.value.trim() === '') {
                usernameError.textContent = 'Username is required.';
                isValid = false;
            } else {
                usernameError.textContent = '';
            }

            // Validate password
            const password = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            if (password.value.trim() === '') {
                passwordError.textContent = 'Password is required.';
                isValid = false;
            } else {
                passwordError.textContent = '';
            }

            return isValid;
        }
    </script>
</head>

<body>
    <div class="container">
        <h2>Login</h2>
        <form onsubmit="return validateForm()" method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
                <div id="emailError" class="text-danger"></div>
            </div>
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username">
                <div id="usernameError" class="text-danger"></div>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password">
                <div id="passwordError" class="text-danger"></div>
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