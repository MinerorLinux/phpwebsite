<?php
if (isset($_GET['id'])) {
    $short_id = $_GET['id'];
    $mappings = [];
    if (file_exists('mappings.json')) {
        $mappings = json_decode(file_get_contents('mappings.json'), true);
    }
    if (isset($mappings[$short_id])) {
        $file_path = $mappings[$short_id];
        if (file_exists($file_path)) {
            header("Location: $file_path");
            exit;
        } else {
            echo "The file no longer exists.";
        }
    } else {
        echo "Invalid link.";
    }
} else {
    echo "No ID provided.";
}
?>