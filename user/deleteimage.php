<?php
require_once("image.inc.php");
require_once("include/page-yatt.inc.php");

// Get uploader instance
$uploader = get_uploader();

// Set JSON response headers
header('Content-Type: application/json');

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user->req();

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['path'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid request data']);
        exit;
    }

    // Authenticated user deletion path
    if (!isset($user) || !$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if (!can_upload_images()) {
        http_response_code(403);
        echo json_encode(['error' => 'Image uploads are not enabled']);
        exit;
    }

    // Delete using path-based deletion with user verification
    if (delete_image($uploader, $data['path'], $user->aid)) {
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
        exit;
    }

    // If we get here, deletion failed - get error from uploader
    http_response_code(500);
    echo json_encode(['error' => $uploader->getError() ?? 'Failed to delete image']);
    exit;
} else {
    // Hash-based API deletion path
    // Expected URL formats:
    // - DAV: deletemessage.phtml?url=path/to/file&hash=abc123&t=1234567890
    // - Imgur: deletemessage.phtml?url=https://imgur.com/abc123
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    if (empty($queryString)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing delete URL']);
        exit;
    }

    // Delete using URL-based deletion
    if (delete_image_by_url($uploader, $queryString)) {
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
        exit;
    }

    // If we get here, deletion failed - get error from uploader
    http_response_code(500);
    echo json_encode(['error' => $uploader->getError() ?? 'Failed to delete image']);
    exit;
}

// vim: set ts=8 sw=4 et:
