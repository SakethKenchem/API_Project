<?php
session_name("admin_session");
session_start();


session_unset();
session_destroy();
header('Location: admin_login.php');
exit;
