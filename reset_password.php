<?php
session_start();
include "db.php";

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    die("Unauthorized access!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];

    $updateQuery = "UPDATE users SET password='$new_password' WHERE email='$email'";
    if ($conn->query($updateQuery) === TRUE) {
        echo "<p style='color:green;'>Password reset successfully! <a href='login.php'>Login</a></p>";
        session_destroy();
    } else {
        echo "<p style='color:red;'>Error resetting password.</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles2.css">
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
        <form method="POST">
            <input type="password" name="new_password" class="input-field" placeholder="New Password" required>
            <input type="password" name="confirm_password" class="input-field" placeholder="Confirm Password" required>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</body>
</html>
