<?php
session_name("user_session");
session_start();
require_once '../../includes/db.php';
require '../../config.php';
include '../../includes/navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

class UserProfile {
    private $conn;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    public function getUserDetails() {
        $stmt = $this->conn->prepare("SELECT username, profile_pic, bio FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserPosts() {
        $stmt = $this->conn->prepare("SELECT id, image_url FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProfilePic($profile_pic) {
        if (!empty($profile_pic) && file_exists(__DIR__ . "/../../uploads/" . $profile_pic)) {
            return "../../uploads/" . $profile_pic;
        } else {
            return "../../assets/default_profile.jpg";
        }
    }
}

$user_id = $_SESSION['user_id'];
$userProfile = new UserProfile($conn, $user_id);
$user = $userProfile->getUserDetails();
$posts = $userProfile->getUserPosts();
$profile_pic = $userProfile->getProfilePic($user['profile_pic']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../../assets/bootstrap/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-card {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
        }

        .bio {
            font-size: 16px;
            color: #555;
            margin-top: 10px;
        }

        .posts-container {
            max-width: 900px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            padding: 15px;
        }

        .post-item {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .post-item:hover {
            transform: scale(1.05);
        }

        .post-item img {
            width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: contain;
            border-radius: 10px;
        }
    </style>
</head>

<body>

    <!-- Profile Section -->
    <div class="profile-card">
        <h4>Profile</h4>
        <img src="<?php echo ($user['profile_pic'] ?: 'default.png'); ?>" alt="Profile Picture" class="profile-pic">

        <h4><?php echo ($user['username']); ?></h4>
        <p class="bio"><?php echo ($user['bio']); ?></p>
        <a href="mysettings.php" style="font-size: small;">Settings</a>

        <hr style="margin-bottom: -19px;">

        <!-- User Posts -->
        <div class="posts-container">
            <?php if (empty($posts)): ?>
                <p class="text-center">This user has not posted anything yet.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-item">
                    <a href="../../views/user/view_post.php?post_id=<?= $post['id'] ?>">
                                <img src="../../uploads/<?= ($post['image_url']) ?>" class="post-image card-img-top" alt="Post image">
                            </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>

</html>
