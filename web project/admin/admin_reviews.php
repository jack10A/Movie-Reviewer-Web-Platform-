<?php
session_start();
require_once '../db.php'; // Path to db.php

// --- ACTION HANDLING (MUST BE BEFORE ANY HTML OUTPUT) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve' || $action === 'reject') {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $review_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Review (ID: $review_id) successfully marked as " . htmlspecialchars($new_status) . "!";
            } else {
                $_SESSION['error'] = "Error updating review (ID: $review_id) status: " . $stmt->error;
                error_log("Admin Reviews - DB Execute Error: " . $stmt->error . " for review ID: " . $review_id);
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Error preparing statement to update review: " . $conn->error;
            error_log("Admin Reviews - DB Prepare Error: " . $conn->error);
        }
    } else {
        $_SESSION['error'] = "Invalid action specified.";
    }
    header("Location: admin_reviews.php");
    exit;
}


$page_title = "Moderate Reviews";
include_once 'admin_includes/admin_header.php'; 

$message = $_SESSION['message'] ?? null;
$error_msg_page = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

$reviews_data = [];
//btrg3  list of movie reviews
$reviews_result = $conn->query("
    SELECT r.id, r.rating, r.comment, r.created_at, r.status,
           u.email as user_email, u.name as user_name,
           m.title as movie_title
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN movies m ON r.movie_id = m.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
");

if (!$reviews_result) {
    if(empty($error_msg_page)) $error_msg_page = "Error fetching pending reviews: " . $conn->error;
    error_log("Admin Reviews - Fetching list error: " . $conn->error);
} else {
    $reviews_data = $reviews_result->fetch_all(MYSQLI_ASSOC);
}
?>

<?php if ($message): ?>
    <div class="alert alert-success" data-aos="fade-in"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error_msg_page): ?>
    <div class="alert alert-danger" data-aos="fade-in"><?php echo htmlspecialchars($error_msg_page); ?></div>
<?php endif; ?>

<div class="card" data-aos="fade-up">
    <div class="manage-reviews-header"> 
        <h3>Pending Reviews (<?php echo count($reviews_data); ?>)</h3>
    </div>

    <?php if (!empty($reviews_data)): ?>
        <div class="table-responsive-wrapper"> 
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Movie Title</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews_data as $review_item): ?>
                        <tr>
                            <td><?php echo $review_item['id']; ?></td>
                            <td><?php echo htmlspecialchars($review_item['movie_title']); ?></td>
                            <td><?php echo htmlspecialchars($review_item['user_name'] ?? $review_item['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($review_item['rating']); ?>/5 ⭐</td>
                            <td>
                                <details>
                                    <summary><?php echo nl2br(htmlspecialchars(substr($review_item['comment'], 0, 70))) . (strlen($review_item['comment']) > 70 ? '...' : ''); ?></summary>
                                    <p><?php echo nl2br(htmlspecialchars($review_item['comment'])); ?></p>
                                </details>
                            </td>
                            <td><?php echo date('M j, Y, g:i a', strtotime($review_item['created_at'])); ?></td>
                            <td class="action-links">
                                <a href="admin_reviews.php?action=approve&id=<?php echo $review_item['id']; ?>" class="btn btn-approve">Approve</a>
                                <a href="admin_reviews.php?action=reject&id=<?php echo $review_item['id']; ?>" class="btn btn-reject">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No pending reviews to moderate at the moment.</p>
    <?php endif; ?>
</div>

<?php include_once 'admin_includes/admin_footer.php'; ?>