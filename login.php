<?php
session_start();
include "db.php";
require_once 'vendor/autoload.php';
use Google\Client;
use Google\Service\Oauth2;

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignore comments
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
    }
}

// Use environment variables
$clientID = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');

$redirectUri = 'https://25b0-2409-40f0-203c-14fa-eb3d-889d-9289-4e81.ngrok-free.app/dummyprojectfinal/login.php';
// http://localhost/dummyprojectfinal/login.php
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            $error = "Token error: " . $token['error'];
        } else {
            $client->setAccessToken($token['access_token']);
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            $email = $google_account_info->email;
            $google_id = $google_account_info->id;
            echo "Email: $email, Google ID: $google_id<br>";

            if (strpos($email, '@rguktrkv.ac.in') !== false) {
                $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ?");
                $stmt->bind_param("s", $google_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["is_admin"] = $user["is_admin"];
                    if ($user["is_admin"] == 1) {
                        header("Location: admin.php");
                    } else {
                        header("Location: index2.php");
                    }
                    exit;
                } else {
                    $username = explode('@', $email)[0];
                    $password = ''; // Empty password for Google users
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin, google_id) VALUES (?, ?, ?, 0, ?)");
                    $stmt->bind_param("ssss", $username, $email, $password, $google_id);
                    if ($stmt->execute()) {
                        $_SESSION["user_id"] = $conn->insert_id;
                        $_SESSION["username"] = $username;
                        $_SESSION["is_admin"] = 0;
                        header("Location: index2.php");
                        exit;
                    } else {
                        $error = "Error registering user: " . $conn->error;
                    }
                }
            } else {
                $error = "Only @rguktrkv.ac.in emails are allowed.";
            }
        }
    } catch (Exception $e) {
        $error = "An error occurred: " . $e->getMessage();
    }
}

// Traditional Login Handling with Prepared Statement
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["is_admin"] = $user["is_admin"];
            if ($user["is_admin"] == 1) {
                header("Location: admin.php");
            } else {
                header("Location: index2.php");
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Cool Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(45deg, #667eea, #764ba2, #6b7280, #f472b6);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            overflow: hidden;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            margin-bottom: 25px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .input-field {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 10px rgba(118, 75, 162, 0.5);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(118, 75, 162, 0.7);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn:hover::after {
            width: 200%;
            height: 200%;
        }

        .google-btn {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: none;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 14px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .google-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.5);
        }

        .google-btn img {
            width: 24px;
            margin-right: 12px;
        }

        .error {
            color: #ff6b6b;
            font-size: 14px;
            margin-top: 15px;
            background: rgba(255, 107, 107, 0.2);
            padding: 10px;
            border-radius: 8px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .signup-link {
            margin-top: 20px;
            display: block;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .signup-link:hover {
            color: #f472b6;
            text-shadow: 0 0 10px rgba(244, 114, 182, 0.7);
        }

        /* Spinner for Google Login */
        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Particle Background */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }
    </style>
</head>
<body>
    <!-- Particle Background -->
    <div id="particles-js"></div>

    <div class="login-container">
        <h2>Super Cool Login</h2>
        
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" class="input-field" placeholder="Username" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <button type="submit" class="btn">Login</button>
        </form>

        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn google-btn" id="google-login">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google Logo">
            Login with Google
        </a>
        
        <a href="register.php" class="signup-link">Don't have an account? Sign up</a>
        <a href="forget_password.php" class="signup-link">Forgot Password?</a>
    </div>

    <!-- Particle.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        // Particle.js Configuration
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.4, "width": 1 },
                "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out" }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "repulse" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
                "modes": { "repulse": { "distance": 100, "duration": 0.4 }, "push": { "particles_nb": 4 } }
            },
            "retina_detect": true
        });

        // Loading Spinner for Google Login
        document.getElementById('google-login').addEventListener('click', function() {
            this.classList.add('loading');
            this.textContent = 'Connecting to Google...';
        });
    </script>
</body>
</html>