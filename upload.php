<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .upload-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .upload-container h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .upload-container input[type="file"] {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .upload-container button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .upload-container button:hover {
            background-color: #0056b3;
        }
        .uploaded-file {
            margin-top: 20px;
        }
        .uploaded-file img {
            max-width: 100%;
            height: auto;
        }
        .uploaded-file a {
            display: block;
            margin-top: 20px;
            color: #007BFF;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .uploaded-file a:hover {
            color: #0056b3;
        }
        .error-message {
            color: #ff0000;
            margin-top: 20px;
        }
        .success-message {
            color: #28a745;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>Upload File</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" id="fileToUpload">
            <button type="submit" name="submit">Upload</button>
        </form>
        <?php
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Database connection
        $host = "pixie-db.fentbusgaming.com";
        $port = 3306;
        $socket = "";
        $user = "sbJTvtCOhYdbaTiM";
        $password = "";
        $dbname = "";

        $conn = new mysqli($host, $user, $password, $dbname, $port, $socket)
            or die('Could not connect to the database server' . mysqli_connect_error());

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES["fileToUpload"]["name"]));
            $uploadOk = 1;

            // Check if file was uploaded without errors
            if ($_FILES["fileToUpload"]["error"] != UPLOAD_ERR_OK) {
                echo "<p class='error-message'>Sorry, there was an error uploading your file.</p>";
                $uploadOk = 0;
            }

            // Check file size
            if ($_FILES["fileToUpload"]["size"] > 50000000) { // 50MB limit
                echo "<p class='error-message'>Sorry, your file is too large.</p>";
                $uploadOk = 0;
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                echo "<p class='error-message'>Sorry, your file was not uploaded.</p>";
            } else {
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                    // Generate a unique ID for the shortened link
                    $short_id = uniqid();
                    $short_link = "shorten.php?id=" . $short_id;

                    // Save the mapping of short ID to file path in the database
                    $stmt = $conn->prepare("INSERT INTO files (short_id, file_path) VALUES (?, ?)");
                    $stmt->bind_param("ss", $short_id, $target_file);
                    $stmt->execute();
                    $stmt->close();

                    echo "<p class='success-message'>The file ". htmlspecialchars(basename($_FILES["fileToUpload"]["name"])). " has been uploaded.</p>";
                    echo "<div class='uploaded-file'><img src='$target_file' alt='Uploaded File'><a href='$target_file' download>Download File</a></div>";
                    echo "<div class='uploaded-file'><a href='$short_link'>Shortened Link</a></div>";
                } else {
                    echo "<p class='error-message'>Sorry, there was an error uploading your file.</p>";
                }
            }
        }

        $conn->close();
        ?>
    </div>
</body>
</html>