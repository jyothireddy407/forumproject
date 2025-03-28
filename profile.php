<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Use try-catch for better error handling
try {
    $stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if (!$user = $userResult->fetch_assoc()) {
        throw new Exception("User not found");
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    header("Location: logout.php"); // Redirect to logout if user not found
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="User Profile Management">
    <title>Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="background-animation"></div>
    <div class="profile-container">
        <div class="profile-header">
            <div class="avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="member-since">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
        </div>

        <div class="profile-form">
            <form action="update_profile.php" method="POST" id="profileForm">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required>
                </div>

                <div class="password-section">
                    <div class="toggle-password">
                        <input type="checkbox" id="change-password" onchange="togglePasswordFields()">
                        <label for="change-password">Change Password</label>
                    </div>

                    <div id="password-fields" class="hidden">
                        <div class="form-group">
                            <label for="current-password"><i class="fas fa-lock"></i> Current Password</label>
                            <div class="password-input">
                                <input type="password" 
                                       id="current-password" 
                                       name="current_password" 
                                       placeholder="Enter current password">
                                <i class="fas fa-eye toggle-visibility" onclick="togglePassword('current-password')"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new-password"><i class="fas fa-key"></i> New Password</label>
                            <div class="password-input">
                                <input type="password" 
                                       id="new-password" 
                                       name="new_password" 
                                       placeholder="Enter new password">
                                <i class="fas fa-eye toggle-visibility" onclick="togglePassword('new-password')"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn save-btn"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="index2.php" class="btn cancel-btn"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>

            <div class="logout-section">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordFields() {
        const passwordFields = document.getElementById('password-fields');
        passwordFields.classList.toggle('hidden');
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const newPassword = document.getElementById('new-password')?.value;

        if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
            e.preventDefault();
            alert('Username must be 3-20 characters and contain only letters, numbers, or underscores');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return;
        }

        if (newPassword && newPassword.length < 8) {
            e.preventDefault();
            alert('New password must be at least 8 characters long');
            return;
        }
    });
    </script>

    <style>
    :root {
        --primary: #3b82f6;
        --secondary: #9333ea;
        --text-light: #ffffff;
        --card-bg: rgba(255, 255, 255, 0.1);
        --shadow: rgba(0, 0, 0, 0.4);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: #0f172a;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow-x: hidden;
    }

    .background-animation {
        position: fixed;
        inset: 0;
        background: linear-gradient(-45deg, var(--primary), #1e3a8a, var(--secondary), #6d28d9);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        z-index: -1;
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .profile-container {
        width: 100%;
        max-width: 480px;
        margin: 20px;
        animation: fadeIn 0.5s ease-in-out;
    }

    .profile-header {
        background: var(--card-bg);
        padding: 2rem 1.5rem;
        border-radius: 15px 15px 0 0;
        text-align: center;
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .avatar {
        font-size: 4rem;
        color: var(--text-light);
        margin-bottom: 1rem;
    }

    h1 {
        color: var(--text-light);
        font-size: 1.8rem;
        font-weight: 600;
    }

    .member-since {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .profile-form {
        background: var(--card-bg);
        padding: 1.5rem;
        border-radius: 0 0 15px 15px;
        backdrop-filter: blur(12px);
        box-shadow: 0 10px 30px var(--shadow);
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-light);
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.2);
        color: var(--text-light);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.3);
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
    }

    .password-input {
        position: relative;
    }

    .toggle-visibility {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
        cursor: pointer;
        opacity: 0.7;
    }

    .toggle-visibility:hover {
        opacity: 1;
    }

    .password-section {
        margin: 1rem 0;
    }

    .toggle-password {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-light);
        cursor: pointer;
        margin-bottom: 1rem;
    }

    .hidden {
        display: none;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 8px;
        color: var(--text-light);
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .save-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
    }

    .cancel-btn {
        background: rgba(255, 255, 255, 0.1);
    }

    .btn:hover {
        transform: translateY(-2px);
        opacity: 0.9;
    }

    .logout-section {
        margin-top: 1.5rem;
        text-align: center;
    }

    .logout-btn {
        color: var(--text-light);
        text-decoration: none;
        opacity: 0.7;
        transition: opacity 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .logout-btn:hover {
        opacity: 1;
    }

    @media (max-width: 480px) {
        .profile-container {
            margin: 10px;
        }

        .profile-header {
            padding: 1.5rem 1rem;
        }

        .profile-form {
            padding: 1rem;
        }

        .form-actions {
            flex-direction: column;
        }
    }
    </style>
</body>
</html>