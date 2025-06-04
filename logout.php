<?php
session_start(); // Start the session

// Destroy the session
session_destroy();

// Redirect to the homepage
header("Location: index.php");
exit();
?>
