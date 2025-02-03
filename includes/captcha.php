<?php
session_name("user_session"); // Use the same session name as in login.php
session_start();

// Generate a random 4-digit captcha
$captcha = rand(1000, 9999);
$_SESSION["captcha"] = $captcha;

// Create image with background
$im = imagecreatetruecolor(100, 40);
$bg = imagecolorallocate($im, 22, 86, 165);
$fg = imagecolorallocate($im, 255, 255, 255);
imagefill($im, 0, 0, $bg);
imagestring($im, 5, 25, 10, $captcha, $fg);

// Prevent browser cache
header("Cache-Control: no-store, no-cache, must-revalidate");
header('Content-type: image/png');

// Output image
imagepng($im);
imagedestroy($im);
?>
