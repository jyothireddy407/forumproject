<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);

    // Check if the email exists
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $otp = rand(100000, 999999); // Generate 6-digit OTP
        $_SESSION['reset_email'] = $email; // Store email in session
        $_SESSION['otp'] = $otp; // Store OTP in session

        // Send OTP to email (Using PHP mail function)
        $subject = "Password Reset OTP";
        $message = "Your OTP for password reset is: $otp";
        $headers = "From: logiclordrkv@gmail.com";

        if (mail($email, $subject, $message, $headers)) {
            header("Location: verify_otp.php"); // Redirect to OTP page
            exit();
        } else {
            echo "Error sending OTP. Please try again.";
        }
    } else {
        echo "Email not found!";
    }
}
?>
