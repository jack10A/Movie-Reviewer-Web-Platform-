<?php

if (
    !isset($_SESSION['user_id']) || 
    !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin' || 
    !isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true 
) {
    
    $request_uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    $_SESSION['redirect_after_login'] = $request_uri; 

    header("Location: ../login.php?from_admin=1"); 
    exit;
}


if (!isset($_SESSION['admin_name']) && isset($_SESSION['user_name'])) {
    $_SESSION['admin_name'] = $_SESSION['user_name'];
}
if (!isset($_SESSION['admin_email']) && isset($_SESSION['user_email'])) {
    $_SESSION['admin_email'] = $_SESSION['user_email'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin Panel'; ?> - Movie Reviews</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body> 

    <div class="theme-container">
      <label class="theme-switch">
        <input type="checkbox" id="theme-toggle">
        <span class="slider"></span>
      </label>
    </div>

<div class="admin-wrapper">
    <aside class="admin-sidebar" data-aos="fade-right">
        <h3>Admin Panel</h3>
        <ul>
            <li><a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="admin_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reviews.php' ? 'active' : ''; ?>">Moderate Reviews</a></li>
            <li><a href="admin_movies.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_movies.php' ? 'active' : ''; ?>">Manage Movies</a></li>
            <li><a href="admin_movie_form.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_movie_form.php' && !isset($_GET['id']) ? 'active' : ''; ?>">Add New Movie</a></li>
        </ul>
        <div class="logout-section">
            Logged in as: <br><strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_email'] ?? 'Admin'); ?></strong>
            <br><a href="../logout.php">Logout</a> 
        </div>
    </aside>
    <main class="admin-main-content">
        <header class="admin-header" data-aos="fade-down">
            <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin Section'; ?></h1>
        </header>
        