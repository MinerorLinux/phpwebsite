<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $category = htmlspecialchars(trim($_POST['category']));
    $uploadDir = 'uploads/';
    $filePath = '';

    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['file']['size'] <= 10485760) { // 10MB limit
            $fileName = basename($_FILES['file']['name']);
            $filePath = $uploadDir . $fileName;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            move_uploaded_file($_FILES['file']['tmp_name'], $filePath);
        } else {
            $error = 'File size exceeds 10MB limit.';
        }
    }

    // Save post
    $posts = json_decode(file_get_contents('posts.json'), true) ?? [];
    $posts[] = ['title' => $title, 'content' => $content, 'category' => $category, 'file' => $filePath];
    file_put_contents('posts.json', json_encode($posts, JSON_PRETTY_PRINT));
}

// Load posts
$posts = json_decode(file_get_contents('posts.json'), true) ?? [];
$categories = ['General', 'Announcements', 'Feedback', 'Support']; // Define categories
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Forum</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 80%; margin: 0 auto; }
        .post { border-bottom: 1px solid #ccc; padding: 10px 0; }
        .post h2 { margin: 0; }
        .post p { margin: 5px 0; }
        .post a { color: blue; text-decoration: underline; }
        .new-post { margin-top: 20px; }
        .error { color: red; }
        .category { font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Advanced Forum</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php
        // Display posts grouped by category
        foreach ($categories as $category) {
            echo '<div class="category">' . htmlspecialchars($category) . '</div>';
            foreach ($posts as $post) {
                if ($post['category'] === $category) {
                    echo '<div class="post">';
                    echo '<h2>' . htmlspecialchars($post['title']) . '</h2>';
                    echo '<p>' . nl2br(htmlspecialchars($post['content'])) . '</p>';
                    if (!empty($post['file'])) {
                        echo '<p><a href="' . htmlspecialchars($post['file']) . '" target="_blank">Download Attachment</a></p>';
                    }
                    echo '</div>';
                }
            }
        }
        ?>

        <div class="new-post">
            <h2>Create a new post</h2>
            <form action="forum.php" method="post" enctype="multipart/form-data">
                <label for="title">Title:</label><br>
                <input type="text" id="title" name="title" required><br><br>
                <label for="content">Content:</label><br>
                <textarea id="content" name="content" rows="4" required></textarea><br><br>
                <label for="category">Category:</label><br>
                <select id="category" name="category" required>
                    <option value="" disabled selected>Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select><br><br>
                <label for="file">Upload File (max 10MB):</label><br>
                <input type="file" id="file" name="file"><br><br>
                <input type="submit" value="Post">
            </form>
        </div>
    </div>
</body>
</html>