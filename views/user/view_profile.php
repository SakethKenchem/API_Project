<?php
session_name("user_session");
session_start();

require_once '../../includes/db.php';
require '../../config.php';
include '../../includes/navbar.php';

class UserProfile
{
    private $conn;
    private $user_id;
    private $logged_in_user_id;

    public function __construct($conn, $user_id, $logged_in_user_id)
    {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->logged_in_user_id = $logged_in_user_id;
    }

    public function getUserDetails()
    {
        $stmt = $this->conn->prepare("SELECT username, profile_pic, bio FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserPosts()
    {
        $stmt = $this->conn->prepare("SELECT id, image_url FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isFollowing()
    {
        if ($this->logged_in_user_id) {
            $stmt = $this->conn->prepare("SELECT * FROM followers WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$this->logged_in_user_id, $this->user_id]);
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function getProfilePicPath($profile_pic)
    {
        $profile_pic_path = '../../uploads/' . $profile_pic;
        if (empty($profile_pic) || !file_exists($profile_pic_path)) {
            return '../assets/default_profile.jpg';
        }
        return $profile_pic_path;
    }

    public function getFollowers()
    {
        $stmt = $this->conn->prepare("SELECT users.id, users.username FROM followers JOIN users ON followers.follower_id = users.id WHERE followers.following_id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFollowing()
    {
        $stmt = $this->conn->prepare("SELECT users.id, users.username FROM followers JOIN users ON followers.following_id = users.id WHERE followers.follower_id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo "Invalid user ID.";
    exit;
}

$user_id = (int)$_GET['user_id'];
$logged_in_user_id = $_SESSION['user_id'] ?? null;

try {
    $profile = new UserProfile($conn, $user_id, $logged_in_user_id);
    $user = $profile->getUserDetails();

    if (!$user) {
        echo "User not found.";
        exit;
    }

    $profile_pic = $profile->getProfilePicPath($user['profile_pic']);
    $posts = $profile->getUserPosts();
    $isFollowing = $profile->isFollowing();

    $followers = $profile->getFollowers();
    $following = $profile->getFollowing();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
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
            margin: 20px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            padding: 10px;
        }

        .post-item img {
            width: fit-content;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            transition: transform 0.3s ease;
        }

        .post-item img:hover {
            transform: scale(1.05);
        }

        .follow-btn {
            margin-top: 10px;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .follow-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
            font-size: 18px;
        }

        .flyout {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            text-align: center;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <div class="profile-card">
            <img src="<?php echo ($user['profile_pic'] ?: 'default.png'); ?>" alt="Profile Picture" class="profile-pic">
            <h2><?php echo ($user['username']); ?></h2>
            <p class="bio"><?php echo ($user['bio']); ?></p>

            <?php if ($logged_in_user_id && $logged_in_user_id != $user_id): ?>
                <button id="followBtn" class="btn <?php echo $isFollowing ? 'btn-danger' : 'btn-primary'; ?> follow-btn"
                    onclick="toggleFollow(<?php echo $user_id; ?>)">
                    <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
                </button>
            <?php endif; ?>
            <div class="follow-stats">
                <span onclick="showFlyout('followers')"><strong><?php echo count($followers); ?></strong> Followers</span>
                <span onclick="showFlyout('following')"><strong><?php echo count($following); ?></strong> Following</span>
            </div>
        </div>

        <div class="posts-container">
            <?php if (empty($posts)): ?>
                <p>This user has not posted anything yet.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-item">
                        <a href="../../views/user/view_post.php?post_id=<?= $post['id'] ?>">
                            <img src="../../uploads/<?= ($post['image_url']); ?>" class="post-image card-img-top" alt="Post image">
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="overlay" id="overlay" onclick="closeFlyout()"></div>
    <div class="flyout" id="flyout">
        <h5 id="flyout-title"></h5>
        <ul id="flyout-list" style="list-style: none; padding: 0;"></ul>
    </div>


    <script src="../../assets/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        function toggleFollow(followingId) {
            fetch('../../includes/follow_unfollow.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `following_id=${followingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "followed") {
                        document.getElementById('followBtn').classList.replace('btn-primary', 'btn-danger');
                        document.getElementById('followBtn').innerText = "Unfollow";
                    } else if (data.status === "unfollowed") {
                        document.getElementById('followBtn').classList.replace('btn-danger', 'btn-primary');
                        document.getElementById('followBtn').innerText = "Follow";
                    }
                });
        }

        function showFlyout(type) {
            let title = type === 'followers' ? 'Followers' : 'Following';
            let list = type === 'followers' ? <?php echo json_encode($followers); ?> : <?php echo json_encode($following); ?>;
            let flyoutList = document.getElementById('flyout-list');
            let flyoutTitle = document.getElementById('flyout-title');

            flyoutList.innerHTML = '';
            flyoutTitle.innerText = title;

            list.forEach(user => {
                let li = document.createElement('li');
                let a = document.createElement('a');
                a.href = `view_profile.php?user_id=${user.id}`;
                a.textContent = user.username;
                a.style.textDecoration = 'none';
                a.style.color = '#007bff';
                a.onmouseover = () => a.style.textDecoration = 'underline';
                a.onmouseout = () => a.style.textDecoration = 'none';

                li.appendChild(a);
                flyoutList.appendChild(li);
            });

            document.getElementById('overlay').style.display = 'block';
            document.getElementById('flyout').style.display = 'block';
        }
    </script>
    </script>
</body>

</html>