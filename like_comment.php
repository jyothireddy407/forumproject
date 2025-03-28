<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$commentId = $_POST['comment_id'];
$type = $_POST['type']; // 'like' or 'dislike'

$conn->begin_transaction();

try {
    // Check if user already liked/disliked this comment
    $checkQuery = "SELECT type FROM comment_likes WHERE comment_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $commentId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();

    if ($existing) {
        if ($existing['type'] === $type) {
            // Remove the existing like/dislike
            $deleteQuery = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("ii", $commentId, $userId);
            $stmt->execute();
        } else {
            // Update to new type
            $updateQuery = "UPDATE comment_likes SET type = ? WHERE comment_id = ? AND user_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sii", $type, $commentId, $userId);
            $stmt->execute();
        }
    } else {
        // Insert new like/dislike
        $insertQuery = "INSERT INTO comment_likes (comment_id, user_id, type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iis", $commentId, $userId, $type);
        $stmt->execute();
    }

    // Get updated counts
    $likesQuery = "SELECT COUNT(*) as likes FROM comment_likes WHERE comment_id = ? AND type = 'like'";
    $stmt = $conn->prepare($likesQuery);
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $likes = $stmt->get_result()->fetch_assoc()['likes'];

    $dislikesQuery = "SELECT COUNT(*) as dislikes FROM comment_likes WHERE comment_id = ? AND type = 'dislike'";
    $stmt = $conn->prepare($dislikesQuery);
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $dislikes = $stmt->get_result()->fetch_assoc()['dislikes'];

    $conn->commit();
    echo json_encode(['success' => true, 'likes' => $likes, 'dislikes' => $dislikes]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>