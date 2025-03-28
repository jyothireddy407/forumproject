<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to delete a comment.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $commentId = $_POST['comment_id'];

    // Ensure the comment belongs to the logged-in user
    $checkQuery = "SELECT * FROM comments WHERE id = $commentId AND user_id = $userId";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        $deleteQuery = "DELETE FROM comments WHERE id = $commentId";
        if ($conn->query($deleteQuery) === TRUE) {
            header("Location: history.php");
            exit;
        } else {
            die("Error deleting comment: " . $conn->error);
        }
    } else {
        die("Unauthorized action.");
    }
}
?>
