<?php
session_name("user_session");
session_start();

require '../../config.php';
include '../../includes/navbar.php'; 
require '../../includes/db.php'; 

class Dashboard
{
    private $conn;
    private $user_id;
    private $user;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->user_id = $_SESSION['user_id'] ?? null;

        if (!$this->user_id) {
            header('Location: ../../views/user/login.php');
            exit();
        }

        $this->loadUserData();
    }

    private function loadUserData()
    {
        if ($this->user_id) {
            $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$this->user_id]);
            $this->user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$this->user) {
                header('Location: ../../views/user/logout.php');
                exit();
            }
        }
    }

    public function getUser()
    {
        return $this->user;
    }
}

$dashboard = new Dashboard($conn); 
$user = $dashboard->getUser();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="text-center">
            <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        </div>
    </div>
</body>

</html>
