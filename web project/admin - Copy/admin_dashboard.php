<?php
session_start();
require_once '../db.php'; 

$page_title = "Admin Dashboard";
include_once 'admin_includes/admin_header.php';

$pending_reviews_count = 0;
$total_movies_count = 0;
//count el reviews pending
$pending_stmt = $conn->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
if ($pending_stmt) {
    $pending_reviews_count = $pending_stmt->fetch_row()[0];
    $pending_stmt->close();
} else { error_log("Dashboard: Error fetching pending reviews count: " . $conn->error); }
//count el movies
$movies_stmt = $conn->query("SELECT COUNT(*) FROM movies");
if ($movies_stmt) {
    $total_movies_count = $movies_stmt->fetch_row()[0];
    $movies_stmt->close();
} else { error_log("Dashboard: Error fetching total movies count: " . $conn->error); }
?>

<div class="dashboard-stats">
    <div class="stat-card card" data-aos="fade-up"> 
        <h3>Pending Reviews</h3>
        <p><?php echo $pending_reviews_count; ?></p>
        <a href="admin_reviews.php" class="btn moderate-link">Moderate Now</a>
    </div>
    <div class="stat-card card" data-aos="fade-up" data-aos-delay="100"> 
        <h3>Total Movies</h3>
        <p><?php echo $total_movies_count; ?></p>
        <a href="admin_movies.php" class="btn moderate-link">Manage Movies</a>
    </div>
</div>

<div class="quick-actions card" data-aos="fade-up" data-aos-delay="200">
    <h2>Quick Actions</h2>
    <a href="admin_reviews.php" class="action-button reviews">Moderate Reviews</a>
    <a href="admin_movies.php" class="action-button movies">Manage Movies</a>
    <a href="admin_movie_form.php" class="action-button btn-primary" style="background-color:var(--button-primary-bg)">Add New Movie</a>
</div>

<?php include_once 'admin_includes/admin_footer.php'; ?>