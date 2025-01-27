<?php
session_start();

$filePath = 'usernames.json';
$pagesDir = 'pages';
$uploadsDir = 'uploads';
$adminUsername = 'nigerian';   
$adminPassword = 'admin123'; 
$banFilePath = 'banned_ips.json';
$tempBanFilePath = 'temp_banned_ips.json';

if (!file_exists($banFilePath)) {
    file_put_contents($banFilePath, json_encode([]));
}

if (!file_exists($tempBanFilePath)) {
    file_put_contents($tempBanFilePath, json_encode([]));
}

$banContent = file_get_contents($banFilePath);
$bannedIps = json_decode($banContent, true);

if (!is_array($bannedIps)) {
    $bannedIps = [];
}

$tempBanContent = file_get_contents($tempBanFilePath);
$tempBannedIps = json_decode($tempBanContent, true);

if (!is_array($tempBannedIps)) {
    $tempBannedIps = [];
}

// Check if the user's IP is banned
$userIp = $_SERVER['REMOTE_ADDR'];
if (in_array($userIp, $bannedIps) || (array_key_exists($userIp, $tempBannedIps) && $tempBannedIps[$userIp] > time())) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'You are banned from modifying this website.']);
    exit;
}

if (!file_exists($filePath)) {
    file_put_contents($filePath, json_encode([]));
}

if (!file_exists($pagesDir)) {
    mkdir($pagesDir, 0777, true);
}
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$jsonContent = file_get_contents($filePath);
$usernames = json_decode($jsonContent, true);

if (!is_array($usernames)) {
    $usernames = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'signout') {
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Signed out successfully']);
        exit;
    }

    if ($action === 'signin') {
        $username = sanitize_input($_POST['username']);
        $password = sanitize_input($_POST['password']);

        if ($username === $adminUsername && $password === $adminPassword) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            echo json_encode(['status' => 'success', 'message' => 'Admin login successful']);
        } elseif (array_key_exists($username, $usernames) && password_verify($password, $usernames[$username]['password'])) {
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            echo json_encode(['status' => 'success', 'message' => 'User login successful']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
        }
        exit;
    }

    if ($action === 'delete_user' && isset($_SESSION['admin'])) {
        $username = sanitize_input($_POST['username']);
        if (array_key_exists($username, $usernames)) {
            unset($usernames[$username]);
            file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'success', 'message' => "User '$username' deleted successfully."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username not found.']);
        }
        exit;
    }

    if ($action === 'delete_file' && isset($_SESSION['admin'])) {
        $filePath = sanitize_input($_POST['file_path']);
        if (file_exists($filePath)) {
            unlink($filePath);
            echo json_encode(['status' => 'success', 'message' => "File '$filePath' deleted successfully."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        }
        exit;
    }

    if ($action === 'upload_file' && isset($_FILES['file'])) {
        $uploadedFilePath = handle_file_upload($_FILES['file']);
        if ($uploadedFilePath) {
            echo json_encode(['status' => 'success', 'message' => "File uploaded successfully.", 'file_path' => $uploadedFilePath]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
        }
        exit;
    }

    if ($action === 'create_page' && isset($_SESSION['username'])) {
        $pageName = sanitize_input($_POST['page_name']);
        $pageContent = sanitize_html($_POST['page_content']);
        $pageFilePath = $pagesDir . '/' . $pageName . '.html';

        file_put_contents($pageFilePath, $pageContent);
        $usernames[$_SESSION['username']]['page'] = $pageName;
        file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));

        echo json_encode(['status' => 'success', 'message' => "Page '$pageName' created successfully."]);
        exit;
    }

    if ($action === 'edit_page' && isset($_SESSION['username'])) {
        $pageName = $usernames[$_SESSION['username']]['page'];
        $pageContent = sanitize_html($_POST['page_content']);
        $pageFilePath = $pagesDir . '/' . $pageName . '.html';

        file_put_contents($pageFilePath, $pageContent);

        echo json_encode(['status' => 'success', 'message' => "Page '$pageName' updated successfully."]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sanitize_html($html) {
    $allowed_tags = '<p><a><b><i><u><strong><em><br><ul><ol><li><h1><h2><h3><h4><h5><h6><div><span><img><iframe><style><script>';
    return strip_tags($html, $allowed_tags);
}

function handle_file_upload($file) {
    global $uploadsDir;
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg', 'audio/mpeg', 'audio/ogg', 'audio/wav'];
    $maxFileSize = 50 * 1024 * 1024; // 50MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        if (in_array($file['type'], $allowedMimeTypes) && $file['size'] <= $maxFileSize) {
            $fileName = basename($file['name']);
            $targetFilePath = $uploadsDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return $targetFilePath;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload file.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type or file size exceeds 50MB.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error uploading file.']);
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>fentbusgaming</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/fontawesome.min.css" integrity="sha384-NvKbDTEnL+A8F/AA5Tc5kmMLSJHUO868P+lDtTpJIeQdGYaUIuLr4lVGOEA1OcMy" crossorigin="anonymous">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' https://discord.com https://instagram.com; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com;">
</head>
<body>
    <header class="header">
        <?php if (isset($_SESSION['username']) || isset($_SESSION['admin'])): ?>
            <form method="post" id="signoutForm">
                <button type="submit" name="action" value="signout">Sign Out</button>
            </form>
        <?php endif; ?>
    </header>
    <main class="container">
        <section class="welcome-container">
            <?php
            if (isset($_SESSION['admin'])) {
                // Admin panel
                echo "<h1>Admin Panel</h1>";
                ?>
                <h2>Delete User</h2>
                <form method="post" id="adminDeleteUserForm">
                    <input type="text" name="username" placeholder="Enter username to delete" required>
                    <button type="submit" name="action" value="delete_user">Delete User</button>
                </form>
                <h2>Delete File</h2>
                <form method="post" id="adminDeleteFileForm">
                    <input type="text" name="file_path" placeholder="Enter file path to delete" required>
                    <button type="submit" name="action" value="delete_file">Delete File</button>
                </form>
                <?php
            } elseif (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
                echo "<h1>Welcome, $username!</h1>";
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
                    <iframe src="pages/<?php echo $pageName; ?>.html" width="100%" height="200px"></iframe>
                    <h2>Upload Image, Video, or Audio</h2>
                    <form id="uploadForm" method="post" enctype="multipart/form-data" action="index.php?action=upload_file">
                        <input type="file" name="file" accept="image/*,video/*,audio/*" required>
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
                        <textarea name="page_content" placeholder="Enter page content" required></textarea>
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
                <form method="post" id="signinForm">
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
        </section>
    </main>
    <footer>
        <p>&copy; 2023 Your Website. All rights reserved.</p>
    </footer>
    <button id="adminButton" class="admin-button">
        <i class="fas fa-user-shield"></i>
    </button>
    <script src="scripts.js"></script>
</body>
</html>