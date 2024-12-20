<?php
$host = 'localhost';  // Database host
$dbname = 'project_api';  // Database name
$username = 'root';  // Database username
$password = 'S00per-d00per';  // Database password

try {
    // Create a PDO instance and set the PDO error mode to exception
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, catch the error and display it
    echo "Connection failed: " . $e->getMessage();
    exit;  // Stop the script if the connection fails
}
?>
