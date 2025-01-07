<?php

require '../../config.php';
require 'vendor/autoload.php';
require '../../includes/db.php';
require '../../includes/send_email.php';

class UserSignup {
    private $conn;
    private $message;

    public function __construct($db) {
        $this->conn = $db;
        $this->message = '';
    }

    public function registerUser($username, $email, $password) {
        // Basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '<div class="text-danger">Invalid email format.</div>';
        }
        if (strlen($username) < 3 || strlen($username) > 20) {
            return '<div class="text-danger">Username must be between 3 and 20 characters.</div>';
        }
        if (strlen($password) < 8) {
            return '<div class="text-danger">Password must be at least 8 characters long.</div>';
        }

        // Check if username or email exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existingCount = $stmt->fetchColumn();

        if ($existingCount > 0) {
            return '<div class="text-danger">Username or email already in use. Please choose a different one.</div>';
        }

        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Insert user into the database
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $passwordHash]);
            $user_id = $this->conn->lastInsertId();

            // Generate OTP and send email
            $otp = rand(100000, 999999);
            $stmt = $this->conn->prepare("INSERT INTO otp_codes (user_id, otp_code) VALUES (?, ?)");
            $stmt->execute([$user_id, $otp]);

            if (sendEmail($email, $otp)) {
                header("Location: ../../includes/verify_otp.php?type=signup&user_id=$user_id");
                exit();
            } else {
                return '<div class="text-danger">Failed to send OTP. Please try again.</div>';
            }
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return '<div class="text-danger">An error occurred. Please try again later.</div>';
        }
    }

    public function getMessage() {
        return $this->message;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $userRegistration = new UserSignup($conn);
    $message = $userRegistration->registerUser($username, $email, $password);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <link href="../../assets/css/signupform.css" rel="stylesheet">

</head>
<body>
    <h2>Not Instagram</h2>
    <div class="container">
        <div class="form-wrapper">
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
                <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
            </form>
            <p class="mt-3" style="color: red; font-size:x-large;"><?php echo $message; ?></p>
        </div>
    </div>
</body>

<script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</html>
