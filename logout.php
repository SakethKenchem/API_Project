<?php

//logout code
session_start();
session_destroy();
header('Location: login.php');
exit();
?>