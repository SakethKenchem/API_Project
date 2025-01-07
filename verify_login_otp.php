<?php
session_name("user_session");
session_start();
require 'db.php'; 
require 'send_email.php'; 

class OTPVerification {
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
            header("Location: login.php");
            exit();
        }

        try {
            $stmt = $this->conn->prepare("SELECT otp_code FROM otp_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$this->user_id]);
            $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($otp_record && $otp_record['otp_code'] === $otp_entered) {
                $_SESSION['is_logged_in'] = true;
                header("Location: dashboard.php");
                exit();
            } else {
                $this->message = 'Invalid OTP. Please try again.';
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
    $otpVerification = new OTPVerification($conn);
    $otpVerification->verifyOTP($_POST['otp']);
    $message = $otpVerification->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>