<?php
require_once("image.inc.php");

use Kawf\Upload\UploadFactory;

// Get forum
$forum = get_forum();

// Check if user is logged in
if (!$user->valid()) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'You must be logged in to delete images']);
    exit;
}

// Get the delete URL and hash from the request
$path = $_GET['url'] ?? '';
$hash = $_GET['hash'] ?? '';
$timestamp = (int)($_GET['t'] ?? 0);

if (empty($path) || empty($hash)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$uploader = get_uploader();

// Attempt to delete the image
$result = $uploader->delete($path, $hash, $timestamp, $user->aid);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Failed to delete image']);
}

// vim: set ts=8 sw=4 et:
