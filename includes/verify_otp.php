<?php
require 'db.php';

class OTPVerification
{
    private $conn;
    private $type;
    private $user_id;
    private $message = '';

    public function __construct($conn, $type, $user_id)
    {
        $this->conn = $conn;
        $this->type = $type;
        $this->user_id = $user_id;
    }

    public function verifyOTP($otp)
    {
        $stmt = $this->conn->prepare("SELECT * FROM otp_codes WHERE user_id = ? AND otp_code = ?");
        $stmt->execute([$this->user_id, $otp]);
        $otpEntry = $stmt->fetch();

        if ($otpEntry) {
            return true;
        }
        return false;
    }

    public function redirectToDashboard()
    {
        header("Location: /API_Project/views/user/dashboard.php");
        exit();
    }

    public function handlePostRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $otp = $_POST['otp'];

            if ($this->verifyOTP($otp)) {
                if ($this->type === 'signup' || $this->type === 'login') {
                    $this->redirectToDashboard();
                }
            } else {
                $this->message = 'Invalid OTP. Please try again.';
            }
        }
    }

    public function getMessage()
    {
        return $this->message;
    }
}

$type = $_GET['type'] ?? '';
$user_id = $_GET['user_id'] ?? '';

$otpVerification = new OTPVerification($conn, $type, $user_id);
$otpVerification->handlePostRequest();
$message = $otpVerification->getMessage();
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
    <div class="card text-center">
        <div class="header-text">Not Instagram</div>
        <h3 class="mb-3">Verify OTP</h3>
        <p class="text-muted">Enter the OTP sent to your email to sign up</p>
        <form method="POST" action="">
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