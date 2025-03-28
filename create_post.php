<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to post.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $imagePath = NULL;

    if (empty($title) || empty($content)) {
        die("Title and content cannot be empty.");
    }

    // 🟢 Handle Image Upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        // Allowed file types
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedTypes)) {
            die("❌ Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
        }

        // Check for file upload errors
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            die("❌ Upload Error Code: " . $_FILES['image']['error']);
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath;
        } else {
            die("❌ Error: Failed to move uploaded file.");
        }
    }

    // 🟢 Insert into database
    $sql = "INSERT INTO posts (user_id, title, content, image) VALUES ('$userId', '$title', '$content', '$imagePath')";

    if ($conn->query($sql) === TRUE) {
        header("Location: index2.php");
        exit;
    } else {
        die("❌ Database Error: " . $conn->error);
    }
}
?>