<?php
/**
 * Image handling functions for the forum
 */

require_once('lib/Upload/Upload.php');
require_once('lib/Upload/UploadFactory.php');

use Kawf\Upload\{ImageMetadata, UploadFactory, Upload};

class UploadContext {
    public array $config;
    public string $filepath;
    public array $fileMetadata;
    public int $userId;
    public string $namespace;
    public ?int $messageId = null;

    public function __construct(
        array $config,
        string $filepath,
        array $fileMetadata,
        int $userId,
        string $namespace
    ) {
        $this->config = $config;
        $this->filepath = $filepath;
        $this->fileMetadata = $fileMetadata;
        $this->userId = $userId;
        $this->namespace = $namespace;
    }

    public function createMetadata(): ImageMetadata {
        return ImageMetadata::createMetadata(
            $this->filepath,
            $this->fileMetadata,
            $this->userId
        );
    }
}

/**
 * Check if image uploads are enabled in the configuration
 */
function can_upload_images($upload_config = null) {
    if ($upload_config == null) {
        $upload_config = get_upload_config();
    }
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

function get_uploader(): Upload {
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
    if (!can_upload_images($context->config))
        return array('error' => "No upload service configured");

    $uploader = UploadFactory::create($context->config);

    if (!$uploader) {
        return array('error' => "No upload service configured");
    }

    // Pass metadata to uploader
    $result = $uploader->upload(
        $context->filepath,
        $context->namespace,
        $context->createMetadata()
    );

    if (!$result) {
        return array('error' => $uploader->getError());
    }

    return $result;
}

/**
 * Updates image metadata with a message reference
 *
 * @param array $upload_config Upload configuration
 * @param string $metadata_url URL to the image metadata
 * @param string $forum_shortname Forum shortname for URL construction
 * @param int $message_id Message ID to add
 * @return string|null Error message if metadata update fails, null if successful
 */
function update_image_metadata(array $upload_config, string $metadata_url, string $forum_shortname, int $message_id): ?string {
    $uploader = UploadFactory::create($upload_config);
    if (!$uploader || !$uploader->supports_metadata()) {
        return "No upload service configured";
    }

    // Always use the full relative path for metadata operations
    $full_metadata_path = $metadata_url;
    $metadata = $uploader->load_metadata($full_metadata_path);
    if (!$metadata) {
        return "No metadata found for $full_metadata_path: " . $uploader->getError();
    }

    // Add message reference
    $message_url = '/' . $forum_shortname . '/msgs/' . $message_id . '.phtml';
    if (!in_array($message_url, $metadata->messages)) {
        $metadata->messages[] = $message_url;
        if (!$uploader->save_metadata($full_metadata_path, $metadata)) {
            return "Failed to save metadata for $full_metadata_path: " . $uploader->getError();
        };
    }
    return null;
}

/**
 * Delete an image by verifying ownership through namespace
 * This is for authenticated users only and does not use hash verification
 *
 * @param Upload $uploader The uploader instance
 * @param string $path The path to the image
 * @param int $userId The ID of the user requesting deletion
 * @return bool True if deletion was successful
 */
function delete_image(Upload $uploader, string $path, int $userId): bool {
    // Extract namespace from path (format: "userId/forumId/filename")
    $parts = explode('/', $path);
    if (count($parts) < 2) {
        error_log("Invalid path format: $path");
        return false;
    }

    // First part should be the user ID
    $pathUserId = (int)$parts[0];
    if ($pathUserId !== $userId) {
        error_log("Unauthorized: Image belongs to a different user: $pathUserId != $userId");
        return false;
    }

    return $uploader->delete($path);
}

/**
 * Delete an image using URL and hash verification
 * This is for API/unauthenticated use
 *
 * @param Upload $uploader The uploader instance
 * @param string $delete_url The deletion URL from the upload result
 * @return bool True if deletion was successful
 */
function delete_image_by_url(Upload $uploader, string $delete_url): bool {
    return $uploader->deleteByUrl($delete_url);
}

function show_images(Upload $uploader, array $forum, ForumUser $user, bool $skip_empty = false) {
    $showimages = new_yatt('showimages.yatt', $forum);
    $title = 'Your images in ' . $forum['name'];
    $showimages->set('TITLE', $title);

    $namespace = "{$forum['fid']}/{$user->aid}";
    $images = $uploader->readdir($namespace);
    if (empty($images)) {
        if ($skip_empty) {
            return null;
        }
        $showimages->parse('images_page.no_images');
    } else {
        foreach ($images as $img) {
            $md = $img['metadata'];
            $showimages->set('IMAGE_URL', htmlspecialchars($img['url']));
            $showimages->set('IMAGE_ORIGINAL_NAME', $md ? htmlspecialchars($md->original_name) : '');
            $showimages->set('IMAGE_UPLOAD_TIME', $md ? date('Y-m-d H:i:s', strtotime($md->upload_time)) : '');
            $showimages->set('IMAGE_FILE_SIZE', $md ? format_bytes($md->file_size) : '');

            // Set the link URL to the last message if available, otherwise use the image URL
            $link_url = $img['url'];
            if ($md && !empty($md->messages)) {
                $link_url = end($md->messages);
            }
            $showimages->set('IMAGE_LINK_URL', htmlspecialchars($link_url));

            // Set delete path - ensure it includes the full namespace (userId/forumId/filename)
            $showimages->set('DELETE_PATH', htmlspecialchars($img['path']));

            $showimages->parse('images_page.images_list.image');
        }
        $showimages->parse('images_page.images_list');
    }
    $showimages->parse('images_page');
    return $showimages;
}


// vim: set ts=8 sw=4 et:
