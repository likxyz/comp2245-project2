<?php

//generates a CSRF token if one does not exist 
function generateCSRFToken(){
    if(empty($_SESSION["csrf_token"])){
        $_SESSION["csrf_token"]= bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

//Checks if the submitted token is valid 
function verifyCSRFToken($token){
    if(
        !isset($_SESSION["csrf_token"]) || !is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)
        ){
            http_response_code(403);
            exit("Invalid CSRF token");
    }
}