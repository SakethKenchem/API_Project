<?php
require_once 'db.php';

class Navbar {
    private $db;
    private $username;
    private $profileLink;
    private $profilePic;

    public function __construct($db) {
        $this->db = $db;
        $this->username = "Guest";
        $this->profileLink = "#";
        $this->profilePic = 'default.png'; // Set a default profile picture
        $this->loadUser();
    }

    private function loadUser() {
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare("SELECT username, profile_pic FROM users WHERE id = :user_id");
                $stmt->execute(['user_id' => $_SESSION['user_id']]);

                if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->username = $result['username'];
                    $this->profileLink = "../../views/user/myprofile.php";
                    $this->profilePic = $result['profile_pic'] ?: 'default.png'; // Use default if null
                }
            } catch (PDOException $e) {
                error_log("Error: " . $e->getMessage());
            }
        }
    }

    public function getUsername() {
        return $this->username;
    }

    public function getProfileLink() {
        return $this->profileLink;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getProfilePic() {
        return $this->profilePic;
    }
}

$user = new Navbar($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' code.jquery.com; script-src 'self' code.jquery.com 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    <title>Not Instagram</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <style>
        .navbar-profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 5px;
            object-fit: cover; /* prevents image distortion */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Not Instagram</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="explore.php"> Explore</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_post.php">Create Post</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dms.php">Messages</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <!-- Commented out the search bar -->
                <!-- <form class="d-flex" role="search" method="GET" action="search.php">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search" required>
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form> -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= ($user->getProfileLink()) ?>">
                                <img src="<?= ($user->getProfilePic()) ?>" alt="Profile" class="navbar-profile-pic">
                                <?= ($user->getUsername()) ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
