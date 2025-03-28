<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    exit;
}

$userId = $_SESSION['user_id'];
$updateSQL = $conn->prepare("UPDATE comments SET seen = 1 WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)");
$updateSQL->bind_param("i", $userId);
$updateSQL->execute();

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>