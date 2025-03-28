<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        $conn->query("DELETE FROM users WHERE id = $userId");
        $message = "User deleted successfully.";
    } elseif (isset($_POST['toggle_admin'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['current_role'] == 1 ? 0 : 1;
        $conn->query("UPDATE users SET is_admin = $newRole WHERE id = $userId");
        $message = "User role updated successfully.";
    }
    header("Location: admin.php?message=" . urlencode($message));
    exit;
}

$search = isset($_GET['search']) ? $_GET['search'] : "";
$usersQuery = "SELECT id, username, email, is_admin FROM users WHERE username LIKE '%$search%' OR email LIKE '%$search%'";
$usersResult = $conn->query($usersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this user?");
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1c1c3c, #121212);
            color: white;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        h1 {
            margin-top: 20px;
            font-size: 2.5rem;
        }
        .message {
            color: lime;
            margin: 10px;
        }
        .search-box {
            margin: 20px;
        }
        input[type="text"] {
            padding: 8px;
            width: 250px;
            border-radius: 5px;
        }
        button {
            padding: 8px;
            cursor: pointer;
            border-radius: 5px;
        }
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background: #222;
            box-shadow: 0px 0px 15px rgba(0, 255, 255, 0.4);
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            border: 1px solid #444;
        }
        th {
            background: #00aaff;
            color: white;
        }
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .delete-btn {
            background: red;
            color: white;
        }
        .admin-btn {
            background: green;
            color: white;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: cyan;
            color: black;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover {
            background: deepskyblue;
        }
    </style>
</head>
<body>
    <h1>Admin Panel - Manage Users</h1>
    <?php if (isset($_GET['message'])): ?>
        <p class="message"> <?php echo htmlspecialchars($_GET['message']); ?> </p>
    <?php endif; ?>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php while ($user = $usersResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo $user['is_admin'] ? 'Admin' : 'User'; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="current_role" value="<?php echo $user['is_admin']; ?>">
                        <button type="submit" name="toggle_admin" class="btn admin-btn">
                            <?php echo $user['is_admin'] ? 'Demote to User' : 'Promote to Admin'; ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete();">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" name="delete_user" class="btn delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="index2.php" class="back-btn">Back to Forum</a>
    <a href="logout.php" class="back-btn">Logout</a>
</body>
</html>
