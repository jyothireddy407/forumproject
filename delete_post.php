<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to delete a post.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'];

    // Ensure the post belongs to the logged-in user
    $checkQuery = "SELECT * FROM posts WHERE id = $postId AND user_id = $userId";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        $deleteQuery = "DELETE FROM posts WHERE id = $postId";
        if ($conn->query($deleteQuery) === TRUE) {
            header("Location: history.php");
            exit;
        } else {
            die("Error deleting post: " . $conn->error);
        }
    } else {
        die("Unauthorized action.");
    }
}
?>
