<?php
require 'C:/Apache24/htdocs/API_Project/config.php';
require_once 'C:/Apache24/htdocs/API_Project/includes/db.php';

$password = password_hash('S00per-d00per', PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (email, password, username) VALUES ('s.kenchem@gmail.com', '$password', 'Saketh')";
$conn->exec($sql);
echo "Admin password inserted successfully";
