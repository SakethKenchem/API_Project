<?php
$host = 'localhost';  
$dbname = 'project_api';  
$username = 'root';  
$password = 'S00per-d00per';  

try {
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    
    echo "Connection failed: " . $e->getMessage();
    exit; 
}
?>
