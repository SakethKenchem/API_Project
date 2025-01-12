<?php
session_name("user_session");
session_start();

require 'C:/Apache24/htdocs/API_Project/config.php';
require 'db.php';
require 'send_email.php';

class LoginOTPVerification {
    private $conn;
    private $user_id;
    private $message;

    public function __construct($db) {
        $this->conn = $db;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->message = '';
    }

    public function isLoggedIn() {
        return isset($this->user_id);
    }

    public function verifyOTP($otp_entered) {
        if (!$this->isLoggedIn()) {
            header("Location: ../../views/user/login.php");
            exit();
        }

        try {
            // Fetch the most recent OTP code from the database for this user
            $stmt = $this->conn->prepare("SELECT otp_code, created_at FROM otp_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$this->user_id]);
            $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($otp_record) {
                // Trim any extra spaces from the entered OTP and the stored OTP
                $otp_entered = trim($otp_entered);
                $stored_otp = trim($otp_record['otp_code']);

                // Check for expiration: OTP expires after 5 minutes
                $otp_expiration_time = 5 * 60; // 5 minutes
                $created_at = strtotime($otp_record['created_at']);
                $current_time = time();

                if (($current_time - $created_at) <= $otp_expiration_time) {
                    // Compare the entered OTP with the stored OTP
                    if ($stored_otp === $otp_entered) {
                        $_SESSION['is_logged_in'] = true;
                        header("Location: /API_Project/views/user/dashboard.php");
                        exit();
                    } else {
                        $this->message = 'Invalid OTP. Please try again.';
                    }
                } else {
                    $this->message = 'OTP expired. Please request a new one.';
                }
            } else {
                $this->message = 'No OTP record found. Please request a new OTP.';
            }
        } catch (PDOException $e) {
            $this->message = 'Error: ' . $e->getMessage();
        }
    }

    public function getMessage() {
        return $this->message;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpVerification = new LoginOTPVerification($conn);
    $otpVerification->verifyOTP($_POST['otp']);
    $message = $otpVerification->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 500px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Verify OTP</h2>
    <form method="POST" action="verify_login_otp.php">
        <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP sent to your email</label>
            <input type="text" class="form-control" id="otp" name="otp" required>
        </div>
        <button type="submit" class="btn btn-primary">Verify OTP</button>
    </form>
    <?php if ($message): ?>
        <div class="alert alert-danger mt-3">
            <?= ($message) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
