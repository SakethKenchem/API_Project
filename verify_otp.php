<?php
require 'db.php';

$type = $_GET['type'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];

    $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE user_id = ? AND otp_code = ?");
    $stmt->execute([$user_id, $otp]);
    $otpEntry = $stmt->fetch();

    if ($otpEntry) {
        if ($type === 'signup') {
            header("Location: dashboard.php");
        } elseif ($type === 'login') {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $message = 'Invalid OTP. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
