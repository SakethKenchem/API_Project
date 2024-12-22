<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($to, $otp)
{
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Set your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username   = 's.kenchem@gmail.com'; // Your SMTP username
        $mail->Password   = 'jncj pmsd ljkk savt';   // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('noreply@yourdomain.com', 'Your App Name');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP Code is: <strong>$otp</strong>. Please enter this code to proceed.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
