<?php
session_name("user_session");
session_start();

require_once '../../includes/db.php';
require '../../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

class UserProfileActions
{
    private $db;
    private $userId;

    public function __construct($db, $userId)
    {
        $this->db = $db;
        $this->userId = $userId;
    }

    public function updateComment($commentId, $newContent)
    {
        $stmt = $this->db->prepare("UPDATE comments SET content = :content WHERE id = :comment_id AND user_id = :user_id");
        $stmt->execute([
            'content' => $newContent,
            'comment_id' => $commentId,
            'user_id' => $this->userId
        ]);

        return ($stmt->rowCount() > 0);
    }

    public function deleteComment($commentId)
    {
        $stmt = $this->db->prepare("DELETE FROM comments WHERE id = :comment_id AND user_id = :user_id");
        $stmt->execute([
            'comment_id' => $commentId,
            'user_id' => $this->userId
        ]);

        return ($stmt->rowCount() > 0);
    }
}

$userProfileActions = new UserProfileActions($conn, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'edit_comment':
            if (isset($_POST['comment_id']) && isset($_POST['content'])) {
                $commentId = $_POST['comment_id'];
                $content = $_POST['content'];

                if ($userProfileActions->updateComment($commentId, $content)) {
                    echo json_encode(['status' => 'success', 'message' => 'Comment updated successfully!', 'content' => htmlspecialchars($content)]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update comment.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
            }
            break;

        case 'delete_comment':
            if (isset($_POST['comment_id'])) {
                $commentId = $_POST['comment_id'];

                if ($userProfileActions->deleteComment($commentId)) {
                    echo json_encode(['status' => 'success', 'message' => 'Comment deleted successfully!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete comment.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid comment ID.']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
