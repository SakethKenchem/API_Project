<?php
require 'db.php';
require 'send_email.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // Check if the username or email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existingCount = $stmt->fetchColumn();

        if ($existingCount > 0) {
            $message = '<div class=\"text-danger\">Username or email already in use. Please choose a different one.</div>';
        } else {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);
            $user_id = $conn->lastInsertId();

            // Generate OTP
            $otp = rand(100000, 999999);
            $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, otp_code) VALUES (?, ?)");
            $stmt->execute([$user_id, $otp]);

            // Send OTP email
            if (sendEmail($email, $otp)) {
                header("Location: verify_otp.php?type=signup&user_id=$user_id");
                exit();
            } else {
                $message = 'Failed to send OTP. Please try again.';
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>

<!-- Signup Page HTML -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        function validateForm() {
            let isValid = true;

            // Validate email
            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailPattern.test(email.value)) {
                emailError.textContent = 'Please enter a valid email address.';
                isValid = false;
            } else {
                emailError.textContent = '';
            }

            // Validate username
            const username = document.getElementById('username');
            const usernameError = document.getElementById('usernameError');
            if (username.value.length < 3 || username.value.length > 20) {
                usernameError.textContent = 'Username must be between 3 and 20 characters.';
                isValid = false;
            } else {
                usernameError.textContent = '';
            }

            // Validate password
            const password = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            const passwordPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;
            if (!password.value.match(passwordPattern)) {
                passwordError.textContent = 'Password must be at least 8 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.';
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
        <h2>Sign Up</h2>
        <form onsubmit="return validateForm()" method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
                <div id="usernameError" class="text-danger"></div>
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div id="emailError" class="text-danger"></div>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div id="passwordError" class="text-danger"></div>
            </div>
            <button type="submit" class="btn btn-primary">Sign Up</button>
            <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
        </form>
        <p class="mt-3"><?php echo $message; ?></p>
    </div>
</body>

</html>
