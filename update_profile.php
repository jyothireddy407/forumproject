<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: profile.php?error=Invalid email format");
        exit;
    }

    // Fetch current password from database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $stmt->fetch();
    $stmt->close();
    
    if (!empty($newPassword)) {
        if (!password_verify($currentPassword, $storedPassword)) {
            header("Location: profile.php?error=Incorrect current password");
            exit;
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
    } else {
        $updateQuery = "UPDATE users SET username = ?, email = ? WHERE id = ?";
    }

    if ($stmt = $conn->prepare($updateQuery)) {
        if (!empty($newPassword)) {
            $stmt->bind_param("sssi", $username, $email, $hashedPassword, $userId);
        } else {
            $stmt->bind_param("ssi", $username, $email, $userId);
        }
        
        if ($stmt->execute()) {
            header("Location: index2.php?success=Profile updated successfully");
        } else {
            header("Location: profile.php?error=Error updating profile");
        }
        
        $stmt->close();
    } else {
        header("Location: profile.php?error=Database error");
    }
}
?>
