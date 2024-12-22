<?php
require_once 'db.php';

if (isset($_POST['query'])) {
    $query = $_POST['query'];

    try {
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE :query OR email LIKE :query");

        // Bind the query parameter to the prepared statement
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);

        // Execute the query
        $stmt->execute();

        // Fetch all matching users
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if users exist and display them
        if ($users) {
            foreach ($users as $user) {
                echo "<tr>
                        <td>{$user['id']}</td>
                        <td>{$user['username']}</td>
                        <td>{$user['email']}</td>
                        <td>{$user['created_at']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No users found</td></tr>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
