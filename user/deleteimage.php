<?php
require_once("image.inc.php");
require_once("include/page-yatt.inc.php");

// Get uploader instance
$uploader = get_uploader();

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['path'])) {
        header('HTTP/1.1 400 Bad Request');
        exit('Missing or invalid request data');
    }

    // Authenticated user deletion path
    if (!isset($user) || !$user) {
        header('HTTP/1.1 401 Unauthorized');
        exit('Unauthorized');
    }

    if (!can_upload_images()) {
        header('HTTP/1.1 403 Forbidden');
        exit('Forbidden');
    }

    // Delete using namespace verification
    if (delete_image($uploader, $data['path'], $user->aid)) {
        header('HTTP/1.1 200 OK');
        exit('Image deleted successfully');
    }
} else {
    // Hash-based API deletion path
    $delete_url = $_GET['delete_url'] ?? '';
    if (empty($delete_url)) {
        header('HTTP/1.1 400 Bad Request');
        exit('Missing delete URL');
    }

    // Delete using hash verification
    if (delete_image_by_url($uploader, $delete_url, 0)) {
        header('HTTP/1.1 200 OK');
        exit('Image deleted successfully');
    }
}

header('HTTP/1.1 500 Internal Server Error');
exit('Failed to delete image');

// vim: set ts=8 sw=4 et:
