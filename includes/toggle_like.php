<?php
session_name("user_session");
session_start();

require '../config.php';
require 'db.php';

class LikeToggle {
    private $conn;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    public function toggleLike($post_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $this->user_id]);
        $isLiked = $stmt->fetchColumn() > 0;

        if ($isLiked) {
            $stmt = $this->conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $this->user_id]);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $this->user_id]);
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../views/user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $likeToggle = new LikeToggle($conn, $user_id);
    $likeToggle->toggleLike($post_id);
}

header('Location: ../views/user/dashboard.php');
exit();
