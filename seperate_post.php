<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Details | Forum</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            height: 100vh;
            position: sticky;
            top: 0;
            background: #ffffff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: #495057;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #0d6efd;
            background: #e9ecef;
            border-radius: 5px;
        }
        .content-card {
            border: none;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .comment-card {
            border-radius: 15px;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        .comment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-accent {
            background-color: #0d6efd;
            border: none;
            color: white;
            transition: all 0.3s;
        }
        .btn-accent:hover {
            background-color: #0a58ca;
            color: white;
            transform: scale(1.03);
        }
        .post-image {
            max-height: 450px;
            object-fit: cover;
            border-radius: 10px;
        }
        .comment-image {
            max-height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }
        .badge-user {
            background-color: #6c757d;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e9ecef;
            color: #0d6efd;
        }
    </style>
    <script src="like_dislike.js" defer></script>
    <script>
        function toggleComments(postId) {
            const commentList = document.getElementById(`comment-list-${postId}`);
            const hiddenComments = commentList.querySelectorAll('.hidden-comment');
            const btn = commentList.nextElementSibling;

            const isHidden = hiddenComments[0]?.classList.contains('d-none');
            hiddenComments.forEach(comment => comment.classList.toggle('d-none', !isHidden));
            btn.innerHTML = isHidden ? 'Hide Comments <i class="bi bi-chevron-up"></i>' : 'Show More Comments <i class="bi bi-chevron-down"></i>';
        }
    </script>
</head>
<body>
    <?php
    session_start();
    include "db.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "<div class='container mt-5'><div class='alert alert-danger' role='alert'>Invalid post ID.</div></div>";
        exit;
    }

    $postId = $conn->real_escape_string($_GET['id']);
    $postQuery = "SELECT posts.*, users.username, 
                     (SELECT COUNT(*) FROM post_likes WHERE post_likes.post_id = posts.id AND type='like') AS likes,
                     (SELECT COUNT(*) FROM post_likes WHERE post_likes.post_id = posts.id AND type='dislike') AS dislikes
                  FROM posts 
                  JOIN users ON posts.user_id = users.id 
                  WHERE posts.id = $postId";
    $postResult = $conn->query($postQuery);

    if ($postResult->num_rows == 0) {
        echo "<div class='container mt-5'><div class='alert alert-warning' role='alert'>Post not found.</div></div>";
        exit;
    }

    $post = $postResult->fetch_assoc();
    ?>

    <!-- Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <h4 class="text-center py-3 text-primary"><i class="bi bi-chat-square-text me-2"></i>Forum</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-house-door me-2"></i>Back to Forum</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="history.php"><i class="bi bi-clock-history me-2"></i>History</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                    <div class="mt-4 text-center">
                        <span class="badge bg-secondary p-2"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="content-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="card-title text-primary mb-3"><?php echo htmlspecialchars($post['title']); ?></h2>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="img-fluid post-image mb-3" loading="lazy">
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">
                                <i class="bi bi-person-fill me-1"></i>
                                <?php 
                                    echo htmlspecialchars($post['username']); 
                                    if ($post['user_id'] == $_SESSION['user_id']) {
                                        echo " <span class='badge badge-user'>You</span>";
                                    }
                                ?> 
                                <small class="ms-2"><?php echo $post['created_at']; ?></small>
                            </span>
                            <div>
                                <button class="btn btn-outline-success btn-sm me-2" onclick="likePost(<?php echo $post['id']; ?>, 'like')">
                                    <i class="bi bi-hand-thumbs-up"></i> <span id="post-likes-<?php echo $post['id']; ?>"><?php echo $post['likes']; ?></span>
                                </button>
                                <button class="btn btn-outline-danger btn-sm me-2" onclick="likePost(<?php echo $post['id']; ?>, 'dislike')">
                                    <i class="bi bi-hand-thumbs-down"></i> <span id="post-dislikes-<?php echo $post['id']; ?>"><?php echo $post['dislikes']; ?></span>
                                </button>
                            </div>
                        </div>

                        <!-- Reply Form in Accordion -->
                        <div class="accordion" id="replyAccordion-<?php echo $post['id']; ?>">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#replyCollapse-<?php echo $post['id']; ?>" aria-expanded="false" aria-controls="replyCollapse-<?php echo $post['id']; ?>">
                                        <i class="bi bi-reply me-2"></i>Reply to Post
                                    </button>
                                </h2>
                                <div id="replyCollapse-<?php echo $post['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#replyAccordion-<?php echo $post['id']; ?>">
                                    <div class="accordion-body">
                                        <form method="POST" action="add_comment.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <div class="mb-3">
                                                <label for="comment-<?php echo $post['id']; ?>" class="form-label">Comment</label>
                                                <textarea name="content" id="comment-<?php echo $post['id']; ?>" class="form-control" rows="2" required placeholder="Type your comment..."></textarea>
                                                <div class="invalid-feedback">Please enter a comment.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="comment-image-<?php echo $post['id']; ?>" class="form-label">Upload Image</label>
                                                <input type="file" name="image" id="comment-image-<?php echo $post['id']; ?>" class="form-control" accept="image/*">
                                            </div>
                                            <button type="submit" class="btn btn-accent"><i class="bi bi-send me-1"></i>Post Comment</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <h4 class="mb-3 text-primary"><i class="bi bi-chat-left-text me-2"></i>Comments</h4>
                <?php
                $commentsQuery = "SELECT comments.*, users.username, 
                    (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id AND type='like') AS likes,
                    (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id AND type='dislike') AS dislikes
                    FROM comments 
                    JOIN users ON comments.user_id = users.id 
                    WHERE comments.post_id = $postId 
                    ORDER BY likes DESC, comments.created_at DESC";

                $commentsResult = $conn->query($commentsQuery);
                $commentCount = $commentsResult->num_rows;

                if ($commentCount > 0) {
                    $commentIndex = 0;
                    echo '<div class="comment-list" id="comment-list-' . $postId . '">';
                    
                    while ($comment = $commentsResult->fetch_assoc()) {
                        $hiddenClass = ($commentIndex > 0) ? 'hidden-comment d-none' : '';
                        echo "<div class='card comment-card mb-3 $hiddenClass'>";
                        echo "<div class='card-body'>";
                        echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
                        echo "<span><strong class='text-primary'>" . htmlspecialchars($comment['username']);
                        if ($comment['user_id'] == $_SESSION['user_id']) {
                            echo " <span class='badge badge-user'>You</span>";
                        }
                        echo "</strong></span>";
                        echo "<small class='text-muted'>" . $comment['created_at'] . "</small>";
                        echo "</div>";
                        echo "<p class='card-text'>" . htmlspecialchars($comment['content']) . "</p>";

                        if (!empty($comment['image'])) {
                            echo "<img src='" . htmlspecialchars($comment['image']) . "' alt='Comment Image' class='img-fluid comment-image mb-2'>";
                        }

                        echo "<div class='d-flex align-items-center'>";
                        echo "<button class='btn btn-outline-success btn-sm me-2' onclick=\"likeComment({$comment['id']}, 'like')\">";
                        echo "<i class='bi bi-hand-thumbs-up'></i> <span id='comment-likes-{$comment['id']}'>{$comment['likes']}</span>";
                        echo "</button>";
                        echo "<button class='btn btn-outline-danger btn-sm me-2' onclick=\"likeComment({$comment['id']}, 'dislike')\">";
                        echo "<i class='bi bi-hand-thumbs-down'></i> <span id='comment-dislikes-{$comment['id']}'>{$comment['dislikes']}</span>";
                        echo "</button>";
                        echo "</div>";
                        echo "</div></div>";

                        $commentIndex++;
                    }
                    echo '</div>';

                    if ($commentCount > 1) {
                        echo '<button class="btn btn-outline-primary mt-2" onclick="toggleComments(' . $postId . ')">Show More Comments <i class="bi bi-chevron-down"></i></button>';
                    }
                } else {
                    echo "<div class='alert alert-light' role='alert'><i class='bi bi-info-circle me-2'></i>No comments yet. Add one below!</div>";
                }
                ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Form Validation Script -->
    <script>
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>