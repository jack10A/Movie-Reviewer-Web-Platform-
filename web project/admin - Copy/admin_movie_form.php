<?php
session_start();
require_once '../db.php'; 

// --- FORM SUBMISSION HANDLING (MUST BE BEFORE ANY HTML OUTPUT) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id_posted = isset($_POST['movie_id']) ? intval($_POST['movie_id']) : null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $release_year = trim($_POST['release_year'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $genre_id = isset($_POST['genre_id']) ? intval($_POST['genre_id']) : null;
    $posted_form_action = $_POST['form_action'] ?? 'add';

    if (empty($title) || empty($release_year) || empty($genre_id)) {
        $_SESSION['error'] = "Title, Release Year, and Genre are required.";
    } elseif (!is_numeric($release_year) || strlen($release_year) != 4) {
        $_SESSION['error'] = "Release Year must be a 4-digit number.";
    } elseif ($genre_id <=0) {
        $_SESSION['error'] = "Please select a valid genre.";
    } else {
        if ($movie_id_posted && $posted_form_action === 'edit') {

            //sql update el movie 
            $sql = "UPDATE movies SET title = ?, description = ?, release_year = ?, image_url = ?, genre_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssisii", $title, $description, $release_year, $image_url, $genre_id, $movie_id_posted);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Movie (ID: $movie_id_posted) updated successfully!";
                    header("Location: admin_movies.php"); exit;
                } else { $_SESSION['error'] = "Error updating movie: " . $stmt->error; error_log("Admin Movie Form Update: " . $stmt->error); }
                $stmt->close();
            } else { $_SESSION['error'] = "Error preparing update: " . $conn->error; error_log("Admin Movie Form Update Prepare: " . $conn->error); }
        } else { 
            
            // sql el movie ADD
            $sql = "INSERT INTO movies (title, description, release_year, image_url, genre_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssisi", $title, $description, $release_year, $image_url, $genre_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Movie added successfully!";
                    header("Location: admin_movies.php"); exit;
                } else { $_SESSION['error'] = "Error adding movie: " . $stmt->error; error_log("Admin Movie Form Add: " . $stmt->error); }
                $stmt->close();
            } else { $_SESSION['error'] = "Error preparing insert: " . $conn->error; error_log("Admin Movie Form Add Prepare: " . $conn->error); }
        }
    }
    $_SESSION['form_data'] = $_POST;
    $redirect_url = "admin_movie_form.php";
    if ($movie_id_posted && $posted_form_action === 'edit') {
        $redirect_url .= "?id=" . $movie_id_posted;
    }
    header("Location: " . $redirect_url);
    exit;
}



$page_title_action = "Add New Movie";
$movie_data_for_form = [
    'id' => null, 'title' => '', 'description' => '',
    'release_year' => date('Y'), 'image_url' => '', 'genre_id' => '' 
];
$current_form_action_display = 'add';

$genres_list = [];
$error_msg_page = $_SESSION['error'] ?? null;
$success_msg_page = $_SESSION['message'] ?? null; 
$persisted_form_data = $_SESSION['form_data'] ?? null;
unset($_SESSION['error'], $_SESSION['message'], $_SESSION['form_data']);

if ($persisted_form_data) {
    $movie_data_for_form['id'] = $persisted_form_data['movie_id'] ?? null;
    $movie_data_for_form['title'] = $persisted_form_data['title'] ?? '';
    $movie_data_for_form['description'] = $persisted_form_data['description'] ?? '';
    $movie_data_for_form['release_year'] = $persisted_form_data['release_year'] ?? date('Y');
    $movie_data_for_form['image_url'] = $persisted_form_data['image_url'] ?? '';
    $movie_data_for_form['genre_id'] = $persisted_form_data['genre_id'] ?? '';
    $current_form_action_display = $persisted_form_data['form_action'] ?? 'add';
}

$genre_result_fetch = $conn->query("SELECT id, name FROM genres ORDER BY name ASC");
if ($genre_result_fetch) {
    $genres_list = $genre_result_fetch->fetch_all(MYSQLI_ASSOC);
    $genre_result_fetch->free();
} else { if(empty($error_msg_page)) $error_msg_page = "Error fetching genres: " . $conn->error; error_log("Admin Movie Form - Fetching genres: " . $conn->error); }

if (isset($_GET['id']) && !$persisted_form_data) {
    $movie_id_get_param = intval($_GET['id']);
    if ($movie_id_get_param > 0) {
        $current_form_action_display = 'edit';
        $stmt_fetch_edit = $conn->prepare("SELECT * FROM movies WHERE id = ?");
        if ($stmt_fetch_edit) {
            $stmt_fetch_edit->bind_param("i", $movie_id_get_param);
            $stmt_fetch_edit->execute();
            $result_edit = $stmt_fetch_edit->get_result();
            if ($result_edit->num_rows === 1) {
                $movie_data_for_form = $result_edit->fetch_assoc();
            } else {
                if(empty($error_msg_page)) $_SESSION['error'] = "Movie (ID: $movie_id_get_param) not found for editing.";
                header("Location: admin_movies.php"); exit;
            }
            $stmt_fetch_edit->close();
        } else { if(empty($error_msg_page)) $error_msg_page = "Error preparing edit fetch: " . $conn->error; error_log("Admin Movie Form Edit Prepare: " . $conn->error); }
    } else {
        if(empty($error_msg_page)) $_SESSION['error'] = "Invalid movie ID for editing.";
        header("Location: admin_movies.php"); exit;
    }
}

if ($current_form_action_display === 'edit' && isset($movie_data_for_form['id'])) {
    $page_title_action = "Edit Movie (ID: " . htmlspecialchars($movie_data_for_form['id']) . ")";
}

$page_title = $page_title_action;
include_once 'admin_includes/admin_header.php';
?>

<script>document.title = "<?php echo htmlspecialchars($page_title_action); ?> - Admin Panel";</script>

<?php if ($success_msg_page):  ?>
    <div class="alert alert-success" data-aos="fade-in"><?php echo htmlspecialchars($success_msg_page); ?></div>
<?php endif; ?>
<?php if ($error_msg_page): ?>
    <div class="alert alert-danger" data-aos="fade-in"><?php echo htmlspecialchars($error_msg_page); ?></div>
<?php endif; ?>

<div class="card movie-form" data-aos="fade-up"> 
    <h3><?php echo htmlspecialchars($page_title_action); ?></h3>
    <form method="POST" action="admin_movie_form.php<?php echo ($current_form_action_display === 'edit' && !empty($movie_data_for_form['id'])) ? '?id=' . $movie_data_for_form['id'] : ''; ?>">
        <input type="hidden" name="form_action" value="<?php echo $current_form_action_display; ?>">
        <?php if ($current_form_action_display === 'edit' && !empty($movie_data_for_form['id'])): ?>
            <input type="hidden" name="movie_id" value="<?php echo htmlspecialchars($movie_data_for_form['id']); ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($movie_data_for_form['title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" class="form-control" rows="5"><?php echo htmlspecialchars($movie_data_for_form['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="release_year">Release Year (YYYY):</label>
            <input type="number" id="release_year" name="release_year" class="form-control" value="<?php echo htmlspecialchars($movie_data_for_form['release_year']); ?>" required min="1800" max="<?php echo date('Y') + 5; // Allow a few years in future ?>">
        </div>
        <div class="form-group">
            <label for="image_url">Image Filename (e.g., poster.jpg - must be in `assets/images/`):</label>
            <input type="text" id="image_url" name="image_url" class="form-control" placeholder="movie_poster.jpg" value="<?php echo htmlspecialchars($movie_data_for_form['image_url']); ?>">
            <?php if ($current_form_action_display === 'edit' && !empty($movie_data_for_form['image_url'])): ?>
                <div class="current-image-preview" style="margin-top:5px;"> 
                    <small>Current: <?php echo htmlspecialchars($movie_data_for_form['image_url']); ?></small><br>
                    <img src="../assets/images/<?php echo htmlspecialchars($movie_data_for_form['image_url']); ?>" alt="Current image">
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="genre_id">Genre:</label>
            <select id="genre_id" name="genre_id" class="form-control" required>
                <option value="">-- Select Genre --</option>
                <?php if (!empty($genres_list)): ?>
                    <?php foreach ($genres_list as $genre_item): ?>
                        <option value="<?php echo htmlspecialchars($genre_item['id']); ?>" <?php echo ($movie_data_for_form['genre_id'] == $genre_item['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre_item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>No genres available. Please add genres first.</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-actions"> 
            <a href="admin_movies.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><?php echo ($current_form_action_display === 'edit' && !empty($movie_data_for_form['id'])) ? 'Update Movie' : 'Add Movie'; ?></button>
        </div>
    </form>
</div>

<?php include_once 'admin_includes/admin_footer.php'; ?>