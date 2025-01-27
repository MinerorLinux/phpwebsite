<?php
session_start();

require 'vendor/autoload.php';

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    error_log("Error loading .env file: " . $e->getMessage());
    die("Error loading configuration.");
}

$adminPassword = $_ENV['ADMIN_PASSWORD'] ?? null; // Load the admin password from the environment variable
if (!$adminPassword) {
    die("Admin password not set in .env file.");
}

$banFilePath = 'banned_ips.json';
$tempBanFilePath = 'temp_banned_ips.json';
$logFilePath = 'user_activity.log';

function initializeFile($filePath, $defaultContent = '[]') {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $defaultContent);
    }
}

initializeFile($banFilePath);
initializeFile($tempBanFilePath);

$bannedIps = json_decode(file_get_contents($banFilePath), true) ?: [];
$tempBannedIps = json_decode(file_get_contents($tempBanFilePath), true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $adminPassword) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
    } else {
        $error = 'Invalid admin password';
    }
}

if (isset($_SESSION['admin'])) {
    $filePath = 'usernames.json';
    $uploadsDir = 'uploads';
    initializeFile($filePath);
    $usernames = json_decode(file_get_contents($filePath), true) ?: [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        $message = $error = '';

        switch ($action) {
            case 'delete_user':
                $username = sanitize_input($_POST['username']);
                if (isset($usernames[$username])) {
                    unset($usernames[$username]);
                    file_put_contents($filePath, json_encode($usernames, JSON_PRETTY_PRINT));
                    $message = "User '$username' deleted successfully.";
                } else {
                    $error = 'Username not found.';
                }
                break;

            case 'delete_file':
                $filePath = sanitize_input($_POST['file_path']);
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $message = "File '$filePath' deleted successfully.";
                } else {
                    $error = 'File not found.';
                }
                break;

            case 'ban_ip':
                $ipAddress = sanitize_input($_POST['ip_address']);
                if (!in_array($ipAddress, $bannedIps)) {
                    $bannedIps[] = $ipAddress;
                    file_put_contents($banFilePath, json_encode($bannedIps, JSON_PRETTY_PRINT));
                    $message = "IP address '$ipAddress' banned successfully.";
                } else {
                    $error = 'IP address already banned.';
                }
                break;

            case 'temp_ban_ip':
                $ipAddress = sanitize_input($_POST['ip_address']);
                $duration = intval($_POST['duration']);
                $expiryTime = time() + ($duration * 60);
                if (!isset($tempBannedIps[$ipAddress])) {
                    $tempBannedIps[$ipAddress] = $expiryTime;
                    file_put_contents($tempBanFilePath, json_encode($tempBannedIps, JSON_PRETTY_PRINT));
                    $message = "IP address '$ipAddress' temporarily banned for $duration minutes.";
                } else {
                    $error = 'IP address already temporarily banned.';
                }
                break;

            case 'unban_ip':
                $ipAddress = sanitize_input($_POST['ip_address']);
                if (($key = array_search($ipAddress, $bannedIps)) !== false) {
                    unset($bannedIps[$key]);
                    file_put_contents($banFilePath, json_encode($bannedIps, JSON_PRETTY_PRINT));
                    $message = "IP address '$ipAddress' unbanned successfully.";
                } else {
                    $error = 'IP address not found in banned list.';
                }
                break;

            case 'unban_temp_ip':
                $ipAddress = sanitize_input($_POST['ip_address']);
                if (isset($tempBannedIps[$ipAddress])) {
                    unset($tempBannedIps[$ipAddress]);
                    file_put_contents($tempBanFilePath, json_encode($tempBannedIps, JSON_PRETTY_PRINT));
                    $message = "IP address '$ipAddress' removed from temporary ban list.";
                } else {
                    $error = 'IP address not found in temporary banned list.';
                }
                break;

            case 'change_password':
                $newPassword = sanitize_input($_POST['new_password']);
                $envContent = file_get_contents(__DIR__ . '/.env');
                $envContent = preg_replace('/^ADMIN_PASSWORD=.*$/m', "ADMIN_PASSWORD=$newPassword", $envContent);
                file_put_contents(__DIR__ . '/.env', $envContent);
                $message = 'Admin password changed successfully.';
                break;

            case 'upload_file':
                if (isset($_FILES['file_to_upload'])) {
                    $uploadDir = sanitize_input($_POST['upload_dir']);
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $targetFile = $uploadDir . '/' . basename($_FILES['file_to_upload']['name']);
                    if (move_uploaded_file($_FILES['file_to_upload']['tmp_name'], $targetFile)) {
                        $message = "File '" . htmlspecialchars(basename($_FILES['file_to_upload']['name'])) . "' uploaded successfully to '$uploadDir'.";
                    } else {
                        $error = 'File upload failed.';
                    }
                }
                break;
        }
    }
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function getDirectories($dir) {
    $directories = [];
    foreach (scandir($dir) as $file) {
        if ($file !== '.' && $file !== '..' && is_dir($dir . '/' . $file)) {
            $directories[] = $file;
        }
    }
    return $directories;
}

$uploadsDir = 'uploads';
$uploadDirs = getDirectories($uploadsDir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
        }
        .admin-panel {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            overflow-y: auto;
            max-height: 90vh;
        }
        h2 {
            margin-top: 0;
        }
        .message, .error {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        form {
            margin-bottom: 20px;
        }
        form h2 {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        input[type="text"], input[type="password"], input[type="file"], select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            background: #f9f9f9;
            margin-bottom: 5px;
            padding: 10px;
            border-radius: 4px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-panel">
        <?php if (isset($_SESSION['admin'])): ?>
            <h2>Admin Panel</h2>
            <?php if (isset($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <div class="dashboard">
                <h3>Dashboard</h3>
                <p>Number of Users: <?php echo count($usernames); ?></p>
                <p>Number of Files: <?php echo count(array_diff(scandir($uploadsDir), array('.', '..'))); ?></p>
                <p>Number of Banned IPs: <?php echo count($bannedIps); ?></p>
                <p>Number of Temporarily Banned IPs: <?php echo count($tempBannedIps); ?></p>
            </div>
            <form method="post" enctype="multipart/form-data">
                <h2>Upload File</h2>
                <input type="file" name="file_to_upload" required>
                <select name="upload_dir" required>
                    <option value="" disabled selected>Select directory</option>
                    <?php foreach ($uploadDirs as $dir): ?>
                        <option value="<?php echo htmlspecialchars($uploadsDir . '/' . $dir); ?>"><?php echo htmlspecialchars($dir); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="upload_file">Upload File</button>
            </form>
            <form method="post">
                <h2>Delete User</h2>
                <select name="username" required>
                    <option value="" disabled selected>Select a user</option>
                    <?php foreach ($usernames as $username => $details): ?>
                        <option value="<?php echo htmlspecialchars($username); ?>"><?php echo htmlspecialchars($username); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="delete_user">Delete User</button>
            </form>
            <form method="post">
                <h2>Delete File</h2>
                <select name="file_path" required>
                    <option value="" disabled selected>Select a file</option>
                    <?php
                    $files = array_diff(scandir($uploadsDir), array('.', '..'));
                    foreach ($files as $file): ?>
                        <option value="<?php echo htmlspecialchars($uploadsDir . '/' . $file); ?>"><?php echo htmlspecialchars($file); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="delete_file">Delete File</button>
            </form>
            <form method="post">
                <h2>Ban IP Address</h2>
                <input type="text" name="ip_address" placeholder="Enter IP address to ban" required>
                <button type="submit" name="action" value="ban_ip">Ban IP</button>
            </form>
            <form method="post">
                <h2>Temporarily Ban IP Address</h2>
                <input type="text" name="ip_address" placeholder="Enter IP address to temporarily ban" required>
                <input type="number" name="duration" placeholder="Duration in minutes" required>
                <button type="submit" name="action" value="temp_ban_ip">Temporarily Ban IP</button>
            </form>
            <form method="post">
                <h2>Unban IP Address</h2>
                <select name="ip_address" required>
                    <option value="" disabled selected>Select an IP address to unban</option>
                    <?php foreach ($bannedIps as $ip): ?>
                        <option value="<?php echo htmlspecialchars($ip); ?>"><?php echo htmlspecialchars($ip); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="unban_ip">Unban IP</button>
            </form>
            <form method="post">
                <h2>Remove Temporary Ban</h2>
                <select name="ip_address" required>
                    <option value="" disabled selected>Select an IP address to remove temporary ban</option>
                    <?php foreach ($tempBannedIps as $ip => $expiryTime): ?>
                        <option value="<?php echo htmlspecialchars($ip); ?>"><?php echo htmlspecialchars($ip); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="unban_temp_ip">Remove Temporary Ban</button>
            </form>
            <form method="post">
                <h2>Change Admin Password</h2>
                <input type="password" name="new_password" placeholder="Enter new admin password" required>
                <button type="submit" name="action" value="change_password">Change Password</button>
            </form>
            <h2>Users</h2>
            <ul>
                <?php foreach ($usernames as $username => $details): ?>
                    <li><?php echo htmlspecialchars($username); ?></li>
                <?php endforeach; ?>
            </ul>
            <h2>Files</h2>
            <ul>
                <?php
                $files = array_diff(scandir($uploadsDir), array('.', '..'));
                foreach ($files as $file): ?>
                    <li><?php echo htmlspecialchars($file); ?></li>
                <?php endforeach; ?>
            </ul>
            <h2>Banned IPs</h2>
            <ul>
                <?php foreach ($bannedIps as $ip): ?>
                    <li><?php echo htmlspecialchars($ip); ?></li>
                <?php endforeach; ?>
            </ul>
            <h2>Temporarily Banned IPs</h2>
            <ul>
                <?php foreach ($tempBannedIps as $ip => $expiryTime): ?>
                    <li><?php echo htmlspecialchars($ip) . ' (expires in ' . round(($expiryTime - time()) / 60) . ' minutes)'; ?></li>
                <?php endforeach; ?>
            </ul>
            <h2>User Activity Logs</h2>
            <ul>
                <?php
                if (file_exists($logFilePath)) {
                    $logContent = file_get_contents($logFilePath);
                    $logEntries = explode("\n", $logContent);
                    foreach ($logEntries as $entry): ?>
                        <li><?php echo htmlspecialchars($entry); ?></li>
                    <?php endforeach;
                }
                ?>
            </ul>
            <a href="index.php" class="back-link">Back to Home</a>
        <?php else: ?>
            <h2>Admin Login</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="admin_password" placeholder="Enter admin password" required>
                <button type="submit">Login</button>
            </form>
            <a href="index.php" class="back-link">Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>