<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['otp']) {
        header("Location: reset_password.php"); // Redirect to password reset page
        exit();
    } else {
        echo "Invalid OTP. Try again.";
    }
}
?>
