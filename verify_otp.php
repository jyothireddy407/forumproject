<?php
include "db.php";
session_start();

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verify_otp"])) {
    $entered_otp = trim($_POST["otp"]);

    if ($entered_otp == $_SESSION['otp']) {
        $_SESSION['otp_verified'] = true;
        $success = "OTP verified successfully! Proceed with registration.";
        header("Location: complete_registration.php");
        exit();
    } else {
        $error = "Invalid OTP!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify OTP</h2>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>
    
    <form method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit" name="verify_otp">Verify</button>
    </form>
</body>
</html>
