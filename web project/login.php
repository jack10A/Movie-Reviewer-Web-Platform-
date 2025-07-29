<?php
session_start();
include 'db.php'; 

$error = '';
$email_val = $_POST['email'] ?? '';
$is_admin_attempt_context = isset($_GET['from_admin']) && $_GET['from_admin'] == '1';


function checkRememberMe() {
    global $conn; 

    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_selector']) && isset($_COOKIE['remember_validator'])) {
        $selector = $_COOKIE['remember_selector'];
        $validator_from_cookie = $_COOKIE['remember_validator'];

        $stmt = $conn->prepare("SELECT id, user_id, hashed_validator, expires_at FROM remember_tokens WHERE selector = ? AND expires_at >= NOW()");
        if ($stmt) {
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $token_data = $result->fetch_assoc();
                
                if (password_verify($validator_from_cookie, $token_data['hashed_validator'])) {
                    
                    $user_stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
                    if ($user_stmt) {
                        $user_stmt->bind_param("i", $token_data['user_id']);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        if ($user_result->num_rows === 1) {
                            $user = $user_result->fetch_assoc();

                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];

                            if ($user['role'] === 'admin') {
                                $_SESSION['admin_logged_in'] = true;
                                $_SESSION['admin_id'] = $user['id'];
                                $_SESSION['admin_name'] = $user['name'];
                                $_SESSION['admin_email'] = $user['email'];
                            } else {
                                $_SESSION['admin_logged_in'] = false;
                            }

                          

                           
                            $redirect_target = $_SESSION['redirect_after_login'] ?? ($_SESSION['redirect_url'] ?? ($user['role'] === 'admin' ? 'admin/admin_dashboard.php' : 'index.php'));
                            unset($_SESSION['redirect_after_login'], $_SESSION['redirect_url']);
                            header("Location: " . $redirect_target);
                            exit;
                        }
                        $user_stmt->close();
                    }
                }
            }
            
            setcookie('remember_selector', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            setcookie('remember_validator', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            if ($stmt) $stmt->close(); 
        } else {
            error_log("Remember me check - DB prepare error: " . $conn->error);
        }
    }
}

if (!isset($_SESSION['user_id'])) { 
    checkRememberMe();
}



if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? 'user') === 'admin' && ($_SESSION['admin_logged_in'] ?? false) === true) {
        $redirect_target = $_SESSION['redirect_after_login'] ?? 'admin/admin_dashboard.php';
        unset($_SESSION['redirect_after_login']);
        header("Location: " . $redirect_target);
        exit;
    } elseif (($_SESSION['user_role'] ?? 'user') === 'user') {
        $redirect_target = $_SESSION['redirect_url'] ?? 'index.php';
        unset($_SESSION['redirect_url']);
        header("Location: " . $redirect_target);
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_attempt = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']); 

    if (empty($email_val) || empty($password_attempt)) {
        $error = "Email and Password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        if (!$stmt) {
            $error = "Database error (prepare). Please try again later.";
            error_log("Login DB prepare error: " . $conn->error);
        } else {
            $stmt->bind_param("s", $email_val);
            if (!$stmt->execute()) {
                $error = "Database error (execute). Please try again later.";
                error_log("Login DB execute error: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    
                    if ($password_attempt === $user['password']) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];

                        
                        if ($remember_me) {
                            
                            $delete_old_stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                            if ($delete_old_stmt) {
                                $delete_old_stmt->bind_param("i", $user['id']);
                                $delete_old_stmt->execute();
                                $delete_old_stmt->close();
                            } else {
                                error_log("Remember me: Failed to prepare delete old tokens statement: " . $conn->error);
                            }


                            $selector = bin2hex(random_bytes(16)); 
                            $validator_plain = bin2hex(random_bytes(32)); 
                            $validator_hashed = password_hash($validator_plain, PASSWORD_DEFAULT); 
                            $expires_seconds = 86400 * 30; // 30 days
                            $expires_at_db = date('Y-m-d H:i:s', time() + $expires_seconds);

                            $insert_token_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?)");
                            if ($insert_token_stmt) {
                                $insert_token_stmt->bind_param("isss", $user['id'], $selector, $validator_hashed, $expires_at_db);
                                if ($insert_token_stmt->execute()) {
                                    
                                    $cookie_expiry = time() + $expires_seconds;
                                    $secure_cookie = isset($_SERVER['HTTPS']); 
                                    
                                    setcookie('remember_selector', $selector, $cookie_expiry, '/', '', $secure_cookie, true);
                                    setcookie('remember_validator', $validator_plain, $cookie_expiry, '/', '', $secure_cookie, true); 
                                } else {
                                    error_log("Remember me: Failed to insert token: " . $insert_token_stmt->error);
                                }
                                $insert_token_stmt->close();
                            } else {
                                 error_log("Remember me: Failed to prepare insert token statement: " . $conn->error);
                            }
                        }
                        


                        if ($user['role'] === 'admin') {
                            $_SESSION['admin_logged_in'] = true;
                            $_SESSION['admin_id'] = $user['id'];
                            $_SESSION['admin_name'] = $user['name'];
                            $_SESSION['admin_email'] = $user['email'];
                            $redirect_target = $_SESSION['redirect_after_login'] ?? 'admin/admin_dashboard.php';
                            unset($_SESSION['redirect_after_login']);
                            header("Location: " . $redirect_target);
                            exit;
                        } else {
                            $_SESSION['admin_logged_in'] = false;
                            $redirect_target = $_SESSION['redirect_url'] ?? 'index.php';
                            unset($_SESSION['redirect_url']);
                             if (isset($_GET['redirect'])) {
                                $redirect_target = $_GET['redirect'];
                            }
                            header("Location: " . $redirect_target);
                            exit;
                        }
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            }
            if ($stmt) $stmt->close(); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_admin_attempt_context ? 'Admin' : 'User'; ?> Login - Movie Review Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        .remember-me { margin-bottom: 15px; text-align: left; font-size: 0.9em; }
        .remember-me input { margin-right: 5px; vertical-align: middle; }
        .remember-me label { vertical-align: middle; color: var(--text-color); opacity: 0.9;}
    </style>
</head>
<body>

    <div class="theme-container">
      <label class="theme-switch">
        <input type="checkbox" id="theme-toggle">
        <span class="slider"></span>
      </label>
    </div>

    <div class="login-container" data-aos="fade-in">
        <h2><?php echo $is_admin_attempt_context ? ' Login' : 'Login'; ?></h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php<?php
            $query_params = [];
            if (isset($_GET['redirect'])) $query_params['redirect'] = urlencode($_GET['redirect']);
            if ($is_admin_attempt_context) $query_params['from_admin'] = '1';
            if (!empty($query_params)) echo '?' . http_build_query($query_params);
        ?>">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email_val); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <!-- MODIFIED: Added Remember Me Checkbox -->
            <div class="form-group remember-me">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Remember Me</label>
            </div>
            <!-- END MODIFIED -->
            <button type="submit">Login</button>
        </form>

        <?php if (!$is_admin_attempt_context): ?>
        <div class="links">
            <a href="register.php">Don't have an account? Register</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="mainUser.js"></script>
</body>
</html>