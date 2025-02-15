<?php
session_name("user_session");
session_start();

require '../../config.php';
require 'vendor/autoload.php';
require '../../includes/db.php';
require '../../includes/send_email.php';

class Login
{
    private $pdo;
    private $message;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function authenticate($email, $username, $password)
    {
        // Fetch user by email and username
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND username = ?");
        $stmt->execute([$email, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Generate OTP
            $otp = rand(100000, 999999);
            $this->saveOtp($user['id'], $otp);

            if ($this->sendOtp($user['email'], $otp)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'user';
                header("Location: ../../includes/verify_login_otp.php");
                exit();
            } else {
                $this->message = 'Failed to send OTP. Please try again.';
            }
        } else {
            $this->message = 'Invalid email, username, or password.';
        }
    }

    private function saveOtp($userId, $otp)
    {
        $stmt = $this->pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $otp]);
    }

    private function sendOtp($email, $otp)
    {
        // Ensure you have a working sendEmail function
        return sendEmail($email, $otp);
    }

    public function getMessage()
    {
        return $this->message;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CAPTCHA: trim input and compare
    $captchaInput = trim($_POST['captcha_input'] ?? '');
    $captchaSession = trim($_SESSION['captcha'] ?? '');

    if ($captchaInput === '' || $captchaInput !== $captchaSession) {
        $message = '<span style="color:red">CAPTCHA ENTERED IS INCORRECT. REFRESH PAGE AND TRY AGAIN.</span>';
    } else {
        // If CAPTCHA is correct, proceed with login
        $login = new Login($conn);
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $login->authenticate($email, $username, $password);
        $message = $login->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/loginform.css">
</head>
<body>
    <h3>Not Instagram</h3>
    <div class="container">
        <div class="form-wrapper">
            <h2>Login</h2>
            <form onsubmit="return validateForm()" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div id="emailError" class="text-danger"></div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                    <div id="usernameError" class="text-danger"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div id="passwordError" class="text-danger"></div>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="showPassword">
                    <label class="form-check-label" for="showPassword">Show Password</label>
                </div>

                <!-- CAPTCHA -->
                <div class="form-group">
                    <label for="captcha">Captcha</label>
                    <div class="d-flex">
                        <img src="../../includes/captcha.php" alt="CAPTCHA Image" class="mr-2">
                        <input type="text" class="form-control" id="captcha_input" name="captcha_input" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
                <p class="mt-3 text-center">Don't have an account? <a href="../../views/user/signup.php">Sign up</a></p>
                <p class="mt-3 text-center">Forgot your password? <a href="../../views/user/forgot_password.php">Reset password</a></p>
            </form>
            <?php if (!empty($message)): ?>
                <div class="alert alert-danger mt-3">
                    <?= $message ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script src="../../assets/js/loginform.js"></script>
<script>
    document.getElementById("showPassword").addEventListener("change", function() {
        var passwordField = document.getElementById("password");
        if (this.checked) {
            passwordField.type = "text";  // Show password
        } else {
            passwordField.type = "password";  // Hide password
        }
    });
</script>
</html>
