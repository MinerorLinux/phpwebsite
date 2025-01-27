<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #121212;
            color: #00ff00;
            overflow: hidden;
        }
        .matrix {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: black;
            overflow: hidden;
            z-index: -1;
        }
        .welcome-container {
            text-align: center;
            padding: 30px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            opacity: 0;
            animation: fadeIn 2s forwards, slideIn 2s forwards;
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        .welcome-container h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #00ff00;
        }
        .welcome-container p {
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #b3b3b3;
        }
        .welcome-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .welcome-container input, .welcome-container textarea, .welcome-container button, .welcome-container select {
            font-size: 1em;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #333;
            border-radius: 5px;
            outline: none;
            background: #1e1e1e;
            color: #00ff00;
            width: 100%;
            max-width: 400px;
        }
        .welcome-container button {
            background: #00ff00;
            color: #121212;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }
        .welcome-container button:hover {
            background: #00cc00;
            transform: scale(1.05);
        }
        .welcome-container button:active {
            transform: scale(0.95);
        }
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
            }
            to {
                transform: translateY(0);
            }
        }
    </style>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https://discord.com https://instagram.com; script-src 'self'; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <canvas class="matrix"></canvas>
    <div class="welcome-container">
        <?php
        session_start();
        $filePath = 'usernames.json';
        $pagesDir = 'pages';
        $uploadsDir = 'uploads';

        // Check if the JSON file exists, if not create it
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([]));
        }

        // Check if the pages directory exists, if not create it
        if (!file_exists($pagesDir)) {
            mkdir($pagesDir, 0777, true);
        }

        // Check if the uploads directory exists, if not create it
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        // Read the existing usernames from the JSON file
        $jsonContent = file_get_contents($filePath);
        $usernames = json_decode($jsonContent, true);

        // Ensure $usernames is an array
        if (!is_array($usernames)) {
            $usernames = [];
        }

        function sanitize_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        function sanitize_html($html) {
            // Basic sanitization to prevent breaking the website
            $allowed_tags = '<p><a><b><i><u><strong><em><br><ul><ol><li><h1><h2><h3><h4><h5><h6><div><span><img><iframe><style><script>';
            return strip_tags($html, $allowed_tags);
        }

        function get_template_content($template) {
            switch ($template) {
                case 'profile':
                    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .profile-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .profile-container img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
        }
        .profile-container h1 {
            margin: 10px 0;
        }
        .profile-container p {
            margin: 10px 0;
        }
        .social-links a {
            margin: 0 10px;
            text-decoration: none;
            color: #333;
        }
        .social-links img {
            width: 30px;
            height: 30px;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <img src="profile-pic.jpg" alt="Profile Picture">
        <h1>Username</h1>
        <p>Bio: This is a short bio about the user.</p>
        <div class="social-links">
            <a href="https://discord.com" target="_blank">
                <img src="discord-logo.png" alt="Discord">
            </a>
            <a href="https://instagram.com" target="_blank">
                <img src="instagram-logo.png" alt="Instagram">
            </a>
        </div>
    </div>
</body>
</html>';
                default:
                    return '';
            }
        }

        function handle_file_upload($file) {
            global $uploadsDir;
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg'];
            $maxFileSize = 50 * 1024 * 1024; // 50MB

            if ($file['error'] === UPLOAD_ERR_OK) {
                if (in_array($file['type'], $allowedMimeTypes) && $file['size'] <= $maxFileSize) {
                    $fileName = basename($file['name']);
                    $targetFilePath = $uploadsDir . '/' . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                        return $targetFilePath;
                    } else {
                        echo "<h1>Failed to upload file.</h1>";
                    }
                } else {
                    echo "<h1>Invalid file type or file size exceeds 50MB.</h1>";
                }
            } else {
                echo "<h1>Error uploading file.</h1>";
            }

            return null;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = sanitize_input($_POST['action']);
            $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
            $password = isset($_POST['password']) ? sanitize_input($_POST['password']) : '';
            $creationDate = date('Y-m-d H:i:s');

            if ($action === 'signup') {
                // Sign-up logic
                if (array_key_exists($username, $usernames)) {
                    echo "<h1>Username already taken. Please choose another one.</h1>";
                } else {
                    // Add the new username to the array
                    $usernames[$username] = [
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'bio' => '',
                        'creation_date' => $creationDate,
                        'page' => ''
                    ];

                    // Save the updated usernames back to the JSON file
                    file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));

                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['username'] = $username;
                    echo "<h1>Welcome, $username!</h1>";
                    echo "<p>Thank you for visiting our site. We hope you have a great experience!</p>";
                }
            } elseif ($action === 'signin') {
                // Sign-in logic
                if (array_key_exists($username, $usernames) && password_verify($password, $usernames[$username]['password'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['username'] = $username;
                    echo "<h1>Welcome back, $username!</h1>";
                } else {
                    echo "<h1>Invalid username or password.</h1>";
                }
            } elseif ($action === 'create_page' && isset($_SESSION['username'])) {
                // Create a new HTML page
                $username = $_SESSION['username'];
                $pageName = sanitize_input($_POST['page_name']);
                $template = sanitize_input($_POST['template']);
                $pageContent = get_template_content($template);
                $pageFilePath = $pagesDir . '/' . basename($pageName) . '.html';

                if (empty($usernames[$username]['page'])) {
                    if (!file_exists($pageFilePath)) {
                        file_put_contents($pageFilePath, $pageContent);
                        $usernames[$username]['page'] = $pageName;
                        file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));
                        echo "<h1>Page '$pageName' created successfully!</h1>";
                    } else {
                        echo "<h1>Page '$pageName' already exists. Please choose another name.</h1>";
                    }
                } else {
                    echo "<h1>You already have a page. You can only create one page.</h1>";
                }
            } elseif ($action === 'edit_page' && isset($_SESSION['username'])) {
                // Edit an existing HTML page
                $username = $_SESSION['username'];
                $pageName = $usernames[$username]['page'];
                $pageContent = sanitize_html($_POST['page_content']);
                $pageFilePath = $pagesDir . '/' . basename($pageName) . '.html';

                if (file_exists($pageFilePath)) {
                    file_put_contents($pageFilePath, $pageContent);
                    echo "<h1>Page '$pageName' updated successfully!</h1>";
                } else {
                    echo "<h1>Page '$pageName' does not exist.</h1>";
                }
            } elseif ($action === 'upload_file' && isset($_SESSION['username'])) {
                // Handle file upload
                if (isset($_FILES['file'])) {
                    $filePath = handle_file_upload($_FILES['file']);
                    if ($filePath) {
                        echo "<h1>File uploaded successfully: <a href='$filePath' target='_blank'>$filePath</a></h1>";
                    }
                }
            } elseif ($action === 'signout') {
                // Sign-out logic
                session_unset();
                session_destroy();
                echo "<h1>You have been signed out.</h1>";
            } elseif ($action === 'forgot_password') {
                // Forgot password logic
                if (array_key_exists($username, $usernames)) {
                    $newPassword = bin2hex(random_bytes(4)); // Generate a random 8-character password
                    $usernames[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));
                    echo "<h1>New password for $username: $newPassword</h1>";
                } else {
                    echo "<h1>Username not found.</h1>";
                }
            }
        }

        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
            echo "<h1>Welcome back, $username!</h1>";
            ?>
            <form method="post">
                <button type="submit" name="action" value="signout">Sign Out</button>
            </form>
            <?php
            if (!empty($usernames[$username]['page'])) {
                $pageName = $usernames[$username]['page'];
                echo "<p>Your page: <a href='pages/{$pageName}.html' target='_blank'>{$pageName}</a></p>";
                ?>
                <h2>Edit Your Page</h2>
                <form method="post">
                    <textarea name="page_content" placeholder="Enter new page content" required></textarea>
                    <button type="submit" name="action" value="edit_page">Edit Page</button>
                </form>
                <h2>Preview Your Page</h2>
                <iframe src="pages/<?php echo $pageName; ?>.html" width="100%" height="400px"></iframe>
                <h2>Upload Image or Video</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="file" accept="image/*,video/*" required>
                    <button type="submit" name="action" value="upload_file">Upload</button>
                </form>
                <?php
            } else {
                ?>
                <h2>Create a New Page</h2>
                <form method="post">
                    <input type="text" name="page_name" placeholder="Enter page name" required>
                    <select name="template" required>
                        <option value="">Select a template</option>
                        <option value="profile">Profile Template</option>
                    </select>
                    <button type="submit" name="action" value="create_page">Create Page</button>
                </form>
                <?php
            }
        } elseif (isset($_GET['username'])) {
            $username = sanitize_input($_GET['username']);

            if (array_key_exists($username, $usernames)) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio'])) {
                    $bio = sanitize_input($_POST['bio']);
                    $usernames[$username]['bio'] = $bio;
                    file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));
                    echo "<h1>Profile updated for @$username!</h1>";
                } else {
                    echo "<h1>Welcome to the profile of @$username!</h1>";
                    echo "<p>This is the profile page for @$username.</p>";
                    echo "<p>Bio: " . htmlspecialchars($usernames[$username]['bio']) . "</p>";
                    echo "<p>Creation Date: " . htmlspecialchars($usernames[$username]['creation_date']) . "</p>";
                    echo "<form method='post'>
                            <textarea name='bio' placeholder='Enter your bio'>" . htmlspecialchars($usernames[$username]['bio']) . "</textarea>
                            <button type='submit'>Update Profile</button>
                          </form>";
                }
            } else {
                echo "<h1>Profile not found.</h1>";
            }
        } else {
            echo "<h1>Please sign up or sign in.</h1>";
            ?>
            <form method="post">
                <input type="text" name="username" placeholder="Enter your username" required>
                <input type="password" name="password" placeholder="Enter your password" required>
                <button type="submit" name="action" value="signup">Sign Up</button>
                <button type="submit" name="action" value="signin">Sign In</button>
            </form>
            <h2>Forgot Password?</h2>
            <form method="post">
                <input type="text" name="username" placeholder="Enter your username" required>
                <button type="submit" name="action" value="forgot_password">Reset Password</button>
            </form>
            <?php
        }
        ?>
    </div>
    <script>
        // Matrix background effect
        const canvas = document.querySelector('.matrix');
        const ctx = canvas.getContext('2d');

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        const fontSize = 16;
        const columns = canvas.width / fontSize;

        const drops = Array.from({ length: columns }).fill(1);

        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = '#00ff00';
            ctx.font = `${fontSize}px monospace`;

            for (let i = 0; i < drops.length; i++) {
                const text = letters.charAt(Math.floor(Math.random() * letters.length));
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }

                drops[i]++;
            }
        }

        setInterval(draw, 33);
    </script>
</body>
</html>
