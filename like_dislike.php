<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "User not logged in"]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $id = $_POST['id']; // Post or Comment ID
    $type = $_POST['type']; // Like or Dislike
    $target = $_POST['target']; // 'post' or 'comment'

    if ($target == 'post') {
        $table = "post_likes";
        $column = "post_id";
    } elseif ($target == 'comment') {
        $table = "comment_likes";
        $column = "comment_id";
    } else {
        die(json_encode(["error" => "Invalid target"]));
    }

    // Check if user already liked/disliked
    $checkQuery = "SELECT * FROM $table WHERE $column = $id AND user_id = $userId";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        $updateQuery = "UPDATE $table SET type='$type' WHERE $column = $id AND user_id = $userId";
        $conn->query($updateQuery);
    } else {
        $insertQuery = "INSERT INTO $table ($column, user_id, type) VALUES ($id, $userId, '$type')";
        $conn->query($insertQuery);
    }

    // Get updated like/dislike counts
    $likesCount = $conn->query("SELECT COUNT(*) AS count FROM $table WHERE $column = $id AND type='like'")->fetch_assoc()['count'];
    $dislikesCount = $conn->query("SELECT COUNT(*) AS count FROM $table WHERE $column = $id AND type='dislike'")->fetch_assoc()['count'];

    echo json_encode(["likes" => $likesCount, "dislikes" => $dislikesCount]);
}
?>
