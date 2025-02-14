<?php
require '../../config.php';
require '../../includes/db.php';

class Likes
{
    private $conn;
    private $user_id;

    public function __construct($db, $user_id)
    {
        $this->conn = $db;
        $this->user_id = $user_id;
    }

    public function toggleLike($post_id)
    {
        if (!$this->user_id) {
            return json_encode(['status' => 'error', 'message' => 'Not logged in']);
        }

        $stmt = $this->conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $this->user_id]);
        $existing_like = $stmt->fetch();

        if ($existing_like) {
            $stmt = $this->conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $this->user_id]);
            $liked = false;
        } else {
            $stmt = $this->conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $this->user_id]);
            $liked = true;
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $count = $stmt->fetchColumn();

        return json_encode(['status' => 'success', 'liked' => $liked, 'count' => $count]);
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'toggle_like') {
    session_name("user_session");
    session_start();
    $dashboard = new Likes($conn, $_SESSION['user_id'] ?? null);
    echo $dashboard->toggleLike($_POST['post_id']);
    exit;
}

?>
