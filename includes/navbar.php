<?php
require_once '../../includes/db.php';

class Navbar {
    private $db;
    private $username;
    private $profileLink;

    public function __construct($db) {
        $this->db = $db;
        $this->username = "Guest";
        $this->profileLink = "#";
        $this->loadUser();
    }

    private function loadUser() {
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare("SELECT username FROM users WHERE id = :user_id");
                $stmt->execute(['user_id' => $_SESSION['user_id']]);

                if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->username = $result['username'];
                    $this->profileLink = "../../views/user/profile.php";
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
                            <a class="nav-link" href="explore.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-compass" viewBox="0 0 16 16">
                                    <path d="M8 16.016a7.5 7.5 0 0 0 1.962-14.74A1 1 0 0 0 9 0H7a1 1 0 0 0-.962 1.276A7.5 7.5 0 0 0 8 16.016m6.5-7.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0" />
                                    <path d="m6.94 7.44 4.95-2.83-2.83 4.95-4.949 2.83 2.828-4.95z" />
                                </svg>
                                Explore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_post.php">➕Create Post</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= ($user->getProfileLink()) ?>">
                                <?= ($user->getUsername()) ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <form class="d-flex" role="search" method="GET" action="search.php">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search" required>
                    <button class="btn btn-outline-success" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>
    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>