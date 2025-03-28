<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

// Check session and redirect if necessary
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = $userId";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$postsQuery = "SELECT posts.*, users.username, 
              (SELECT COUNT(*) FROM post_likes WHERE post_likes.post_id = posts.id AND type='like') AS likes,
              (SELECT COUNT(*) FROM post_likes WHERE post_likes.post_id = posts.id AND type='dislike') AS dislikes
           FROM posts 
           JOIN users ON posts.user_id = users.id";
if (!empty($search)) {
    $postsQuery .= " WHERE posts.title LIKE '%$search%' OR posts.content LIKE '%$search%' ";
}
$postsQuery .= " ORDER BY likes DESC, posts.created_at DESC";
$postsResult = $conn->query($postsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum | Cosmic Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #5b21b6;
            --secondary: #9333ea;
            --dark-bg: #1f2937;
            --light-bg: #f9fafb;
        }
        body {
            background: var(--light-bg);
            color: #1f2937;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        body.dark-mode {
            background: var(--dark-bg);
            color: #e5e7eb;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .post-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .dark-mode .post-card {
            background: #374151;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.5);
        }
        .post-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }
        .comment-card {
            background: #f3f4f6;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .dark-mode .comment-card {
            background: #4b5563;
        }
        .post-image, .comment-image {
            max-width: 100%;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.4s ease;
        }
        .post-image:hover {
            transform: scale(1.03);
        }
        .btn-custom {
            background: var(--primary);
            color: white;
            border: none;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            background: var(--secondary);
            transform: scale(1.05);
        }
        .notification-dropdown {
            position: fixed;
            width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            z-index: 1000;
            transform: translateY(-10px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .notification-dropdown.show {
            transform: translateY(0);
            opacity: 1;
        }
        .dark-mode .notification-dropdown {
            background: #2d3748;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .dropdown-header {
            padding: 12px 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dropdown-body {
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #a0aec0 #edf2f7;
        }
        .dark-mode .dropdown-body {
            scrollbar-color: #a0aec0 #2d3748;
        }
        .notifications-list {
            padding: 8px 0;
        }
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #edf2f7;
            transition: background 0.2s;
            cursor: pointer;
        }
        .dark-mode .notification-item {
            border-bottom: 1px solid #4a5568;
        }
        .notification-item:hover {
            background: #f7fafc;
        }
        .dark-mode .notification-item:hover {
            background: #4a5568;
        }
        .notification-item.unread {
            background: #f0f5ff;
        }
        .dark-mode .notification-item.unread {
            background: #3c4b64;
        }
        .notification-time {
            font-size: 0.75rem;
            color: #718096;
        }
        .dark-mode .notification-time {
            color: #a0aec0;
        }
        .dropdown-footer {
            padding: 8px 16px;
            border-top: 1px solid #edf2f7;
        }
        .dark-mode .dropdown-footer {
            border-top: 1px solid #4a5568;
        }
        .hidden {
            display: none;
        }
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
        }
        .animate-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
        .search-form input:focus {
            box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.3);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-cosmic me-2"></i>Cosmic Community</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <span class="navbar-text me-3 fw-semibold"><?php echo htmlspecialchars($user['username']); ?></span>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php"><i class="bi bi-clock-history me-1"></i>History</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="togglePostForm()"><i class="bi bi-plus-circle me-1"></i>New Post</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <button id="notif-btn" class="btn btn-outline-light me-3 position-relative" data-bs-toggle="tooltip" title="Notifications">
                        <i class="bi bi-bell"></i> <span id="notif-count" class="badge bg-danger position-absolute top-0 start-100 translate-middle">0</span>
                    </button>
                    <button id="theme-toggle" class="btn btn-outline-light me-3" data-bs-toggle="tooltip" title="Toggle Theme">🌙</button>
                    <a href="logout.php" class="btn btn-custom"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div id="notif-dropdown" class="notification-dropdown hidden">
        <div class="dropdown-header">
            <h6 class="mb-0 fw-bold">Notifications</h6>
            <button id="notif-close-btn" class="btn-close btn-close-white" aria-label="Close"></button>
        </div>
        <div class="dropdown-body">
            <div id="notif-content" class="notifications-list">
                <div class="text-center text-muted py-3">No new notifications</div>
            </div>
        </div>
        <div class="dropdown-footer">
            <button id="notif-ok-btn" class="btn btn-custom btn-sm w-100">Mark All as Read</button>
        </div>
    </div>

    <div class="container mt-5">
        <div id="postFormContainer" class="collapse">
            <div class="card post-card mx-auto max-w-2xl">
                <div class="card-body p-5">
                    <h3 class="card-title text-primary fw-bold">Create a New Post</h3>
                    <form id="postForm" method="POST" action="create_post.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">Title</label>
                            <input type="text" id="title" name="title" class="form-control border-0 shadow-sm" required>
                            <div class="invalid-feedback">Title is required.</div>
                        </div>
                        <div class="mb-4">
                            <label for="content" class="form-label fw-semibold">Content</label>
                            <textarea id="content" name="content" class="form-control border-0 shadow-sm" rows="5" required></textarea>
                            <div class="invalid-feedback">Content is required.</div>
                        </div>
                        <div class="mb-4">
                            <label for="image" class="form-label fw-semibold">Upload Image</label>
                            <input type="file" id="image" name="image" class="form-control border-0 shadow-sm" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-custom w-full">Post <i class="bi bi-send ms-1"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-6">
        <h2 class="text-center text-3xl font-bold text-white-800 dark:text-white-200 mb-6">Current Discussions</h2>
    </div>

    <div class="container mt-5">
        <div id="searchContainer">
            <form method="GET" action="" class="input-group max-w-lg mx-auto">
                <input type="text" name="search" class="form-control border-0 shadow-sm" placeholder="Search posts..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-custom"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>

    <div class="container my-6">
        <div id="posts" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($post = $postsResult->fetch_assoc()): ?>
                <div class="post-card fade-in-up" data-post-id="<?php echo $post['id']; ?>">
                    <div class="card-body p-5">
                        <h3 class="card-title text-xl font-semibold">
                            <a href="#" class="text-primary hover:underline" onclick="showSinglePost(<?php echo $post['id']; ?>)">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        <p class="card-text post-content text-gray-600 dark:text-gray-300 mt-2"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image mt-3" loading="lazy">
                        <?php endif; ?>
                        <small class="text-muted d-block mt-3">
                            <i class="bi bi-person-fill me-1"></i>
                            <?php 
                                echo htmlspecialchars($post['username']); 
                                if ($post['user_id'] == $_SESSION['user_id']) {
                                    echo " <span class='badge bg-secondary'>You</span>";
                                }
                            ?>
                        </small>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="flex space-x-2">
                                <button class="btn btn-outline-success btn-sm btn-custom" onclick="likePost(<?php echo $post['id']; ?>, 'like')" data-bs-toggle="tooltip" title="Like">
                                    <i class="bi bi-hand-thumbs-up"></i> <span id="post-likes-<?php echo $post['id']; ?>"><?php echo $post['likes']; ?></span>
                                </button>
                                <button class="btn btn-outline-danger btn-sm btn-custom" onclick="likePost(<?php echo $post['id']; ?>, 'dislike')" data-bs-toggle="tooltip" title="Dislike">
                                    <i class="bi bi-hand-thumbs-down"></i> <span id="post-dislikes-<?php echo $post['id']; ?>"><?php echo $post['dislikes']; ?></span>
                                </button>
                                <button class="btn btn-outline-primary btn-sm btn-custom" data-bs-toggle="collapse" data-bs-target="#reply-box-<?php echo $post['id']; ?>" aria-expanded="false">
                                    <i class="bi bi-reply"></i> Reply
                                </button>
                            </div>
                        </div>

                        <div id="reply-box-<?php echo $post['id']; ?>" class="collapse mt-4">
                            <form method="POST" action="add_comment.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <div class="mb-3">
                                    <textarea name="content" class="form-control border-0 shadow-sm" placeholder="Your comment..." required></textarea>
                                    <div class="invalid-feedback">Comment is required.</div>
                                </div>
                                <div class="mb-3">
                                    <input type="file" name="image" class="form-control border-0 shadow-sm" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-custom">Comment <i class="bi bi-send ms-1"></i></button>
                            </form>
                        </div>

                        <div class="mt-4">
                            <h5 class="font-semibold">Comments:</h5>
                            <?php
                            $postId = $post['id'];
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
                                    echo "<div class='comment-card $hiddenClass p-3'>";
                                    echo "<p class='text-sm'><strong class='text-primary'>" . htmlspecialchars($comment['username']);
                                    if ($comment['user_id'] == $_SESSION['user_id']) {
                                        echo " <span class='badge bg-secondary'>You</span>";
                                    }
                                    echo ":</strong> <span class='comment-content'>" . htmlspecialchars($comment['content']) . "</span></p>";
                                    if (!empty($comment['image'])) {
                                        echo "<img src='" . htmlspecialchars($comment['image']) . "' alt='Comment Image' class='comment-image mt-2'>";
                                    }
                                    echo "<div class='flex space-x-2 mt-2'>";
                                    echo "<button class='btn btn-outline-success btn-sm' onclick=\"likeComment({$comment['id']}, 'like')\">";
                                    echo "<i class='bi bi-hand-thumbs-up'></i> <span id='comment-likes-{$comment['id']}'>{$comment['likes']}</span>";
                                    echo "</button>";
                                    echo "<button class='btn btn-outline-danger btn-sm' onclick=\"likeComment({$comment['id']}, 'dislike')\">";
                                    echo "<i class='bi bi-hand-thumbs-down'></i> <span id='comment-dislikes-{$comment['id']}'>{$comment['dislikes']}</span>";
                                    echo "</button>";
                                    echo "</div>";
                                    echo "</div>";
                                    $commentIndex++;
                                }
                                echo '</div>';
                                if ($commentCount > 1) {
                                    echo '<button class="btn btn-link mt-2 text-primary" onclick="toggleComments(' . $postId . ')">Show More Comments <i class="bi bi-chevron-down"></i></button>';
                                }
                            } else {
                                echo "<p class='text-muted text-sm'>No comments yet.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div id="cancelButtonContainer" class="text-center mt-4" style="display: none;">
            <button class="btn btn-secondary" onclick="showAllPosts()">Cancel</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const themeToggle = document.getElementById("theme-toggle");
            const body = document.body;
            if (localStorage.getItem("theme") === "dark") {
                body.classList.add("dark-mode");
                themeToggle.textContent = "☀️";
            }
            themeToggle.addEventListener("click", () => {
                body.classList.toggle("dark-mode");
                localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
                themeToggle.textContent = body.classList.contains("dark-mode") ? "☀️" : "🌙";
            });

            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

            const notifBtn = document.getElementById("notif-btn");
            const notifDropdown = document.getElementById("notif-dropdown");
            const notifCount = document.getElementById("notif-count");
            const notifContent = document.getElementById("notif-content");

            function fetchNotifications() {
                fetch("fetch_notifications.php")
                    .then(response => response.json())
                    .then(data => {
                        notifCount.textContent = data.length;
                        if (data.length) {
                            notifContent.innerHTML = data.map(n => `
                                <div class="notification-item ${n.read ? '' : 'unread'}" 
                                     data-id="${n.id}" 
                                     data-post-id="${n.post_id}" 
                                     onclick="showNotificationPost(${n.post_id})">
                                    <p class="mb-1 text-sm">${n.content} on "<strong>${n.title}</strong>"</p>
                                    <span class="notification-time">${new Date(n.created_at).toLocaleTimeString()}</span>
                                </div>
                            `).join("");
                        } else {
                            notifContent.innerHTML = '<div class="text-center text-muted py-3">No new notifications</div>';
                        }
                    })
                    .catch(err => console.error("Error fetching notifications:", err));
            }

            fetchNotifications();
            setInterval(fetchNotifications, 10000);

            notifBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                notifDropdown.classList.toggle("hidden");
                notifDropdown.classList.toggle("show");
            });

            document.addEventListener("click", (e) => {
                if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                    notifDropdown.classList.add("hidden");
                    notifDropdown.classList.remove("show");
                }
            });

            document.getElementById("notif-close-btn").addEventListener("click", () => {
                notifDropdown.classList.add("hidden");
                notifDropdown.classList.remove("show");
            });

            document.getElementById("notif-ok-btn").addEventListener("click", () => {
                fetch("mark_notifications_read.php", { method: "POST" })
                    .then(() => {
                        notifCount.textContent = "0";
                        notifContent.innerHTML = '<div class="text-center text-muted py-3">No new notifications</div>';
                        notifDropdown.classList.add("hidden");
                        notifDropdown.classList.remove("show");
                    });
            });

            document.querySelectorAll(".needs-validation").forEach(form => {
                form.addEventListener("submit", e => {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add("was-validated");
                }, false);
            });

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("animate-in");
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });
            document.querySelectorAll(".post-card").forEach(card => observer.observe(card));

            const maxChars = 200;
            document.querySelectorAll(".post-content, .comment-content").forEach(item => {
                const fullText = item.innerHTML.trim();
                if (fullText.length > maxChars) {
                    const shortText = fullText.substring(0, maxChars) + "...";
                    const readMoreBtn = document.createElement("button");
                    readMoreBtn.textContent = "Read More";
                    readMoreBtn.classList.add("btn", "btn-link", "p-0", "ms-2", "text-primary");
                    item.innerHTML = shortText;
                    item.appendChild(readMoreBtn);
                    readMoreBtn.addEventListener("click", () => {
                        item.innerHTML = readMoreBtn.textContent === "Read More" ? fullText : shortText;
                        item.appendChild(readMoreBtn);
                        readMoreBtn.textContent = readMoreBtn.textContent === "Read More" ? "Read Less" : "Read More";
                    });
                }
            });
        });

        function showSinglePost(postId) {
            const posts = document.querySelectorAll('.post-card');
            posts.forEach(post => {
                if (post.getAttribute('data-post-id') == postId) {
                    post.style.display = 'block';
                } else {
                    post.style.display = 'none';
                }
            });
            document.getElementById('cancelButtonContainer').style.display = 'block';
            document.getElementById('posts').classList.remove('md:grid-cols-2', 'lg:grid-cols-3');
            document.getElementById('posts').classList.add('grid-cols-1');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showAllPosts() {
            const posts = document.querySelectorAll('.post-card');
            posts.forEach(post => {
                post.style.display = 'block';
            });
            document.getElementById('cancelButtonContainer').style.display = 'none';
            document.getElementById('posts').classList.remove('grid-cols-1');
            document.getElementById('posts').classList.add('md:grid-cols-2', 'lg:grid-cols-3');
        }

        function fetchNotifications() {
    fetch("fetch_notifications.php")
        .then(response => response.json())
        .then(data => {
            notifCount.textContent = data.length;
            if (data.length) {
                notifContent.innerHTML = data.map(n => `
                    <div class="notification-item ${n.read ? '' : 'unread'}" 
                         data-id="${n.id}" 
                         data-post-id="${n.post_id}" 
                         onclick="showNotificationPost(${n.post_id})">
                        <p class="mb-1 text-sm">${n.content} on "<strong>${n.title}</strong>"</p>
                        <span class="notification-time">${new Date(n.created_at).toLocaleTimeString()}</span>
                    </div>
                `).join("");
            } else {
                notifContent.innerHTML = '<div class="text-center text-muted py-3">No new notifications</div>';
            }
        })
        .catch(err => console.error("Error fetching notifications:", err));
}

function showNotificationPost(postId) {
    // Ensure postId is valid
    if (!postId) {
        console.error('Invalid post ID');
        return;
    }

    // Find and show the specific post
    const posts = document.querySelectorAll('.post-card');
    let postFound = false;
    
    posts.forEach(post => {
        if (parseInt(post.getAttribute('data-post-id')) === parseInt(postId)) {
            post.style.display = 'block';
            post.scrollIntoView({ behavior: 'smooth', block: 'start' });
            postFound = true;
        } else {
            post.style.display = 'none';
        }
    });

    if (!postFound) {
        console.error('Post not found:', postId);
        // Optionally fetch the post if it's not in the current view
        // fetchPost(postId);
    }

    // Adjust layout
    document.getElementById('cancelButtonContainer').style.display = 'block';
    document.getElementById('posts').classList.remove('md:grid-cols-2', 'lg:grid-cols-3');
    document.getElementById('posts').classList.add('grid-cols-1');

    // Hide notification dropdown
    document.getElementById("notif-dropdown").classList.add("hidden");
    document.getElementById("notif-dropdown").classList.remove("show");

    // Mark notification as read
    fetch("mark_notifications_read.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchNotifications(); // Refresh notifications
        }
    })
    .catch(err => console.error('Error marking notification as read:', err));
}

        function togglePostForm() {
            document.getElementById("postFormContainer").classList.toggle("show");
        }

        function toggleComments(postId) {
            const commentList = document.getElementById(`comment-list-${postId}`);
            const hiddenComments = commentList.querySelectorAll(".hidden-comment");
            const btn = commentList.nextElementSibling;
            const isHidden = hiddenComments[0]?.classList.contains("d-none");
            hiddenComments.forEach(comment => {
                comment.classList.toggle("d-none", !isHidden);
                if (isHidden) comment.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 300 });
            });
            btn.innerHTML = isHidden ? "Show Less <i class='bi bi-chevron-up'></i>" : "Show More Comments <i class='bi bi-chevron-down'></i>";
        }

        function likePost(postId, type) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "like_post.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById(`post-likes-${postId}`).textContent = response.likes;
                        document.getElementById(`post-dislikes-${postId}`).textContent = response.dislikes;
                    } else {
                        alert("Error: " + response.message);
                    }
                }
            };
            xhr.send(`post_id=${postId}&type=${type}`);
        }

        function likeComment(commentId, type) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "like_comment.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById(`comment-likes-${commentId}`).textContent = response.likes;
                        document.getElementById(`comment-dislikes-${commentId}`).textContent = response.dislikes;
                    } else {
                        alert("Error: " + response.message);
                    }
                }
            };
            xhr.send(`comment_id=${commentId}&type=${type}`);
        }
        document.addEventListener("DOMContentLoaded", function() {
    // Get 'post_id' from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const postId = urlParams.get('post_id');

    if (postId) {
        showSinglePost(postId); // Call function with post ID
    }
});
    </script>
</body>
</html>