<?php
session_start();
include 'db.php';

$error = '';
$name_val = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_val = $_POST['name'] ?? '';
    $email_val = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? ''; 

    if (empty($name_val) || empty($email_val) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email_val);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $error = "Email already registered.";
            }
            $stmt_check->close();
        } else {
            $error = "Database error. Please try again.";
            error_log("Register page - Email check prepare error: " . $conn->error);
        }

        if (empty($error)) {
           
            $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($stmt_insert) {
                
                $stmt_insert->bind_param("sss", $name_val, $email_val, $password);
                if ($stmt_insert->execute()) {
                    $_SESSION['user_id'] = $stmt_insert->insert_id;
                    $_SESSION['user_name'] = $name_val;
                    $_SESSION['user_role'] = 'user';
                    session_regenerate_id(true);
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                    error_log("Register page - User insert execute error: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            } else {
                $error = "Database error during registration. Please try again.";
                error_log("Register page - User insert prepare error: " . $conn->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Movie Review Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body>
    <div class="theme-container">
      <label class="theme-switch">
        <input type="checkbox" id="theme-toggle">
        <span class="slider"></span>
      </label>
    </div>
    <div class="login-container" data-aos="fade-in">
        <h2>Register New Account</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($name_val); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email_val); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="links">
            <a href="login.php">Already have an account? Login</a>
        </div>
        
    </div>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="mainUser.js"></script>
</body>
</html>