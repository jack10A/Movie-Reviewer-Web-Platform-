<?php
session_start();
require_once '../db.php'; 

$page_title = "Manage Movies";
include_once 'admin_includes/admin_header.php';

$message = $_SESSION['message'] ?? null;
$error_msg_page = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);
//sql shows all movies
$movies_data = [];
$sql = "SELECT m.id, m.title, m.release_year, m.image_url, g.name AS genre_name
        FROM movies m
        LEFT JOIN genres g ON m.genre_id = g.id
        ORDER BY m.title ASC";
$result = $conn->query($sql);

if ($result) {
    $movies_data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    $error_msg_page = "Error fetching movies: " . $conn->error;
    error_log("Admin Movies - Error fetching movies list: " . $conn->error);
}
?>

<?php if ($message): ?>
    <div class="alert alert-success" data-aos="fade-in"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error_msg_page): ?>
    <div class="alert alert-danger" data-aos="fade-in"><?php echo htmlspecialchars($error_msg_page); ?></div>
<?php endif; ?>

<div class="card" data-aos="fade-up">
    <div class="manage-movies-header"> 
        <h3>All Movies (<?php echo count($movies_data); ?>)</h3>
        <a href="admin_movie_form.php" class="btn btn-primary">Add New Movie</a>
    </div>

    <?php if (!empty($movies_data)): ?>
        <div class="table-responsive-wrapper"> 
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th class="movie-image-thumbnail">Image</th> 
                        <th>Title</th>
                        <th>Year</th>
                        <th>Genre</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies_data as $movie_item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($movie_item['id']); ?></td>
                            <td class="movie-image-thumbnail"> 
                                <?php if (!empty($movie_item['image_url'])): ?>
                                    <img src="../assets/images/<?php echo htmlspecialchars($movie_item['image_url']); ?>" alt="<?php echo htmlspecialchars($movie_item['title']); ?>">
                                <?php else: ?>
                                    <span class="no-pic-placeholder">No Pic</span> 
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($movie_item['title']); ?></td>
                            <td><?php echo htmlspecialchars($movie_item['release_year']); ?></td>
                            <td><?php echo htmlspecialchars($movie_item['genre_name'] ?? 'N/A'); ?></td>
                            <td class="action-links">
                                <a href="admin_movie_form.php?id=<?php echo $movie_item['id']; ?>" class="btn btn-edit">Edit</a>
                                <a href="admin_movie_delete.php?id=<?php echo $movie_item['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete movie: \'<?php echo htmlspecialchars(addslashes($movie_item['title'])); ?>\'? This cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (empty($error_msg_page)): ?>
        <p>No movies found. <a href="admin_movie_form.php">Add the first one!</a></p>
    <?php endif; ?>
</div>

<?php include_once 'admin_includes/admin_footer.php'; ?>