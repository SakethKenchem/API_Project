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
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND username = ?");
        $stmt->execute([$email, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $otp = rand(100000, 999999);
            $this->saveOtp($user['id'], $otp);
            if ($this->sendOtp($user['email'], $otp)) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'user';
                header("Location: ../../includes/verify_login_otp.php");
                exit();
            } else {
                $this->message = 'Failed to send OTP. Try again.';
            }
        } else {
            $this->message = 'Invalid email, username, or password.';
        }
    }

    private function saveOtp($userId, $otp)
    {
        $stmt = $this->pdo->prepare("INSERT INTO otp_codes (user_id, otp_code) VALUES (?, ?)");
        $stmt->execute([$userId, $otp]);
    }

    private function sendOtp($email, $otp)
    {
        return sendEmail($email, $otp);
    }

    public function getMessage()
    {
        return $this->message;
    }
}

$login = new Login($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $login->authenticate($email, $username, $password);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script>
        function validateForm() {
            let isValid = true;

            const email = document.getElementById('email');
            const username = document.getElementById('username');
            const password = document.getElementById('password');

            document.getElementById('emailError').textContent = email.value.trim() ? '' : 'Email is required.';
            document.getElementById('usernameError').textContent = username.value.trim() ? '' : 'Username is required.';
            document.getElementById('passwordError').textContent = password.value.trim() ? '' : 'Password is required.';

            if (!email.value.trim() || !username.value.trim() || !password.value.trim()) isValid = false;

            return isValid;
        }
    </script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(203, 11, 11, 0.1);
            background-color: #fff;
            border-radius: 8px;
        }

        .form-control {
            margin-bottom: 15px;
            height: 45px;
        }

        .form-group label {
            margin-bottom: 5px;
        }

        .btn-primary {
            width: 100%;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        h3 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h3>Not Instagram</h3>
    <div class="container">
        <div class="form-wrapper">
            <h2>Login</h2>
            <form onsubmit="return validateForm()" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                    <div id="emailError" class="text-danger"></div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username">
                    <div id="usernameError" class="text-danger"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <div id="passwordError" class="text-danger"></div>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <p class="mt-3 text-center">Don't have an account? <a href="../../views/user/signup.php">Sign up</a></p>
            </form>
            <?php if ($message = $login->getMessage()): ?>
                <div class="alert alert-danger mt-3">
                    <?= ($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>