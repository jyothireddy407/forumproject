<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];

// Modified query to include post_id and better notification data
$query = "SELECT comments.id, comments.content, comments.post_id, posts.title, comments.created_at 
          FROM comments 
          JOIN posts ON comments.post_id = posts.id 
          WHERE posts.user_id = $userId AND comments.seen = 0
          ORDER BY comments.created_at DESC 
          LIMIT 5";

$result = $conn->query($query);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'content' => $row['content'],
        'post_id' => $row['post_id'],
        'title' => $row['title'],
        'created_at' => $row['created_at'],
        'read' => false
    ];
}

echo json_encode($notifications);
?>