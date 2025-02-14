<?php
require_once 'db.php';
session_start();

class Search {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function handleSearch($query) {
        $query = trim($query);
        if ($query === '') {
            return json_encode([
                'status' => 'error',
                'message' => 'Search query is required'
            ]);
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id, username, profile_pic
                FROM users
                WHERE LOWER(username) LIKE LOWER(:searchQuery)
                LIMIT 5
            ");
            $searchParam = "%$query%";
            $stmt->bindValue(':searchQuery', $searchParam, PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($results)) {
                return json_encode([
                    'status' => 'empty',
                    'message' => 'No users found'
                ]);
            }

            return json_encode([
                'status' => 'success',
                'results' => $results
            ]);
        } catch (PDOException $e) {
            error_log("Search error: " . $e->getMessage());
            return json_encode([
                'status' => 'error',
                'message' => 'An error occurred while searching'
            ]);
        }
    }
}

$search = new Search($conn);

// Handle AJAX search request
if (isset($_GET['q'])) {
    header('Content-Type: application/json');
    echo $search->handleSearch($_GET['q']);
    exit;
}
?>