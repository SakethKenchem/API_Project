<?php
session_name("user_session");
session_start();
require '../../includes/db.php';
require '../../config.php';

class DMSBackend {
    private $conn;
    private $currentUser;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->currentUser = $_SESSION['user_id'] ?? null;
    }

    public function handleRequest() {
        if (!$this->currentUser) {
            $this->sendResponse(["error" => "Not logged in"]);
            return;
        }

        $action = $_GET['action'] ?? $_POST['action'] ?? null;

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->handleGet($action);
                break;
            case 'POST':
                $this->handlePost($action);
                break;
            default:
                $this->sendResponse(["error" => "Invalid request method"]);
        }
    }

    private function handleGet($action) {
        if (!$action) {
            $this->sendResponse(["error" => "No action specified"]);
            return;
        }

        switch ($action) {
            case 'fetch':
                $this->fetchMessages();
                break;
            case 'users':
                $this->fetchUsers();
                break;
            case 'search':
                $this->searchUsers();
                break;
            default:
                $this->sendResponse(["error" => "Invalid action"]);
        }
    }

    private function handlePost($action) {
        if (!$action) {
            $this->sendResponse(["error" => "No action specified"]);
            return;
        }

        switch ($action) {
            case 'send':
                $this->sendMessage();
                break;
            default:
                $this->sendResponse(["error" => "Invalid action"]);
        }
    }

    private function fetchMessages() {
        if (!isset($_GET['with'])) {
            $this->sendResponse(["error" => "No user specified"]);
            return;
        }

        $otherUser = intval($_GET['with']);
        $stmt = $this->conn->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$this->currentUser, $otherUser, $otherUser, $this->currentUser]);
        $this->sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function fetchUsers() {
        $stmt = $this->conn->prepare("SELECT id, username, COALESCE(profile_pic, '../../assets/default-avatar.png') AS profile_pic FROM users WHERE id != ?");
        $stmt->execute([$this->currentUser]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse($users);
    }

    private function searchUsers() {
        if (!isset($_GET['query'])) {
            $this->sendResponse(["error" => "No search query specified"]);
            return;
        }

        $query = "%" . $_GET['query'] . "%";
        $stmt = $this->conn->prepare("SELECT id, username, COALESCE(profile_pic, '../../assets/default-avatar.png') AS profile_pic FROM users WHERE username LIKE ? AND id != ?");
        $stmt->execute([$query, $this->currentUser]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse($users);
    }

    private function sendMessage() {
        if (!isset($_POST['with'], $_POST['message'])) {
            $this->sendResponse(["error" => "Missing parameters"]);
            return;
        }

        $otherUser = intval($_POST['with']);
        $message = trim($_POST['message']);

        if (empty($message)) {
            $this->sendResponse(["error" => "Empty message"]);
            return;
        }

        $stmt = $this->conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$this->currentUser, $otherUser, $message]);
        $this->sendResponse(["success" => true]);
    }

    private function getProfilePic($userId) {
        $stmt = $this->conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['profile_pic'] ? '../../uploads/' . $result['profile_pic'] : '../../assets/default-avatar.png';
    }

    private function sendResponse($data) {
        echo json_encode($data);
        exit;
    }
}

$dmsBackend = new DMSBackend($conn);
$dmsBackend->handleRequest();
?>