<?php
session_start();
include 'db.php'; 
// --- GENRE FILTERING & SEARCH LOGIC ---
$search_term = "";
$sql_condition = "";
$params = [];
$types = "";
$selected_genre_id = null;
if (isset($_GET['genre_id']) && is_numeric($_GET['genre_id'])) {
    $selected_genre_id = intval($_GET['genre_id']);
}
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $search_param = "%" . $search_term . "%";
    if (!empty($sql_condition)) { $sql_condition .= " AND "; } else { $sql_condition = " WHERE "; }
    $sql_condition .= "(m.title LIKE ? OR g.name LIKE ? OR m.description LIKE ?)";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}
if ($selected_genre_id) {
    if (!empty($sql_condition)) { $sql_condition .= " AND "; } else { $sql_condition = " WHERE "; }
    $sql_condition .= "m.genre_id = ?";
    $params[] = $selected_genre_id;
    $types .= "i";
}
// --- END GENRE FILTERING & SEARCH LOGIC ---

// --- FETCH GENRES ---
$genres = [];
$genre_result = $conn->query("SELECT id, name FROM genres ORDER BY name ASC");
if ($genre_result) {
    $genres = $genre_result->fetch_all(MYSQLI_ASSOC);
    $genre_result->free();
} else {
    error_log("Index page - Error fetching genres: " . $conn->error);
}
// --- END FETCH GENRES ---

// --- FETCH MOVIES ---
$page_error = null;
$movies_data = [];
$sql = "SELECT
        m.id, m.title, m.image_url, m.release_year, g.name AS genre,
        ROUND(AVG(r.rating), 1) AS avg_rating,
        COUNT(DISTINCT r.id) AS review_count
    FROM movies m
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN reviews r ON r.movie_id = m.id AND r.status = 'approved'
    " . $sql_condition . "
    GROUP BY m.id, m.title, m.image_url, m.release_year, g.name
    ORDER BY m.title ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
     $page_error = "Error preparing movie query: " . $conn->error;
     error_log("Index page - Movie prepare error: " . $conn->error);
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $page_error = "Error executing movie query: " . $stmt->error;
        error_log("Index page - Movie execute error: " . $stmt->error);
    } else {
        $result = $stmt->get_result();
        if ($result) {
            $movies_data = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $page_error = "Error getting results: " . $stmt->error;
            error_log("Index page - Movie get_result error: " . $stmt->error);
        }
    }
    $stmt->close();
}
// --- END FETCH MOVIES ---

// --- SESSION MESSAGES (Add this block) ---
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
$info_message = $_SESSION['info_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['info_message']);
// --- END SESSION MESSAGES ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Review Platform <?php
        if ($selected_genre_id && count($genres) > 0) {
            foreach ($genres as $g) { if ($g['id'] == $selected_genre_id) echo "- " . htmlspecialchars($g['name']); }
        } elseif (!empty($search_term)) { echo "- Search: " . htmlspecialchars($search_term); }
    ?></title>
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

    <div class="top-bar" data-aos="fade-down">
        <div class="search-bar">
            <form action="index.php" method="GET">
                <?php if ($selected_genre_id): ?>
                    <input type="hidden" name="genre_id" value="<?php echo htmlspecialchars($selected_genre_id); ?>">
                <?php endif; ?>
                <input type="search" name="search" placeholder="Search movies..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit">Search</button>
                <?php if (!empty($search_term) || $selected_genre_id): ?>
                    <a href="index.php" class="clear-search-btn">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="user-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span> |
                <a href="my_watchlist.php">My Watchlist</a> | 
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a> |
                <a href="register.php">Register</a>
            <?php endif; ?>
           
        </div>
    </div>

    <div class="main-container">
        <h1 class="page-title" data-aos="fade-up">🎥 Movie Listings</h1>

        <?php if ($success_message): ?><div class="alert alert-success" data-aos="fade-down"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger" data-aos="fade-down"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
        <?php if ($info_message): ?><div class="alert" style="background-color: var(--input-bg-color); border:1px solid var(--border-color); color:var(--text-color);" data-aos="fade-down"><?php echo htmlspecialchars($info_message); ?></div><?php endif; ?>
        

        <?php if ($page_error): ?>
            <p class="no-results error"><?php echo htmlspecialchars($page_error); ?></p>
        <?php endif; ?>

        <?php if (!empty($genres)): ?>
            <div class="filter-section" data-aos="fade-up" data-aos-delay="100">
                <h2>Filter by Genre</h2>
                <div class="genre-filters">
                    <a href="index.php<?php echo !empty($search_term) ? '?search=' . urlencode($search_term) : ''; ?>"
                        class="all <?php echo !$selected_genre_id ? 'active' : ''; ?>">All Genres</a>
                    <?php foreach ($genres as $genre_item): ?>
                        <a href="index.php?genre_id=<?php echo $genre_item['id']; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"
                            class="<?php echo ($selected_genre_id == $genre_item['id']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($genre_item['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($selected_genre_id && count($genres) > 0): ?>
            <?php foreach ($genres as $g): if ($g['id'] == $selected_genre_id): ?>
                    <p class="current-filter-notice" data-aos="fade-up" data-aos-delay="150">
                        Currently viewing movies in "<strong><?php echo htmlspecialchars($g['name']); ?></strong>"
                        <?php if (!empty($search_term)) echo " matching search: \"<strong>" . htmlspecialchars($search_term) . "</strong>\""; ?>.
                    </p>
            <?php break; endif; endforeach; ?>
        <?php elseif (!empty($search_term)): ?>
            <p class="current-filter-notice" data-aos="fade-up" data-aos-delay="150">Showing results for "<strong><?php echo htmlspecialchars($search_term); ?></strong>".</p>
        <?php endif; ?>


        <div class="movie-grid">
            <?php if (!empty($movies_data)): ?>
                <?php foreach ($movies_data as $idx => $row): ?>
                    <div class="movie-card" data-aos="fade-up" data-aos-delay="<?php echo ($idx % 3 + 1) * 100;  ?>">
                        <a href="movie.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="image-link">
                            <?php if (!empty($row['image_url'])): ?>
                                <img src="assets/images/<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </a>
                        <div class="movie-card-content">
                            <a href="movie.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="image-link">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            </a>
                            <p><strong>Genre:</strong> <?php echo htmlspecialchars($row['genre'] ?? 'N/A'); ?></p>
                            <p><strong>Released:</strong> <?php echo htmlspecialchars($row['release_year'] ?? 'N/A'); ?></p>
                            <p><strong>Rating:</strong> <?php echo $row['avg_rating'] !== null ? htmlspecialchars($row['avg_rating']) . ' ⭐' : 'No ratings'; ?></p>
                            <p><strong>Reviews:</strong> <?php echo htmlspecialchars($row['review_count']); ?></p>

                            <!-- MODIFIED: Add to Watchlist Button/Form -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form action="add_to_watchlist.php" method="POST" style="margin-top:10px;">
                                    <input type="hidden" name="movie_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-secondary btn-watchlist" title="Add to Watchlist">
                                        <span class="watchlist-icon">➕</span> Watchlist
                                    </button>
                                </form>
                            <?php else: ?>
                                
                                <a href="login.php?redirect=<?php echo urlencode('movie.php?id='.$row['id']); ?>" class="btn btn-secondary btn-watchlist" title="Login to add to Watchlist">
                                     <span class="watchlist-icon">➕</span> Watchlist
                                </a>
                            <?php endif; ?>
                            <!-- END MODIFIED -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (empty($page_error)): ?>
                    <p class="no-results" data-aos="fade-up">
                        <?php
                        if (!empty($search_term) && $selected_genre_id) {
                            $genre_name_for_message = "Selected Genre"; 
                            foreach ($genres as $g) { if ($g['id'] == $selected_genre_id) $genre_name_for_message = $g['name']; }
                            echo "No movies found in \"" . htmlspecialchars($genre_name_for_message) . "\" matching \"" . htmlspecialchars($search_term) . "\".";
                        } elseif (!empty($search_term)) {
                            echo "No movies found matching \"" . htmlspecialchars($search_term) . "\".";
                        } elseif ($selected_genre_id) {
                            $genre_name_for_message = "Selected Genre"; 
                            foreach ($genres as $g) { if ($g['id'] == $selected_genre_id) $genre_name_for_message = $g['name']; }
                            echo "No movies found in the \"" . htmlspecialchars($genre_name_for_message) . "\" genre.";
                        } else {
                            echo "No movies available at the moment.";
                        }
                        ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="mainUser.js"></script>
</body>
</html>