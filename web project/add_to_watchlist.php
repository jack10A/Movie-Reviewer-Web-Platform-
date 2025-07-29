<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Set an error message
    $_SESSION['error_message'] = "You need to be logged in to add movies to your watchlist.";
    // Try to redirect back to the movie page or a sensible default
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: " . $redirect_url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])) {
    $user_id = $_SESSION['user_id'];
    $movie_id = intval($_POST['movie_id']);

    if ($movie_id > 0) {
        // Check if already in watchlist (due to UNIQUE constraint, insert will fail anyway, but good to check)
        $check_stmt = $conn->prepare("SELECT id FROM watchlists WHERE user_id = ? AND movie_id = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("ii", $user_id, $movie_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $_SESSION['info_message'] = "This movie is already in your watchlist.";
            } else {
                // Add to watchlist
                $insert_stmt = $conn->prepare("INSERT INTO watchlists (user_id, movie_id) VALUES (?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("ii", $user_id, $movie_id);
                    if ($insert_stmt->execute()) {
                        $_SESSION['success_message'] = "Movie added to your watchlist!";
                    } else {
                        // This might happen if unique constraint is violated concurrently, or other DB error
                        $_SESSION['error_message'] = "Could not add movie to watchlist. Error: " . $insert_stmt->error;
                        error_log("Add to watchlist execute error: " . $insert_stmt->error);
                    }
                    $insert_stmt->close();
                } else {
                    $_SESSION['error_message'] = "Could not prepare statement to add movie. Error: " . $conn->error;
                    error_log("Add to watchlist prepare error: " . $conn->error);
                }
            }
            $check_stmt->close();
        } else {
             $_SESSION['error_message'] = "Error checking watchlist: " . $conn->error;
             error_log("Watchlist check prepare error: " . $conn->error);
        }
    } else {
        $_SESSION['error_message'] = "Invalid movie ID.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the previous page (or a sensible default)
$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: " . $redirect_url);
exit;
?>