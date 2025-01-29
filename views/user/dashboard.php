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

    // Method to fetch posts
    public function getPosts()
    {
        $stmt = $this->conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$dashboard = new Dashboard($conn);
$user = $dashboard->getUser();
$posts = $dashboard->getPosts(); 

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        .post-image {
            width: 100%;
            
            height: 200px;
           
            object-fit: cover;
        }

        .card-deck {
            display: flex;
            justify-content: center;
        }

        .card {
            margin: 15px;
        
        }
    </style>
</head>

<body>
    <div class="text-center">
        <h1 class="greeting">Welcome, <?php echo ($user['username']); ?>!</h1>
    </div>
    <div class="container mt-5">


        <!-- cards -->
        <div class="row justify-content-center mt-4">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <?php if ($post['image_url']): ?>
                            <img src="../../uploads/<?php echo ($post['image_url']); ?>" class="card-img-top post-image" alt="Post Image">
                        <?php endif; ?>
                        <!-- username -->
                        <div class="card-header">
                            <?php echo ($user['username']); ?>
                        </div>

                        <div class="card-body">
                            <p class="card-text"><?php echo ($post['content']); ?></p>
                        </div>
                        <div class="card-footer text-muted">
                            Posted on <?php echo ($post['created_at']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>