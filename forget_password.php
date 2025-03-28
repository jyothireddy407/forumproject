<?php
session_start();
include "db.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);

    // Check if email exists
    $userQuery = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($userQuery);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $otp = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // ✅ Fixed SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'logiclordrkv@gmail.com'; // ✅ Your Gmail
            $mail->Password = 'vxtv fqth thrk ebuy'; // ✅ Use an **App Password** if needed
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('logiclordrkv@gmail.com', 'Forum Support'); // ✅ Fixed sender email
            $mail->addAddress($email);
            $mail->Subject = "Password Reset OTP";
            $mail->Body = "Your OTP for password reset is: $otp";

            $mail->send();
            header("Location: verify_reset_otp.php");
            exit();
        } catch (Exception $e) {
            echo "Error sending email: " . $mail->ErrorInfo;
        }
    } else {
        echo "<p style='color:red;'>Email not found!</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles2.css">
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" class="input-field" placeholder="Enter your email" required>
            <button type="submit" class="btn">Send OTP</button>
        </form>
    </div>
</body>
</html>
