<?php
require '../../config.php';
require '../../includes/db.php';

class Comments
{
    private $conn;
    private $user_id;

    public function __construct($db, $user_id)
    {
        $this->conn = $db;
        $this->user_id = $user_id;
    }

    public function addComment($post_id, $content)
    {
        if (!$this->user_id || empty(trim($content))) {
            return json_encode(['status' => 'error', 'message' => 'Invalid comment']);
        }

        $stmt = $this->conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $this->user_id, $content]);

        $comment_id = $this->conn->lastInsertId();
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $username = $stmt->fetchColumn();

        return json_encode([
            'status' => 'success',
            'comment_id' => $comment_id,
            'username' => $username,
            'content' => htmlspecialchars($content)
        ]);
    }

    public function getComments($post_id)
    {
        $stmt = $this->conn->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
        $stmt->execute([$post_id]);
        return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function editComment($comment_id, $content)
    {
        if (!$this->user_id || empty(trim($content))) {
            return json_encode(['status' => 'error', 'message' => 'Invalid comment']);
        }

        $stmt = $this->conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $comment_id, $this->user_id]);

        return json_encode(['status' => 'success', 'content' => htmlspecialchars($content)]);
    }

    public function deleteComment($comment_id)
    {
        if (!$this->user_id) {
            return json_encode(['status' => 'error', 'message' => 'Not logged in']);
        }

        $stmt = $this->conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $this->user_id]);

        return json_encode(['status' => 'success']);
    }
}

if (isset($_POST['action'])) {
    session_name("user_session");
    session_start();
    $comments = new Comments($conn, $_SESSION['user_id'] ?? null);

    if ($_POST['action'] == 'add_comment') {
        echo $comments->addComment($_POST['post_id'], $_POST['content']);
    } elseif ($_POST['action'] == 'get_comments') {
        echo $comments->getComments($_POST['post_id']);
    } elseif ($_POST['action'] == 'edit_comment') {
        echo $comments->editComment($_POST['comment_id'], $_POST['content']);
    } elseif ($_POST['action'] == 'delete_comment') {
        echo $comments->deleteComment($_POST['comment_id']);
    }
    exit;
}
?>
