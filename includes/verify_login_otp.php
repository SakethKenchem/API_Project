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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            flex-direction: column;
        }
        .card {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background: #fff;
        }
        .btn-success {
            width: 100%;
            font-weight: bold;
            border-radius: 5px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        .header-text {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header-text">Not Instagram</div>
    <div class="card text-center">
        <h3 class="mb-3">Verify OTP</h3>
        <p class="text-muted">Enter the OTP sent to your email to login</p>
        <form method="POST" action="verify_login_otp.php">
            <div class="mb-3">
                <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP" required>
            </div>
            <button type="submit" class="btn btn-success">Verify</button>
        </form>
        <?php if ($message): ?>
            <div class="alert alert-danger mt-3">
                <?= ($message) ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
