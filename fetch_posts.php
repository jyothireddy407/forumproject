<?php
header("Content-Type: application/json");
include "db.php"; // Ensure the database connection is included

$query = "SELECT id, title, content FROM posts ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $query);

$posts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $posts[] = $row;
}

echo json_encode($posts);
?>
