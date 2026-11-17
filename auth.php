
<?php

// Start the session if one has not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Make sure user is logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Check if user is an admin
function isAdmin() {
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

// Make sure page is admin only
function requireAdmin() {
    requireLogin();

    if (!isAdmin()) {
        http_response_code(403);
        exit("Access Denied.");
    }
}

