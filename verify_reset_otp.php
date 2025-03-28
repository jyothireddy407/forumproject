<?php
session_start();
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    die("Invalid access!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);

    if ($entered_otp == $_SESSION['reset_otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        echo "<p style='color:red;'>Invalid OTP!</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link rel="stylesheet" href="styles2.css">
</head>
<body>
    <div class="container">
        <h2>Verify OTP</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
        <form method="POST">
            <input type="text" name="otp" class="input-field" placeholder="Enter OTP" required>
            <button type="submit" name="verify_otp" class="btn">Verify</button>
        </form>
    </div>
</body>
</html>
