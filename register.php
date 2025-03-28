<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require 'vendor/autoload.php';
include "db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class RegistrationHandler {
    private $conn;
    private $mail;
    const OTP_EXPIRY = 300; // 5 minutes in seconds
    const EMAIL_DOMAIN = '@rguktrkv.ac.in';

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->mail = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer() {
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'logiclordrkv@gmail.com';
        $this->mail->Password = 'vxtv fqth thrk ebuy';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->setFrom('logiclordrkv@gmail.com', 'Forum Registration');
    }

    public function handleRequest() {
        $response = ['success' => '', 'error' => ''];

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            return $response;
        }

        if (isset($_POST["send_otp"])) {
            return $this->sendOtp($_POST["email"] ?? '');
        }

        if (isset($_POST["register"])) {
            return $this->registerUser(
                $_POST["username"] ?? '',
                $_POST["password"] ?? '',
                $_POST["otp"] ?? ''
            );
        }

        return $response;
    }

    private function sendOtp($email) {
        $response = ['success' => '', 'error' => ''];
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        if (!$this->isValidEmail($email)) {
            $response['error'] = "Please enter a valid @rguktrkv.ac.in email!";
            return $response;
        }

        if ($this->isEmailRegistered($email)) {
            $response['error'] = "Email already registered!";
            return $response;
        }

        $otp = sprintf("%06d", rand(100000, 999999));
        $_SESSION["email"] = $email;

        if ($this->storeOtp($email, $otp) && $this->sendEmail($email, $otp)) {
            $response['success'] = "OTP sent to your email. Check your inbox/spam!";
        } else {
            $response['error'] = "Failed to send OTP. Please try again.";
        }

        return $response;
    }

    private function registerUser($username, $password, $otp) {
        $response = ['success' => '', 'error' => ''];
        
        if (!isset($_SESSION["email"])) {
            $response['error'] = "Please request an OTP first!";
            return $response;
        }

        // Replace deprecated FILTER_SANITIZE_STRING with custom sanitization
        $username = $this->sanitizeString(trim($username));
        $otp = $this->sanitizeString(trim($otp));
        $email = $_SESSION["email"];

        if (!$this->validateInputs($username, $password)) {
            $response['error'] = "Invalid username or password!";
            return $response;
        }

        $otpData = $this->verifyOtp($email, $otp);
        if (!$otpData['valid']) {
            $response['error'] = $otpData['message'];
            return $response;
        }

        if ($this->isUsernameTaken($username)) {
            $response['error'] = "Username already taken!";
            return $response;
        }

        // Use PASSWORD_BCRYPT instead of PASSWORD_ARGON2ID
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        if ($this->saveUser($username, $email, $passwordHash)) {
            $this->cleanupOtp($email);
            unset($_SESSION["email"]);
            $response['success'] = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $response['error'] = "Registration failed. Please try again.";
        }

        return $response;
    }

    private function sanitizeString($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    private function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && 
               str_ends_with($email, self::EMAIL_DOMAIN);
    }

    private function validateInputs($username, $password) {
        return strlen($username) >= 3 && 
               strlen($username) <= 20 && 
               preg_match('/^[a-zA-Z0-9_]+$/', $username) && 
               strlen($password) >= 8;
    }

    private function isEmailRegistered($email) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    private function isUsernameTaken($username) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    private function storeOtp($email, $otp) {
        $stmt = $this->conn->prepare(
            "INSERT INTO otp_verifications (email, otp) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE otp=?, created_at=NOW()"
        );
        $stmt->bind_param("sss", $email, $otp, $otp);
        return $stmt->execute();
    }

    private function sendEmail($email, $otp) {
        try {
            $this->mail->addAddress($email);
            $this->mail->Subject = 'Your Verification OTP';
            $this->mail->isHTML(true);
            $this->mail->Body = "
                <h2>OTP Verification</h2>
                <p>Your OTP is: <strong>$otp</strong></p>
                <p>This code expires in 5 minutes.</p>
            ";
            return $this->mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    private function verifyOtp($email, $otp) {
        $stmt = $this->conn->prepare(
            "SELECT otp, created_at FROM otp_verifications WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $timeDiff = time() - strtotime($row['created_at']);
            if ($timeDiff > self::OTP_EXPIRY) {
                return ['valid' => false, 'message' => 'OTP has expired!'];
            }
            if ($row['otp'] !== $otp) {
                return ['valid' => false, 'message' => 'Invalid OTP!'];
            }
            return ['valid' => true, 'message' => ''];
        }
        return ['valid' => false, 'message' => 'No OTP found!'];
    }

    private function saveUser($username, $email, $passwordHash) {
        $stmt = $this->conn->prepare(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $email, $passwordHash);
        return $stmt->execute();
    }

    private function cleanupOtp($email) {
        $stmt = $this->conn->prepare(
            "DELETE FROM otp_verifications WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
    }
}

$handler = new RegistrationHandler($conn);
$response = $handler->handleRequest();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff758c;
            --secondary: #769aff;
            --success: #5cb85c;
            --error: #d9534f;
            --light: #ffffff;
            --shadow: rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            background: var(--light);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--shadow);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .input-field {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(255, 117, 140, 0.3);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border: none;
            color: var(--light);
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }

        .success {
            background: #dff0d8;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .error {
            background: #f2dede;
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .link {
            display: block;
            text-align: center;
            color: var(--primary);
            text-decoration: none;
            margin-top: 1rem;
            transition: color 0.3s;
        }

        .link:hover {
            color: #ff3b6f;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.5rem;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>

        <?php if (!empty($response['error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($response['error']); ?></div>
        <?php endif; ?>

        <?php if (!empty($response['success'])): ?>
            <div class="message success"><?php echo $response['success']; ?></div>
        <?php endif; ?>

        <form method="POST" id="otpForm">
            <div class="form-group">
                <input type="email" 
                       name="email" 
                       class="input-field" 
                       placeholder="Enter your @rguktrkv.ac.in email" 
                       required 
                       value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>"
                       <?php echo isset($_SESSION['email']) ? 'readonly' : ''; ?>>
            </div>
            <?php if (!isset($_SESSION['email'])): ?>
                <button type="submit" name="send_otp" class="btn">Get OTP</button>
            <?php endif; ?>
        </form>

        <?php if (isset($_SESSION['email'])): ?>
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <input type="text" name="username" class="input-field" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="input-field" placeholder="Password (min 8 chars)" required>
                </div>
                <div class="form-group">
                    <input type="text" name="otp" class="input-field" placeholder="Enter OTP" required>
                </div>
                <button type="submit" name="register" class="btn">Register</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="link">Already have an account? Login</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const otpForm = document.getElementById('otpForm');
            if (otpForm) {
                otpForm.addEventListener('submit', (e) => {
                    const email = otpForm.querySelector('input[name="email"]').value;
                    if (!email.endsWith('@rguktrkv.ac.in')) {
                        e.preventDefault();
                        alert('Please use a valid @rguktrkv.ac.in email address!');
                    }
                });
            }

            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', (e) => {
                    const username = registerForm.querySelector('input[name="username"]').value;
                    const password = registerForm.querySelector('input[name="password"]').value;
                    
                    if (username.length < 3 || username.length > 20 || !/^[a-zA-Z0-9_]+$/.test(username)) {
                        e.preventDefault();
                        alert('Username must be 3-20 characters and contain only letters, numbers, or underscores!');
                    }
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long!');
                    }
                });
            }
        });
    </script>
</body>
</html>