<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_host = 'localhost';
$db_name = 'moviereviewer';
$db_user = 'root';
$db_pass = '';
$db_port = 3308; 


$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);


if ($conn->connect_error) {
    
    error_log("Database Connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later or contact support. Error: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");


// --- START:el REMEMBER ME CHECK LOGIC (cookies) ---
if (!function_exists('checkRememberMeLogin')) {
    function checkRememberMeLogin($db_conn) { // Pass $conn as a parameter
        
        if (isset($_SESSION['user_id'])) {
            return; // No need to check cookies if already logged in
        }

        if (isset($_COOKIE['remember_selector']) && isset($_COOKIE['remember_validator'])) {
            $selector = $_COOKIE['remember_selector'];
            $validator_from_cookie = $_COOKIE['remember_validator'];

            // sql el cookies
           
            $stmt = $db_conn->prepare(
                "SELECT rt.id AS token_id, rt.user_id, rt.hashed_validator, rt.expires_at, 
                        u.name AS user_name, u.email AS user_email, u.role AS user_role 
                 FROM remember_tokens rt 
                 JOIN users u ON rt.user_id = u.id 
                 WHERE rt.selector = ? AND rt.expires_at >= NOW()"
            );

            if ($stmt) {
                $stmt->bind_param("s", $selector);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $token_data = $result->fetch_assoc();

                   
                    if (password_verify($validator_from_cookie, $token_data['hashed_validator'])) {
                    
                        session_regenerate_id(true); 

                        $_SESSION['user_id'] = $token_data['user_id'];
                        $_SESSION['user_name'] = $token_data['user_name'];
                        $_SESSION['user_email'] = $token_data['user_email'];
                        $_SESSION['user_role'] = $token_data['user_role'];

                        if ($token_data['user_role'] === 'admin') {
                            $_SESSION['admin_logged_in'] = true;
                            $_SESSION['admin_id'] = $token_data['user_id']; 
                            $_SESSION['admin_name'] = $token_data['user_name'];
                            $_SESSION['admin_email'] = $token_data['user_email'];
                        } else {
                            $_SESSION['admin_logged_in'] = false;
                        }

                       

                    } else {
                        // Invalid validator, clear cookies and delete the token from DB
                        error_log("Remember Me: Invalid validator for selector: " . $selector);
                        setcookie('remember_selector', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                        setcookie('remember_validator', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                        
                        $delete_invalid_token_stmt = $db_conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                        if ($delete_invalid_token_stmt) {
                           $delete_invalid_token_stmt->bind_param("s", $selector);
                           $delete_invalid_token_stmt->execute();
                           $delete_invalid_token_stmt->close();
                        }
                    }
                } else {
                    // Selector not found or expired, clear cookies
                    setcookie('remember_selector', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                    setcookie('remember_validator', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                }
                $stmt->close();
            } else {
                error_log("Remember Me check (in db.php) - DB prepare error: " . $db_conn->error);
            }
        }
    }
}


if ($conn && property_exists($conn, 'connect_errno') && $conn->connect_errno === 0) { 
    if (!isset($_SESSION['user_id'])) { 
         checkRememberMeLogin($conn);
    }
}


?>