<?php
// Database credentials
$host = "localhost";
$user = "root";
$pass = "";
$db = "happytooth";
$port = 3306;

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
