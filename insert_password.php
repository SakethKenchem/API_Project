<?php
require_once 'db.php';
$password = password_hash('S00per-d00per', PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (email, password, username) VALUES ('s.kenchem@gmail.com', '$password', 'Saketh')";
$conn->exec($sql);
echo "Admin password inserted successfully";

?>