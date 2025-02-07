<?php
session_name("user_session");
session_start();

require_once '../../includes/db.php';
require '../../config.php';
include '../../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

class UserProfile
{
    private $db;
    private $id;
    private $data;

    public function __construct($db, $userId)
    {
        $this->db = $db;
        $this->id = $userId;
        $this->loadUser();
    }

    private function loadUser()
    {
        $stmt = $this->db->prepare("SELECT username, email, created_at FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $this->id]);
        $this->data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUsername($newUsername)
    {
        $stmt = $this->db->prepare("UPDATE users SET username = :username WHERE id = :user_id");
        return $stmt->execute([
            'username' => $newUsername,
            'user_id' => $this->id
        ]);
    }

    public function getData()
    {
        return $this->data;
    }
}

$message = '';
$userProfile = new UserProfile($conn, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_username'])) {
    if ($userProfile->updateUsername($_POST['new_username'])) {
        $message = "Username updated successfully!";
        $userProfile = new UserProfile($conn, $_SESSION['user_id']);
    }
}

$userData = $userProfile->getData();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Not Instagram</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <?php if ($message): ?>
            <div class="alert alert-info"><?= ($message) ?></div>
        <?php endif; ?>

        <!-- Profile Information Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5>Account Details</h5>
                            <p><strong>Email:</strong> <?= ($userData['email']) ?></p>
                            <p><strong>Member Since:</strong> <?= (date('F j, Y', strtotime($userData['created_at']))) ?></p>
                        </div>
                        <div class="mb-4">
                            <h5>Update Username</h5>
                            <form method="POST" class="mt-3">
                                <div class="mb-3">
                                    <label for="current_username" class="form-label">Current Username</label>
                                    <input type="text" class="form-control" id="current_username" value="<?= ($userData['username']) ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="new_username" class="form-label">New Username</label>
                                    <input type="text" class="form-control" id="new_username" name="new_username" required minlength="3" maxlength="30">
                                    <div class="form-text">Username must be 3-30 characters long.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Username</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liked Posts Section-->
        <div class="row justify-content-center" style="margin-bottom: 35px;">
            <div class="col-md-10">
                <?php include 'view_likes.php'; ?>
            </div>
        </div>
    </div>

    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>