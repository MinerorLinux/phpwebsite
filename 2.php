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
            background: linear-gradient(to right, #f0f0f0, #e0e0e0);
            color: #333;
        }
        .header {
            background: #007bff;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 1.5em;
        }
        .header button {
            background: #0056b3;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }
        .header button:hover {
            background: #003f7f;
            transform: scale(1.05);
        }
        .header button:active {
            transform: scale(0.95);
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .welcome-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .welcome-container h2 {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: #333;
        }
        .welcome-container p {
            font-size: 1em;
            margin-bottom: 15px;
            color: #666;
        }
        .welcome-container form {
            display: flex;
            flex-direction: column;
        }
        .welcome-container input, .welcome-container textarea, .welcome-container button {
            font-size: 1em;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            outline: none;
            background: #fff;
            color: #333;
            width: 100%;
            max-width: 350px;
        }
        .welcome-container button {
            background: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }
        .welcome-container button:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
        .welcome-container button:active {
            transform: scale(0.95);
        }
        .progress-bar {
            width: 100%;
            background: #ccc;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar-inner {
            height: 20px;
            width: 0;
            background: #007bff;
            transition: width 0.3s;
        }
        .preview {
            margin: 10px 0;
        }
        .preview img, .preview video, .preview audio {
            max-width: 100%;
            border-radius: 5px;
        }
        .member-counter {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
        }
    </style>
    <?php
    session_start();
    $nonce = bin2hex(random_bytes(16));
    ?>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https://discord.com https://instagram.com; script-src 'self' 'nonce-<?= $nonce ?>'; style-src 'self' 'unsafe-inline';">
</head>
<body>
    <div class="header">
        <h1>Welcome Page</h1>
        <?php if (isset($_SESSION['username'])): ?>
            <form method="post" action="?action=signup">
                <button type="submit" name="action" value="signout">Sign Out</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="container">
        <?php
        $filePath = 'usernames.json';
        $usersDir = 'users';

        // Check if the JSON file exists, if not create it
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([]));
        }

        // Check if the users directory exists, if not create it
        if (!file_exists($usersDir)) {
            mkdir($usersDir, 0777, true);
        }

        // Read the existing usernames from the JSON file
        $jsonContent = file_get_contents($filePath);
        $usernames = json_decode($jsonContent, true);

        // Ensure $usernames is an array
        if (!is_array($usernames)) {
            $usernames = [];
        }

        // Member counter
        $memberCount = count($usernames);
        echo "<div class='member-counter'>Total Members: $memberCount</div>";
        ?>
        <div class="welcome-container">
            <?php
            function sanitize_input($data) {
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
            }

            function handle_file_upload($file, $username) {
                global $usersDir;
                $allowedMimeTypes = [
                    'image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/tiff', 'image/webp',
                    'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                    'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/x-ms-wma'
                ];
                $maxFileSize = 50 * 1024 * 1024; // 50MB

                $userDir = $usersDir . '/' . $username;
                if (!file_exists($userDir)) {
                    mkdir($userDir, 0777, true);
                }

                if ($file['error'] === UPLOAD_ERR_OK) {
                    if (in_array($file['type'], $allowedMimeTypes) && $file['size'] <= $maxFileSize) {
                        $fileName = basename($file['name']);
                        $targetFilePath = $userDir . '/' . $fileName;

                        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                            return $targetFilePath;
                        } else {
                            echo json_encode(['error' => 'Failed to upload file.']);
                        }
                    } else {
                        echo json_encode(['error' => 'Invalid file type or file size exceeds 50MB.']);
                    }
                } else {
                    echo json_encode(['error' => 'Error uploading file.']);
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
                            'creation_date' => $creationDate,
                            'page' => ''
                        ];

                        // Save the updated usernames back to the JSON file
                        file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));

                        // Create user directory
                        $userDir = $usersDir . '/' . $username;
                        if (!file_exists($userDir)) {
                            mkdir($userDir, 0777, true);
                        }

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
                    $pageContent = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . $pageName . '</title></head><body><h1>' . $pageName . '</h1></body></html>';
                    $userDir = $usersDir . '/' . $username;
                    $pageFilePath = $userDir . '/' . basename($pageName) . '.html';

                    if (!empty($usernames[$username]['page'])) {
                        // If the page exists but is broken, allow the user to create a new one
                        if (file_exists($pageFilePath)) {
                            unlink($pageFilePath);
                        }
                        $usernames[$username]['page'] = '';
                    }

                    if (!file_exists($pageFilePath)) {
                        file_put_contents($pageFilePath, $pageContent);
                        $usernames[$username]['page'] = $pageName;
                        file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));
                        echo "<h1>Page '$pageName' created successfully!</h1>";
                    } else {
                        echo "<h1>Page '$pageName' already exists. Please choose another name.</h1>";
                    }
                } elseif ($action === 'edit_page' && isset($_SESSION['username'])) {
                    // Edit an existing HTML page
                    $username = $_SESSION['username'];
                    $pageName = $usernames[$username]['page'];
                    $pageContent = sanitize_input($_POST['page_content']);
                    $userDir = $usersDir . '/' . $username;
                    $pageFilePath = $userDir . '/' . basename($pageName) . '.html';

                    if (file_exists($pageFilePath)) {
                        file_put_contents($pageFilePath, $pageContent);
                        echo "<h1>Page '$pageName' updated successfully!</h1>";
                    } else {
                        echo "<script nonce='<?= $nonce ?>'>
                            if (confirm('Page \"$pageName\" does not exist. Would you like to create a new page?')) {
                                window.location.href = 'index.php?action=create_page';
                            }
                        </script>";
                    }
                } elseif ($action === 'upload_file' && isset($_SESSION['username'])) {
                    // Handle file upload
                    if (isset($_FILES['file'])) {
                        $filePath = handle_file_upload($_FILES['file'], $_SESSION['username']);
                        if ($filePath) {
                            echo json_encode(['filePath' => $filePath]);
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
                if (!empty($usernames[$username]['page'])) {
                    $pageName = $usernames[$username]['page'];
                    echo "<p>Your page: <a href='users/{$username}/{$pageName}.html' target='_blank'>{$pageName}</a></p>";
                    ?>
                    <h2>Edit Your Page</h2>
                    <form method="post">
                        <textarea name="page_content" placeholder="Enter new page content" required></textarea>
                        <button type="submit" name="action" value="edit_page">Edit Page</button>
                    </form>
                    <h2>Preview Your Page</h2>
                    <iframe src="users/<?php echo $username; ?>/<?php echo $pageName; ?>.html" width="100%" height="200px"></iframe>
                    <h2>Upload Image, Video, Audio, or HTML</h2>
                    <form id="uploadForm" method="post" enctype="multipart/form-data" action="?action=upload_file">
                        <input type="file" name="file" accept="image/*,video/*,audio/*,text/html" required>
                        <button type="submit" name="action" value="upload_file">Upload</button>
                    </form>
                    <div class="progress-bar">
                        <div class="progress-bar-inner"></div>
                    </div>
                    <div class="preview"></div>
                    <?php
                } else {
                    ?>
                    <h2>Create a New Page</h2>
                    <form method="post">
                        <input type="text" name="page_name" placeholder="Enter page name" required>
                        <button type="submit" name="action" value="create_page">Create Page</button>
                    </form>
                    <?php
                }
                // Add the sign-out button here
                ?>
                <form method="post">
                    <button type="submit" name="action" value="signout">Sign Out</button>
                </form>
                <?php
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
    </div>
    <script nonce="<?= $nonce ?>">
        // File upload progress and preview
        const uploadForm = document.getElementById('uploadForm');
        const progressBar = document.querySelector('.progress-bar-inner');
        const preview = document.querySelector('.preview');

        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(uploadForm);
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(event) {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.filePath) {
                            const fileType = response.filePath.split('.').pop().toLowerCase();
                            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'].includes(fileType)) {
                                preview.innerHTML = `<img src="${response.filePath}" alt="Uploaded Image">`;
                            } else if (['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv'].includes(fileType)) {
                                preview.innerHTML = `<video controls><source src="${response.filePath}" type="video/${fileType}"></video>`;
                            } else if (['mp3', 'wav', 'ogg', 'wma'].includes(fileType)) {
                                preview.innerHTML = `<audio controls><source src="${response.filePath}" type="audio/${fileType}"></audio>`;
                            } else if (fileType === 'html') {
                                preview.innerHTML = `<iframe src="${response.filePath}" width="100%" height="200px"></iframe>`;
                            }
                        } else if (response.error) {
                            alert(response.error);
                        }
                    } catch (e) {
                        alert('Error processing the response.');
                    }
                }
            });

            xhr.open('POST', uploadForm.action);
            xhr.send(formData);
        });
    </script>
</body>
</html>
