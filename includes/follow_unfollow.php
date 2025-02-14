<?php
session_name("user_session");
session_start();
require_once 'db.php';

class FollowUnfollow {
    private $conn;
    private $follower_id;
    private $following_id;

    public function __construct($db, $follower_id, $following_id) {
        $this->conn = $db;
        $this->follower_id = $follower_id;
        $this->following_id = $following_id;
    }

    public function processRequest() {
        if (!$this->following_id || $this->follower_id == $this->following_id) {
            return json_encode(["status" => "error", "message" => "Invalid follow request"]);
        }

        try {
            if ($this->isFollowing()) {
                $this->unfollow();
                return json_encode(["status" => "unfollowed"]);
            } else {
                $this->follow();
                return json_encode(["status" => "followed"]);
            }
        } catch (PDOException $e) {
            return json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    private function isFollowing() {
        $stmt = $this->conn->prepare("SELECT * FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$this->follower_id, $this->following_id]);
        return $stmt->rowCount() > 0;
    }

    private function follow() {
        $stmt = $this->conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$this->follower_id, $this->following_id]);
    }

    private function unfollow() {
        $stmt = $this->conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$this->follower_id, $this->following_id]);
    }
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$follower_id = $_SESSION['user_id'];
$following_id = $_POST['following_id'] ?? null;

$followUnfollow = new FollowUnfollow($conn, $follower_id, $following_id);
echo $followUnfollow->processRequest();
?>
