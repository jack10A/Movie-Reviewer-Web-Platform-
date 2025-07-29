<?php
session_start();
include 'db.php'; 
include_once 'includes/navigation_links.php'; 

$movie = null;
$reviews_result = null;
$movie_id = 0;
$page_error = null;
$is_in_watchlist = false; 


$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
$info_message = $_SESSION['info_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['info_message']);


if (!isset($_GET['id'])) {
    $page_error = "Movie ID is required.";
} else {
    $movie_id = intval($_GET['id']);
    if ($movie_id <= 0) {
        $page_error = "Invalid Movie ID provided.";
    } else {
        // Fetch movie details
        $movie_sql = "SELECT m.*, g.name AS genre FROM movies m LEFT JOIN genres g ON m.genre_id = g.id WHERE m.id = ?";
        $stmt_movie = $conn->prepare($movie_sql);
        // ... error handling with $stmt_movie
        if (!$stmt_movie) {
            $page_error = "Error preparing movie details query.";
            error_log("Movie page - Movie prepare failed: " . $conn->error);
        } else {
            $stmt_movie->bind_param("i", $movie_id);
            if (!$stmt_movie->execute()) {
                $page_error = "Error fetching movie details.";
                error_log("Movie page - Movie execute failed: " . $stmt_movie->error);
            } else {
                $movie_db_result = $stmt_movie->get_result();
                if ($movie_db_result->num_rows > 0) {
                    $movie = $movie_db_result->fetch_assoc();

                    // MODIFIED: Check if movie is in current user's watchlist
                    if (isset($_SESSION['user_id'])) {
                        $user_id_session = $_SESSION['user_id'];
                        $check_wl_stmt = $conn->prepare("SELECT id FROM watchlists WHERE user_id = ? AND movie_id = ?");
                        if ($check_wl_stmt) {
                            $check_wl_stmt->bind_param("ii", $user_id_session, $movie_id);
                            $check_wl_stmt->execute();
                            if ($check_wl_stmt->get_result()->num_rows > 0) {
                                $is_in_watchlist = true;
                            }
                            $check_wl_stmt->close();
                        } else {
                            error_log("Movie page - Watchlist check prepare error: " . $conn->error);
                        }
                    }
                    
                } else {
                    $page_error = "Movie not found.";
                }
            }
            $stmt_movie->close();
        }


        // Fetch reviews if movie was found
        if ($movie) {
            $review_sql = "SELECT r.*, u.name AS user_name
                           FROM reviews r
                           JOIN users u ON r.user_id = u.id
                           WHERE r.movie_id = ? AND r.status = 'approved'
                           ORDER BY r.created_at DESC";
            $stmt_reviews = $conn->prepare($review_sql);
            // ... error handling with $stmt_reviews
            if (!$stmt_reviews) {
                if(!$page_error) $page_error = "Error preparing reviews query.";
                error_log("Movie page - Reviews prepare failed: " . $conn->error);
            } else {
                $stmt_reviews->bind_param("i", $movie_id);
                if (!$stmt_reviews->execute()) {
                    if(!$page_error) $page_error = "Error fetching reviews.";
                    error_log("Movie page - Reviews execute failed: " . $stmt_reviews->error);
                } else {
                    $reviews_result = $stmt_reviews->get_result();
                }
                $stmt_reviews->close();
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
    <title><?php echo ($movie && isset($movie['title'])) ? htmlspecialchars($movie['title']) : 'Movie Details'; ?> - Movie Review Platform</title>
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

    <div class="movie-detail-page-container" data-aos="fade-in">
        <div class="page-navigation">
            <a href="index.php">🏠 Home</a>
            <a href="<?php echo get_back_link('index.php'); ?>">← Back</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                | <a href="my_watchlist.php">My Watchlist</a> 
            <?php endif; ?>
            
        </div>

         <!-- Display Session Messages -->
        <?php if ($success_message): ?><div class="alert alert-success" data-aos="fade-down"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger" data-aos="fade-down"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
        <?php if ($info_message): ?><div class="alert" style="background-color: var(--input-bg-color); border:1px solid var(--border-color); color:var(--text-color);" data-aos="fade-down"><?php echo htmlspecialchars($info_message); ?></div><?php endif; ?>
        <!-- End Display Session Messages -->


        <?php if ($page_error && !$movie): ?>
            <div class="movie-detail card error-message">
                <h1>Error</h1>
                <p><?php echo htmlspecialchars($page_error); ?></p>
                <p><a href="index.php" class="btn">Return to Home Page</a></p>
            </div>
        <?php elseif ($movie): ?>
            <div class="movie-detail card">
                <h1 data-aos="fade-right"><?php echo htmlspecialchars($movie['title']); ?></h1>
                <p data-aos="fade-right" data-aos-delay="100"><strong>Genre:</strong> <?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?></p>
                <p data-aos="fade-right" data-aos-delay="200"><strong>Release Year:</strong> <?php echo htmlspecialchars($movie['release_year'] ?? 'N/A'); ?></p>
                
                <?php if (!empty($movie['image_url'])): ?>
                    <img src="assets/images/<?php echo htmlspecialchars($movie['image_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" data-aos="zoom-in" data-aos-delay="300">
                <?php else: ?>
                    <div class="no-image" data-aos="zoom-in" data-aos-delay="300">
                        No Image Available
                    </div>
                <?php endif; ?>
                
                <p data-aos="fade-up" data-aos-delay="400"><?php echo nl2br(htmlspecialchars($movie['description'] ?? 'No description available.')); ?></p>

                <div class="action-buttons-movie-detail" style="margin-top: 15px; margin-bottom: 15px;" data-aos="fade-up" data-aos-delay="500">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="review.php?movie_id=<?php echo $movie_id; ?>" class="btn button-like" style="background-color: var(--button-primary-bg); border-color: var(--button-primary-bg); margin-right:10px;">Write a Review</a>
                        
                        <!-- MODIFIED: Add/Remove from Watchlist Button -->
                        <?php if ($is_in_watchlist): ?>
                            <form action="remove_from_watchlist.php" method="POST" style="display: inline-block;">
                                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                                <button type="submit" class="btn btn-delete btn-watchlist" title="Remove from Watchlist">
                                    <span class="watchlist-icon">➖</span> Remove from Watchlist
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="add_to_watchlist.php" method="POST" style="display: inline-block;">
                                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                                <button type="submit" class="btn btn-secondary btn-watchlist" title="Add to Watchlist">
                                    <span class="watchlist-icon">➕</span> Add to Watchlist
                                </button>
                            </form>
                        <?php endif; ?>
                        <!-- END MODIFIED -->

                    <?php else: ?>
                        <p><a href="login.php?redirect=<?php echo urlencode('movie.php?id='.$movie_id); ?>" class="btn button-like" style="background-color: var(--button-primary-bg); border-color: var(--button-primary-bg);">Login to Write a Review or Add to Watchlist</a></p>
                    <?php endif; ?>
                </div>


                <div class="reviews" data-aos="fade-up" data-aos-delay="600">
                    <h2>Reviews</h2>
                    <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
                        <?php while ($review_item = $reviews_result->fetch_assoc()): ?>
                            <div class="review" data-aos="fade-up" data-aos-delay="100">
                                <strong><?php echo htmlspecialchars($review_item['user_name']); ?></strong> –
                                Rating: <?php echo htmlspecialchars($review_item['rating']); ?>/5 ⭐<br>
                                <em><?php echo nl2br(htmlspecialchars($review_item['comment'])); ?></em><br>
                                <small>Posted on <?php echo date('M j, Y, g:i a', strtotime($review_item['created_at'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No approved reviews yet for this movie.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
             <div class="movie-detail card error-message">
                <h1>Movie Not Found</h1>
                <p>The movie you are looking for could not be found.</p>
                <p><a href="index.php" class="btn">Return to Home Page</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="mainUser.js"></script>
</body>
</html>