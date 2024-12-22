<?php
require_once 'db.php';
$password = password_hash('S00per-d00per', PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (email, password) VALUES ('s.kenchem@gmail.com', '$password')";
$conn->exec($sql);
echo "Admin password inserted successfully";

?>