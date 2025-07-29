<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You need to be logged in.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])) {
    $user_id = $_SESSION['user_id'];
    $movie_id = intval($_POST['movie_id']);

    if ($movie_id > 0) {
        $stmt = $conn->prepare("DELETE FROM watchlists WHERE user_id = ? AND movie_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $movie_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success_message'] = "Movie removed from your watchlist.";
                } else {
                    $_SESSION['info_message'] = "Movie was not found in your watchlist or already removed.";
                }
            } else {
                $_SESSION['error_message'] = "Error removing movie from watchlist: " . $stmt->error;
                error_log("Remove from watchlist execute error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
            error_log("Remove from watchlist prepare error: " . $conn->error);
        }
    } else {
        $_SESSION['error_message'] = "Invalid movie ID.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
?>