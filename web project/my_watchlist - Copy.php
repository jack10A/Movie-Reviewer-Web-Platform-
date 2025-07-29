<?php
session_start();
require_once 'db.php';
include_once 'includes/navigation_links.php'; 


if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'my_watchlist.php'; 
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$watchlist_movies = [];
$page_error = null;
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
$info_message = $_SESSION['info_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['info_message']);


$sql = "SELECT m.id, m.title, m.release_year, m.image_url, g.name AS genre_name, w.added_at
        FROM watchlists w
        JOIN movies m ON w.movie_id = m.id
        LEFT JOIN genres g ON m.genre_id = g.id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $watchlist_movies = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $page_error = "Error fetching your watchlist: " . $stmt->error;
        error_log("My Watchlist - fetch error: " . $stmt->error);
    }
    $stmt->close();
} else {
    $page_error = "Error preparing watchlist query: " . $conn->error;
    error_log("My Watchlist - prepare error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlist - Movie Review Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body>
    <div class="theme-container">
        <label class="theme-switch"><input type="checkbox" id="theme-toggle"><span class="slider"></span></label>
    </div>

    <div class="main-container" data-aos="fade-in">
        <div class="page-navigation">
            <a href="index.php">🏠 Home</a>
            <a href="<?php echo get_back_link('index.php'); ?>">← Back</a>
        </div>

        <h1 class="page-title">My Watchlist</h1>

        <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
        <?php if ($info_message): ?><div class="alert" style="background-color: var(--input-bg-color); border: 1px solid var(--border-color); color: var(--text-color);"><?php echo htmlspecialchars($info_message); ?></div><?php endif; ?>
        <?php if ($page_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($page_error); ?></div><?php endif; ?>


        <?php if (!empty($watchlist_movies)): ?>
            <div class="movie-grid">
                <?php foreach ($watchlist_movies as $idx => $movie): ?>
                    <div class="movie-card" data-aos="fade-up" data-aos-delay="<?php echo ($idx % 4 + 1) * 100; ?>">
                        <a href="movie.php?id=<?php echo htmlspecialchars($movie['id']); ?>" class="image-link">
                            <?php if (!empty($movie['image_url'])): ?>
                                <img src="assets/images/<?php echo htmlspecialchars($movie['image_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </a>
                        <div class="movie-card-content">
                            <a href="movie.php?id=<?php echo htmlspecialchars($movie['id']); ?>" class="image-link">
                                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            </a>
                            <p><strong>Genre:</strong> <?php echo htmlspecialchars($movie['genre_name'] ?? 'N/A'); ?></p>
                            <p><strong>Released:</strong> <?php echo htmlspecialchars($movie['release_year'] ?? 'N/A'); ?></p>
                            <p><small>Added on: <?php echo date('M j, Y', strtotime($movie['added_at'])); ?></small></p>
                            <form action="remove_from_watchlist.php" method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                <button type="submit" class="btn btn-delete" style="background-color:var(--error-bg); color:var(--error-text); border-color:var(--error-border); width:100%;">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (empty($page_error)): // If watchlist is empty ?>
            <div class="card no-results" style="text-align:center;">
                <p>Your watchlist is empty.</p>
                <a href="index.php" class="btn btn-primary">Browse Movies</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="mainUser.js"></script>
</body>
</html>