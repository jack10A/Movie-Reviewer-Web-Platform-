<?php
session_start();
include 'db.php';

include_once 'includes/navigation_links.php';

$movie = null;
$movie_id = 0;
$success_msg = ""; 
$error_msg = "";   
$form_values = ['rating' => '', 'comment' => '']; 

if (!isset($_GET['movie_id'])) {
    $error_msg = "Movie ID is required to submit a review.";
} else {
    $movie_id = intval($_GET['movie_id']);
    if ($movie_id <= 0) {
        $error_msg = "Invalid Movie ID specified.";
    } else {
        $movie_sql = "SELECT title FROM movies WHERE id = ?";
        $stmt_movie_fetch = $conn->prepare($movie_sql); 
        if (!$stmt_movie_fetch) {
            $error_msg = "Could not load movie details (prepare failed).";
            error_log("Review page: Movie fetch prepare failed: " . $conn->error);
        } else {
            $stmt_movie_fetch->bind_param("i", $movie_id);
            if (!$stmt_movie_fetch->execute()) {
                $error_msg = "Could not load movie details (execute failed).";
                error_log("Review page: Movie fetch execute failed: " . $stmt_movie_fetch->error);
            } else {
                $movie_result_fetch = $stmt_movie_fetch->get_result(); 
                if ($movie_result_fetch->num_rows > 0) {
                    $movie = $movie_result_fetch->fetch_assoc();
                } else {
                    $error_msg = "Movie not found. Cannot submit a review.";
                }
            }
            $stmt_movie_fetch->close();
        }
    }
}

if ($movie && !isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = "review.php?movie_id=" . $movie_id;
    header("Location: login.php?redirect=" . urlencode("review.php?movie_id=" . $movie_id));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form_values['rating'] = $_POST['rating'] ?? '';
    $form_values['comment'] = $_POST['comment'] ?? '';

    if (!$movie) {
        $error_msg = "❌ Cannot submit review: Movie details are missing or invalid.";
    } elseif (!isset($_SESSION['user_id'])) {
        $error_msg = "❌ You must be logged in to submit a review.";
    } else {
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $user_id = $_SESSION['user_id'];

        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $insert_sql = "INSERT INTO reviews (user_id, movie_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt_insert = $conn->prepare($insert_sql);
            if (!$stmt_insert) {
                $error_msg = "❌ Failed to prepare review submission.";
                error_log("Review insert prepare failed: " . $conn->error);
            } else {
                $stmt_insert->bind_param("iiss", $user_id, $movie_id, $rating, $comment);
                if ($stmt_insert->execute()) {
                    $success_msg = "✅ Review submitted successfully! It is now pending approval.";
                    $form_values = ['rating' => '', 'comment' => ''];
                } else {
                    $error_msg = "❌ Failed to submit review due to a database error.";
                    error_log("Review insert execute failed: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            }
        } else {
            $error_msg = "❌ Please provide a valid rating (1-5) and a comment.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review<?php echo ($movie && isset($movie['title'])) ? ' for ' . htmlspecialchars($movie['title']) : ''; ?> - Movie Review Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>
<body class="review-form-page"> 

    <div class="theme-container">
      <label class="theme-switch">
        <input type="checkbox" id="theme-toggle">
        <span class="slider"></span>
      </label>
    </div>

    <div data-aos="fade-in">
        <div class="page-navigation">
            <a href="index.php">🏠 Home</a>
            <a href="<?php echo get_back_link($movie_id > 0 ? 'movie.php?id=' . $movie_id : 'index.php'); ?>">
                ← Back <?php echo ($movie_id > 0 ? 'to Movie' : ''); ?>
            </a>
             | 
        </div>

        <div class="user-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> |
                <a href="logout.php">Logout</a>
            <?php elseif($movie): // Only show login/register if movie is valid for review ?>
                <a href="login.php?redirect=<?php echo urlencode('review.php?movie_id='.$movie_id); ?>">Login to Review</a> |
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>

        <div class="card"> 
            <h2>Submit Review for "<?php echo ($movie && isset($movie['title'])) ? htmlspecialchars($movie['title']) : 'Selected Movie'; ?>"</h2>

            <?php if (!empty($success_msg)): ?>
                <div class="message success"><?php echo $success_msg; ?></div>
                <p style="margin-top:15px;"><a href="movie.php?id=<?php echo $movie_id; ?>" class="btn">← View Movie Page</a></p>
            <?php elseif (!empty($error_msg) && !$movie): ?>
                <div class="message error"><?php echo $error_msg; ?></div>
                <p style="margin-top:15px;"><a href="index.php" class="btn">← Go to Home Page</a></p>
            <?php elseif ($movie && isset($_SESSION['user_id'])): ?>
                <div class="form-container">
                    <?php if (!empty($error_msg)): ?>
                        <div class="message error"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="review.php?movie_id=<?php echo $movie_id; ?>">
                        <div class="form-group">
                            <label for="rating">Rating (1 to 5):</label>
                            <input type="number" id="rating" name="rating" class="form-control" min="1" max="5" required
                                   value="<?php echo htmlspecialchars($form_values['rating']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="comment">Review Comment:</label>
                            <textarea id="comment" name="comment" class="form-control" required><?php echo htmlspecialchars($form_values['comment']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                </div>
            <?php elseif (!$movie && empty($error_msg)): // If movie not specified or found, error set ?>
                 <div class="message error">Movie not specified or found.</div>
                 <p style="margin-top:15px;"><a href="index.php" class="btn">← Go to Home Page</a></p>
            <?php elseif (!isset($_SESSION['user_id']) && $movie): ?>
                 <div class="message error">You need to be logged in to submit a review for "<?php echo htmlspecialchars($movie['title']); ?>".</div>
            <?php endif; ?>
        </div> <!-- End card -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="mainUser.js"></script>
</body>
</html>