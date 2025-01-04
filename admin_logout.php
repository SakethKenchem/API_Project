<?php
session_name("admin_session"); 
session_start();


// Check if the session is set and contains the 'role' key
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit;
} else {
    echo "Unauthorized access.";
}
?>
