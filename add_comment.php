<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to comment.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'];
    $content = trim($_POST['content']);
    $imagePath = null;

    if (empty($content) && empty($_FILES['image']['name'])) {
        die("Comment cannot be empty.");
    }

    // 🟢 Handle Image Upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/comments/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $imageName = basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . time() . "_" . $imageName;
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                $imagePath = $targetFilePath;
            } else {
                die("Error uploading image.");
            }
        } else {
            die("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
        }
    }

    // 🟢 Insert Comment Using Prepared Statement
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $postId, $userId, $content, $imagePath);

    if ($stmt->execute()) {
        header("Location: index2.php");
        exit;
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
}
?>