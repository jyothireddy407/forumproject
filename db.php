<?php
$host = "localhost";
$user = "root"; // Change if using a different database user
$pass = "";
$dbname = "dummyforum";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
