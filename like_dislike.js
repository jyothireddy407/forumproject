function likeItem(id, type, target) {
    fetch("like_dislike.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${id}&type=${type}&target=${target}`
    })
    .then(response => response.json())
    .then(data => {
        if (target === "post") {
            document.getElementById(`post-likes-${id}`).innerText = data.likes;
            document.getElementById(`post-dislikes-${id}`).innerText = data.dislikes;
        } else if (target === "comment") {
            document.getElementById(`comment-likes-${id}`).innerText = data.likes;
            document.getElementById(`comment-dislikes-${id}`).innerText = data.dislikes;
        }
    })
    .catch(error => console.error("Error:", error));
}

// Functions for Posts
function likePost(postId, type) {
    likeItem(postId, type, "post");
}

// Functions for Comments
function likeComment(commentId, type) {
    likeItem(commentId, type, "comment");
}
