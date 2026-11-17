<?php

require_once "auth.php";
require_once "csrf.php";

//only allow logouts through POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify the CSRF token
verifyCSRFToken($_POST['csrf_token'] ?? '');

// Clear all session variables
$_SESSION = [];


if(ini_get("session.use_cookies")){
    $params = session_get_cookie_params();

    //delete the session's cookie
    setcookie(
        session_name(),
        "",
        time() - 42000,
         $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
    
}

//DESTROY the session
session_destroy();

//redirect to login page
header("Location: login.php");
exit;