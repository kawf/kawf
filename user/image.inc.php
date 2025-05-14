<?php
/**
 * Image handling functions for the forum
 */

require_once('lib/Upload/Upload.php');
require_once('lib/Upload/UploadFactory.php');
require_once('lib/Upload/UploadContext.php');

use Kawf\Upload\{UploadFactory, UploadContext};

/**
 * Check if image uploads are enabled in the configuration
 */
function can_upload_images($upload_config) {
    return isset($upload_config) && ($upload_config['dav']['enabled'] || $upload_config['imgur']['enabled']);
}

/**
 * Get upload configuration
 */
function get_upload_config(): array {
    global $webdav_config, $imgur_client_id;
    return array(
        // DAV configuration
        'dav' => isset($webdav_config) && is_array($webdav_config) ? array(
            'enabled' => !empty($webdav_config['url']),
            'url' => $webdav_config['url'],
            'username' => $webdav_config['username'],
            'password' => $webdav_config['password'],
            'path' => $webdav_config['path'],
            'public_url' => $webdav_config['public_url'],
            'delete_salt' => $webdav_config['delete_salt']
        ):array('enabled'=>false),
        // Imgur configuration
        'imgur' => isset($imgur_client_id) ? array(
            'enabled' => true,
            'client_id' => $imgur_client_id
        ):array('enabled'=>false)
    );
}

function get_uploader() {
    // Get upload configuration
    $upload_config = get_upload_config();

    // Create uploader instance
    $uploader = UploadFactory::create($upload_config);
    if (!$uploader) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'No upload service configured']);
        exit;
    }
    return $uploader;
}

/**
 * Convert PHP ini value to bytes
 */
function ini_val_to_bytes($val) {
    $val = strtolower(trim($val));

    if (preg_match("/^(\d+)([kmg])$/", $val, $m)) {
        $val = intval($m[1]);
        switch ($m[2]) {
        case "k":
            $val *= 1024;
            break;
        case "m";
            $val *= 1024 * 1024;
            break;
        case "g";
            $val *= 1024 * 1024;
            break;
        }
    }

    return intval($val);
}

/**
 * Get maximum allowed image upload size in bytes
 */
function max_image_upload_bytes($upload_config) {
    $pms = ini_val_to_bytes(ini_get("post_max_size"));
    $ums = ini_val_to_bytes(ini_get("upload_max_filesize"));

    // Get the maximum upload size from the configured service
    $service_limit = UploadFactory::getMaxUploadSize($upload_config);

    // Use the smallest limit
    $mb = min($service_limit, $pms, $ums);

    // Leave 10k overhead for other post data
    if ($mb > 10240)
        $mb -= 10240;

    return $mb;
}

/**
 * Uploads an image using the configured upload service
 *
 * Takes an UploadContext containing all necessary upload information and handles
 * the upload process through the appropriate uploader (DAV or Imgur). Returns
 * an array containing the image URL, delete URL, and metadata URL if successful,
 * or an error message if the upload fails.
 *
 * @param UploadContext $context Context containing upload configuration, file data,
 *                             and metadata
 * @return array|null Array containing:
 *                    - url: Public URL of the uploaded image
 *                    - delete_url: URL to delete the image
 *                    - metadata_url: URL to the image metadata (if supported)
 *                    - error: Error message if upload fails
 */
function upload_image(UploadContext $context): ?array {
    if (!can_upload_images($context->getConfig()))
        return array('error' => "No upload service configured");

    $uploader = UploadFactory::create($context->getConfig());

    if (!$uploader) {
        return array('error' => "No upload service configured");
    }

    // Pass metadata to uploader
    $result = $uploader->upload(
        $context->getFilepath(),
        $context->getNamespace(),
        $context->createMetadata()
    );

    if (!$result) {
        return array('error' => $uploader->getError());
    }

    return $result;
}

/**
 * Creates an UploadContext for image uploads
 *
 * @param array $upload_config Upload configuration
 * @param string $filepath Path to the file to upload
 * @param array $fileMetadata File metadata from client
 * @param int $userId User ID of the uploader
 * @param string $forumId Forum ID to create namespace from
 * @return UploadContext Context object for the upload
 */
function create_upload_context(array $upload_config, string $filepath, array $fileMetadata, int $userId, string $forumId): UploadContext {
    return new UploadContext(
        $upload_config,
        $filepath,
        $fileMetadata,
        $userId,
        $userId . '/' . $forumId
    );
}

/**
 * Updates image metadata with a message reference
 *
 * @param array $upload_config Upload configuration
 * @param string $metadata_url URL to the image metadata
 * @param string $forum_shortname Forum shortname for URL construction
 * @param int $message_id Message ID to add
 * @return bool True if metadata was updated successfully
 */
function update_image_metadata(array $upload_config, string $metadata_url, string $forum_shortname, int $message_id): bool {
    $uploader = UploadFactory::create($upload_config);
    if (!$uploader || !$uploader->supports_metadata()) {
        return false;
    }

    // Always use the full relative path for metadata operations
    $full_metadata_path = $metadata_url;
    $metadata = $uploader->load_metadata($full_metadata_path);
    if (!$metadata) {
        return false;
    }

    // Add message reference
    $message_url = '/' . $forum_shortname . '/msgs/' . $message_id . '.phtml';
    if (!in_array($message_url, $metadata->messages)) {
        $metadata->messages[] = $message_url;
        return $uploader->save_metadata($full_metadata_path, $metadata);
    }

    return true;
}

/**
 * Delete an uploaded image
 *
 * @param string $delete_url The deletion URL from the upload result
 * @param int $userId The ID of the user requesting deletion
 * @return bool True if deletion was successful
 */
function delete_image(string $delete_url, int $userId): bool {
    global $uploader;

    // Parse the delete URL
    $parsed = parse_url($delete_url);
    if (!$parsed) {
        error_log("Invalid delete URL: $delete_url");
        return false;
    }

    // Extract path and query parameters
    $path = ltrim($parsed['path'], '/');
    parse_str($parsed['query'] ?? '', $query);

    // Handle different uploader types
    if ($path === 'deleteimage.phtml') {
        // DAV deletion with new parameter format
        return $uploader->delete(
            $query['url'] ?? '',
            $query['hash'] ?? '',
            (int)($query['t'] ?? 0),
            $userId
        );
    } else if (strpos($delete_url, 'api.imgur.com/3/image/') !== false) {
        // Imgur deletion
        $deletehash = basename($delete_url);
        return $uploader->delete($deletehash);
    }

    error_log("Unknown delete URL format: $delete_url");
    return false;
}
// vim: set ts=8 sw=4 et:
