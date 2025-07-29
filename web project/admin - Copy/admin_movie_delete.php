<?php
session_start();
require_once '../db.php'; 

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin' ||
    !isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true
) {
    $_SESSION['error'] = "You must be logged in as an admin to perform this action.";
    header("Location: ../login.php?from_admin=1");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Movie ID not specified for deletion.";
    header("Location: admin_movies.php");
    exit;
}

$movie_id = intval($_GET['id']);

if ($movie_id <= 0) {
    $_SESSION['error'] = "Invalid Movie ID for deletion.";
    header("Location: admin_movies.php");
    exit;
}

// Prepare and execute the DELETE statement
$stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $movie_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Movie (ID: " . htmlspecialchars($movie_id) . ") deleted successfully.";
        } else {
            // This case means the ID was valid format, but no row matched (e.g., already deleted or never existed)
            $_SESSION['error'] = "Movie (ID: " . htmlspecialchars($movie_id) . ") could not be deleted. It may not exist or was already removed.";
        }
    } else {
        $_SESSION['error'] = "Error deleting movie: " . $stmt->error;
        error_log("Admin Movie Delete - DB Execute Error: " . $stmt->error . " for movie ID: " . $movie_id);
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Error preparing delete statement: " . $conn->error;
    error_log("Admin Movie Delete - DB Prepare Error: " . $conn->error);
}


header("Location: admin_movies.php");
exit;
?>