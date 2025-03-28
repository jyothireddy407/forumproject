// document.addEventListener("DOMContentLoaded", function () {
//     const notifBtn = document.getElementById("notif-btn");
//     const notifDropdown = document.getElementById("notif-dropdown");
//     const notifCount = document.getElementById("notif-count");
//     const notifOkBtn = document.getElementById("notif-ok-btn");
//     const notifCancelBtn = document.getElementById("notif-cancel-btn"); // Get Cancel button

//     // Fetch notifications without marking them as seen
//     function fetchNotifications() {
//         fetch("fetch_notifications.php?t=" + new Date().getTime())
//             .then(response => response.json())
//             .then(data => {
//                 notifDropdown.innerHTML = ""; // Clear dropdown

//                 if (data.length === 0) {
//                     notifDropdown.innerHTML = "<p>No new notifications.</p>";
//                     notifCount.style.display = "none";
//                     notifOkBtn.style.display = "none";
//                     notifCancelBtn.style.display = "none"; // Hide Cancel button
//                 } else {
//                     notifCount.style.display = "inline-block";
//                     notifCount.textContent = data.length; // Update count

//                     data.forEach(comment => {
//                         const notifItem = document.createElement("p");
//                         notifItem.innerHTML = `<strong>${comment.title}:</strong> ${comment.content.substring(0, 50)}...`;
//                         notifDropdown.appendChild(notifItem);
//                     });

//                     // Append OK & Cancel buttons
//                     notifDropdown.appendChild(notifOkBtn);
//                     notifDropdown.appendChild(notifCancelBtn);
//                     notifOkBtn.style.display = "block";
//                     notifCancelBtn.style.display = "block";
//                 }
//             })
//             .catch(error => console.error("Error fetching notifications:", error));
//     }

//     // Show dropdown when bell icon is clicked
//     notifBtn.addEventListener("click", () => {
//         notifDropdown.classList.toggle("show");
//     });

//     // Hide notifications when OK is clicked and mark them as seen
//     notifOkBtn.addEventListener("click", () => {
//         fetch("mark_notifications_seen.php", { method: "POST" }) // Ensure it's a POST request
//             .then(() => {
//                 notifDropdown.classList.remove("show");
//                 notifCount.style.display = "none"; // Hide count after OK
//             })
//             .catch(error => console.error("Error marking notifications as seen:", error));
//     });

//     // Hide notifications when Cancel is clicked
//     notifCancelBtn.addEventListener("click", () => {
//         notifDropdown.classList.remove("show");
//     });

//     // Fetch notifications every 5 seconds
//     fetchNotifications();
//     setInterval(fetchNotifications, 5000);
// });
