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
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header text-center"><h4>Verify OTP</h4></div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-danger"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="otp" class="form-label">OTP Code</label>
                    <input type="text" class="form-control" id="otp" name="otp" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
