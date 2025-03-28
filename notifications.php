<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];

// Get all notifications
$notificationQuery = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$notificationQuery->bind_param("i", $userId);
$notificationQuery->execute();
$notificationResult = $notificationQuery->get_result();

// Mark notifications as read
$updateSQL = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$updateSQL->bind_param("i", $userId);
$updateSQL->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Cosmic Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; text-align: center; background: #f8f8f8; }
        .notification-box { background: white; padding: 20px; margin: 20px auto; width: 50%; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .notification { border-bottom: 1px solid #ddd; padding: 10px; }
        .notification:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <h2>Notifications</h2>
    <div class="notification-box">
        <?php if ($notificationResult->num_rows > 0): ?>
            <?php while ($notification = $notificationResult->fetch_assoc()): ?>
                <div class="notification">
                    <p><?php echo htmlspecialchars($notification["message"]); ?></p>
                    <small>Time: <?php echo $notification["created_at"]; ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notifications yet.</p>
        <?php endif; ?>
    </div>
    <a href="index2.php" class="btn btn-primary">Back to Forum</a>
</body>
</html>