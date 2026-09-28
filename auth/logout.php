<?php
session_start();
session_unset();    // Unset all session variables
session_destroy();  // Destroy the session

// Prevent browser from caching the previous page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect to login page or homepage
header("Location: ../auth/login.php");
exit;
?>
