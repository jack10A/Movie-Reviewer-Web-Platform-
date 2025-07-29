<?php
session_start();

$was_admin = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
$user_id_to_clear_tokens = $_SESSION['user_id'] ?? null;

// Clear "Remember Me" cookies
if (isset($_COOKIE['remember_selector'])) {
    setcookie('remember_selector', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}
if (isset($_COOKIE['remember_validator'])) {
    setcookie('remember_validator', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}


if ($user_id_to_clear_tokens) {
    include 'db.php'; 
    if ($conn) { 
        $stmt_delete_tokens = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        if ($stmt_delete_tokens) {
            $stmt_delete_tokens->bind_param("i", $user_id_to_clear_tokens);
            $stmt_delete_tokens->execute();
            $stmt_delete_tokens->close();
        } else {
            error_log("Logout: Failed to prepare delete remember_tokens statement: " . $conn->error);
        }
    }
}

session_destroy();

if ($was_admin) {
    header("Location: login.php?from_admin=1");
} else {
    header("Location: login.php");
}
exit;
?>