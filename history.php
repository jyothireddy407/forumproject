<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errorMessage = '';

// Set timezone to match server/database (adjust as needed)
date_default_timezone_set('UTC'); // Example: Use your server's timezone

try {
    $postsStmt = $conn->prepare("SELECT id, title, content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $postsStmt->bind_param("i", $userId);
    $postsStmt->execute();
    $postsResult = $postsStmt->get_result();

    $commentsStmt = $conn->prepare(
        "SELECT c.id, c.content, c.created_at, p.id AS post_id, p.title AS post_title 
         FROM comments c 
         JOIN posts p ON c.post_id = p.id 
         WHERE c.user_id = ? 
         ORDER BY c.created_at DESC"
    );
    $commentsStmt->bind_param("i", $userId);
    $commentsStmt->execute();
    $commentsResult = $commentsStmt->get_result();

    // Handle updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_post'])) {
            $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            
            if ($postId && $title && $content) {
                $stmt = $conn->prepare(
                    "UPDATE posts SET title = ?, content = ? 
                     WHERE id = ? AND user_id = ? AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at) <= 600"
                );
                $stmt->bind_param("ssii", $title, $content, $postId, $userId);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    header("Location: history.php?success=post_updated");
                    exit;
                } else {
                    $errorMessage = "Failed to update post or edit window expired";
                }
                $stmt->close();
            } else {
                $errorMessage = "Invalid post data";
            }
        }
        
        if (isset($_POST['update_comment'])) {
            $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
            $content = trim($_POST['content']);
            
            if ($commentId && $content) {
                $stmt = $conn->prepare(
                    "UPDATE comments SET content = ? 
                     WHERE id = ? AND user_id = ? AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at) <= 600"
                );
                $stmt->bind_param("sii", $content, $commentId, $userId);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    header("Location: history.php?success=comment_updated");
                    exit;
                } else {
                    $errorMessage = "Failed to update comment or edit window expired";
                }
                $stmt->close();
            } else {
                $errorMessage = "Invalid comment data";
            }
        }
    }
} catch (Exception $e) {
    error_log("History page error: " . $e->getMessage());
    $errorMessage = "An error occurred while loading your history: " . $e->getMessage();
}

function isEditable($createdAt) {
    $currentTime = time();
    $createdTime = strtotime($createdAt);
    $timeDiff = $currentTime - $createdTime;
    
    // Debugging output (remove in production)
    echo "<!-- Debug: Current Time: " . date('Y-m-d H:i:s', $currentTime) . 
         ", Created At: " . $createdAt . 
         ", Time Diff: $timeDiff seconds, Editable: " . ($timeDiff <= 600 ? 'Yes' : 'No') . " -->\n";
    
    return $timeDiff <= 600; // 600 seconds = 10 minutes
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View your posting and commenting history">
    <title>My History - Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="background"></div>
    <div class="container">
        <header>
            <h1><i class="fas fa-history"></i> My History</h1>
            <a href="index2.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php echo $_GET['success'] === 'post_updated' ? 'Post updated successfully!' : 'Comment updated successfully!'; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <section class="history-section">
            <h2><i class="fas fa-pen"></i> My Posts</h2>
            <div class="items-container">
                <?php if ($postsResult->num_rows > 0): ?>
                    <?php while ($post = $postsResult->fetch_assoc()): ?>
                        <div class="item post" id="post-<?php echo $post['id']; ?>">
                            <div class="item-header">
                                <h3 id="title-display-<?php echo $post['id']; ?>">
                                    <a href="index2.php?post_id=<?php echo $post['id']; ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <span class="timestamp">
                                    <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                </span>
                            </div>
                            <div class="content-wrapper">
                                <p class="content" id="content-display-<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['content']); ?>
                                </p>
                                <form method="POST" class="edit-form" id="edit-form-post-<?php echo $post['id']; ?>">
                                    <input type="text" 
                                           name="title" 
                                           class="edit-title" 
                                           value="<?php echo htmlspecialchars($post['title']); ?>" 
                                           required>
                                    <textarea name="content" 
                                              class="edit-content" 
                                              required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="update_post" value="1">
                                    <div class="edit-actions">
                                        <button type="submit" class="save-btn" title="Save">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                        <button type="button" class="cancel-btn" 
                                                title="Cancel"
                                                onclick="toggleEdit('post', <?php echo $post['id']; ?>, false)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="actions">
                                <?php if (isEditable($post['created_at'])): ?>
                                    <button class="edit-btn" 
                                            title="Edit Post"
                                            onclick="toggleEdit('post', <?php echo $post['id']; ?>, true)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <form method="POST" action="delete_post.php" class="delete-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="delete-btn" 
                                            title="Delete Post"
                                            onclick="return confirm('Are you sure you want to delete this post?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-content">You haven't created any posts yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="history-section">
            <h2><i class="fas fa-comment"></i> My Comments</h2>
            <div class="items-container">
                <?php if ($commentsResult->num_rows > 0): ?>
                    <?php while ($comment = $commentsResult->fetch_assoc()): ?>
                        <div class="item comment" id="comment-<?php echo $comment['id']; ?>">
                            <div class="item-header">
                                <p class="post-link">
                                    On: <a href="index2.php?post_id=<?php echo $comment['post_id']; ?>">
                                        <?php echo htmlspecialchars($comment['post_title']); ?>
                                    </a>
                                </p>
                                <span class="timestamp">
                                    <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>
                            <div class="content-wrapper">
                                <p class="content" id="content-display-<?php echo $comment['id']; ?>">
                                    <?php echo htmlspecialchars($comment['content']); ?>
                                </p>
                                <form method="POST" class="edit-form" id="edit-form-comment-<?php echo $comment['id']; ?>">
                                    <textarea name="content" 
                                              class="edit-content" 
                                              required><?php echo htmlspecialchars($comment['content']); ?></textarea>
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <input type="hidden" name="update_comment" value="1">
                                    <div class="edit-actions">
                                        <button type="submit" class="save-btn" title="Save">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                        <button type="button" class="cancel-btn" 
                                                title="Cancel"
                                                onclick="toggleEdit('comment', <?php echo $comment['id']; ?>, false)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="actions">
                                <?php if (isEditable($comment['created_at'])): ?>
                                    <button class="edit-btn" 
                                            title="Edit Comment"
                                            onclick="toggleEdit('comment', <?php echo $comment['id']; ?>, true)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <form method="POST" action="delete_comment.php" class="delete-form">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="delete-btn" 
                                            title="Delete Comment"
                                            onclick="return confirm('Are you sure you want to delete this comment?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-content">You haven't made any comments yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
    function toggleEdit(type, id, show) {
        const displayContent = document.getElementById(`content-display-${id}`);
        const displayTitle = type === 'post' ? document.getElementById(`title-display-${id}`) : null;
        const editForm = document.getElementById(`edit-form-${type}-${id}`);
        
        if (show) {
            displayContent.classList.add('hidden');
            if (displayTitle) displayTitle.classList.add('hidden');
            editForm.classList.remove('hidden');
        } else {
            displayContent.classList.remove('hidden');
            if (displayTitle) displayTitle.classList.remove('hidden');
            editForm.classList.add('hidden');
            
            const form = editForm;
            const contentTextarea = form.querySelector('.edit-content');
            contentTextarea.value = displayContent.textContent.trim();
            if (type === 'post') {
                const titleInput = form.querySelector('.edit-title');
                titleInput.value = displayTitle.textContent.trim();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.edit-form').forEach(form => {
            form.classList.add('hidden');
        });
    });
    </script>

    <style>
    :root {
        --primary: #3b82f6;
        --secondary: #9333ea;
        --background: #f0f2f5;
        --card-bg: #ffffff;
        --text: #333333;
        --text-light: #666666;
        --danger: #dc3545;
        --edit: #28a745;
        --save: #17a2b8;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: var(--background);
        min-height: 100vh;
        padding: 15px;
    }

    .background {
        position: fixed;
        inset: 0;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        opacity: 0.1;
        z-index: -1;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    header {
        text-align: center;
        margin-bottom: 1.5rem;
        position: relative;
        padding: 10px 0;
    }

    h1 {
        color: var(--text);
        font-size: 1.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .back-btn {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        padding: 8px;
        font-size: 1rem;
        transition: color 0.3s ease;
    }

    .back-btn:hover {
        color: var(--secondary);
    }

    .history-section {
        margin-bottom: 1.5rem;
    }

    h2 {
        color: var(--text);
        font-size: 1.3rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .items-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .item {
        background: var(--card-bg);
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .item-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
        gap: 0.5rem;
    }

    .item h3, .item .post-link {
        margin: 0;
        font-weight: 500;
        font-size: 1.1rem;
        word-break: break-word;
    }

    .item a {
        color: var(--primary);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .item a:hover {
        color: var(--secondary);
        text-decoration: underline;
    }

    .timestamp {
        color: var(--text-light);
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .content-wrapper {
        position: relative;
    }

    .content {
        color: var(--text);
        line-height: 1.5;
        word-break: break-word;
        margin: 0;
        font-size: 0.95rem;
    }

    .edit-form {
        margin-top: 0.5rem;
    }

    .edit-title {
        width: 100%;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.95rem;
    }

    .edit-content {
        width: 100%;
        min-height: 80px;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        resize: vertical;
        font-size: 0.95rem;
    }

    .edit-actions, .actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .edit-btn, .delete-btn, .save-btn, .cancel-btn {
        padding: 0.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
        color: white;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        touch-action: manipulation;
    }

    .edit-btn {
        background: var(--edit);
    }

    .edit-btn:hover, .edit-btn:active {
        background: #218838;
        transform: scale(1.05);
    }

    .delete-btn {
        background: var(--danger);
    }

    .delete-btn:hover, .delete-btn:active {
        background: #c82333;
        transform: scale(1.05);
    }

    .save-btn {
        background: var(--save);
    }

    .save-btn:hover, .save-btn:active {
        background: #138496;
        transform: scale(1.05);
    }

    .cancel-btn {
        background: #6c757d;
    }

    .cancel-btn:hover, .cancel-btn:active {
        background: #5a6268;
        transform: scale(1.05);
    }

    .hidden {
        display: none;
    }

    .no-content {
        text-align: center;
        color: var(--text-light);
        font-style: italic;
        padding: 1rem;
        font-size: 0.95rem;
    }

    .error-message, .success-message {
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        text-align: center;
        font-size: 0.9rem;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
    }

    .success-message {
        background: #d4edda;
        color: #155724;
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }

        .container {
            max-width: 100%;
        }

        h1 {
            font-size: 1.5rem;
        }

        .back-btn {
            font-size: 0.9rem;
            padding: 5px;
        }

        h2 {
            font-size: 1.2rem;
        }

        .item {
            padding: 0.75rem;
        }

        .item h3, .item .post-link {
            font-size: 1rem;
        }

        .timestamp {
            font-size: 0.8rem;
        }

        .content {
            font-size: 0.9rem;
        }

        .edit-title, .edit-content {
            font-size: 0.9rem;
        }

        .edit-btn, .delete-btn, .save-btn, .cancel-btn {
            min-width: 36px;
            height: 36px;
            font-size: 0.85rem;
        }

        .edit-actions, .actions {
            justify-content: space-between;
        }
    }

    @media (max-width: 480px) {
        h1 {
            font-size: 1.3rem;
        }

        .back-btn {
            position: static;
            display: inline-flex;
            margin-bottom: 0.5rem;
        }

        header {
            text-align: left;
            padding: 5px 0;
        }

        .item-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .edit-actions, .actions {
            flex-direction: column;
            gap: 0.3rem;
        }

        .edit-btn, .delete-btn, .save-btn, .cancel-btn {
            width: 100%;
            height: 40px;
            justify-content: center;
        }
    }
    </style>
</body>
</html>
<?php
if (isset($postsStmt)) $postsStmt->close();
if (isset($commentsStmt)) $commentsStmt->close();
?>