<?php
$servername = "localhost";
$username = "root";
$password = "S00per-d00per";

try {
  $conn = new PDO("mysql:host=$servername;dbname=project_api", $username, $password);

  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}
?>