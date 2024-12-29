<?php
session_name("user_session");
session_start();

if ($_SESSION['role'] === 'user') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
} else {
    echo "Unauthorized access.";
}
?>
