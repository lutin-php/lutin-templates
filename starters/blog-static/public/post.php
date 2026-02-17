<?php
/**
 * Blog Static - Single Post View
 */

declare(strict_types=1);

$slug = $_GET['slug'] ?? '';

// Validate slug (prevent directory traversal)
if (!preg_match('/^[\w\-]+$/', $slug)) {
    http_response_code(400);
    die('Invalid post slug');
}

$postFile = __DIR__ . '/../data/posts/' . $slug . '.html';

if (!file_exists($postFile)) {
    http_response_code(404);
    die('Post not found');
}

// Read post content
$postContent = file_get_contents($postFile);
if ($postContent === false) {
    http_response_code(500);
    die('Failed to read post');
}

// Extract title from first <h1> tag for the page title
$pageTitle = 'Untitled';
if (preg_match('/<h1[^>]*>(.+?)<\/h1>/i', $postContent, $matches)) {
    $pageTitle = strip_tags(trim($matches[1]));
}

// Extract date from slug (YYYY-MM-DD-format)
$date = '';
if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $slug, $matches)) {
    $date = date('F j, Y', strtotime($matches[1]));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - My Static Blog</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>📝 My Static Blog</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <article class="post-content">
            <?php if ($date): ?>
                <time datetime="<?php echo htmlspecialchars($slug); ?>">
                    <?php echo htmlspecialchars($date); ?>
                </time>
            <?php endif; ?>
            
            <?php 
            // Include the HTML content directly
            echo $postContent; 
            ?>
            
            <a href="index.php" class="back-link">← Back to all posts</a>
        </article>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> My Static Blog. Built with Lutin.php</p>
        </div>
    </footer>
</body>
</html>
