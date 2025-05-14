<?php
namespace Kawf\Upload;

abstract class Upload {
    protected $config;
    protected $error;

    public function __construct(array $config = []) {
        $this->config = $config;
    }

    /**
     * Get the last error message
     */
    public function getError(): ?string {
        return $this->error;
    }

    /**
     * Generate a unique filename for the upload
     * @param string|null $namespace Optional namespace (e.g. "fid/aid")
     * @param string $original Original filename
     * @return string|null The unique filename, or null if the uploader handles it
     */
    protected function generateUniqueFilename(?string $namespace, string $original): ?string {
        // Base implementation uses uniqid()
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        return uniqid() . '.' . $extension;
    }

    protected function return_bytes($val): int {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;

        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    protected function getPhpUploadLimits(): int {
        $upload_max_filesize = ini_get('upload_max_filesize');
        $post_max_size = ini_get('post_max_size');
        return min(
            $this->return_bytes($upload_max_filesize),
            $this->return_bytes($post_max_size)
        );
    }

    protected function validateFile(string $filename): bool {
        if (!file_exists($filename)) {
            $this->error = "$filename not found";
            return false;
        }
        return true;
    }

    protected function makeCurlRequest(string $url, array $options = []): ?array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        foreach ($options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code < 200 || $http_code >= 300) {
            $this->error = "HTTP request failed: $http_code";
            return null;
        }

        return [
            'response' => $response,
            'http_code' => $http_code
        ];
    }

    /**
     * Check if upload is available/configured
     */
    abstract public function isAvailable(): bool;

    /**
     * Get maximum upload size in bytes
     */
    abstract public function getMaxUploadSize(): int;

    /**
     * Upload a file and return the public URL
     *
     * @param string $filename Path to the file to upload
     * @param string|null $namespace Optional namespace for the upload (e.g. "fid/aid")
     * @param ImageMetadata|null $metadata Optional metadata for the upload
     * @return array|null Array containing 'url' and optionally 'delete_url', or null on failure
     */
    abstract public function upload(string $filename, ?string $namespace = null, ?ImageMetadata $metadata = null): ?array;

    /**
     * Generate a secure deletehash for an upload that can be verified without database storage
     *
     * @param string $path The path or identifier of the upload
     * @param int $userId The user ID of the uploader
     * @param int $timestamp When the hash was generated
     * @return string A secure hash that can be used for deletion
     */
    protected function generateDeleteHash(string $path, int $userId, int $timestamp): string {
        // Create a data string that includes all verification info
        $data = sprintf(
            "%s|%d|%d|%s",
            $path,
            $userId,
            $timestamp,
            $this->config['delete_salt'] ?? 'default_salt_change_me'
        );

        // Generate SHA-256 hash of the data
        return hash('sha256', $data);
    }

    /**
     * Verify a delete hash from a URL
     *
     * @param string $path The path from the URL
     * @param string $hash The hash from the URL
     * @param int $timestamp The timestamp from the URL
     * @param int $userId The current user's ID
     * @param int $maxAge Maximum age of the hash in seconds (default 24 hours)
     * @return bool True if the hash is valid and not expired
     */
    public function verifyDeleteHash(string $path, string $hash, int $timestamp, int $userId, int $maxAge = 86400): bool {
        // Check if the hash has expired
        if (time() - $timestamp > $maxAge) {
            return false;
        }

        // Generate the expected hash
        $expectedHash = $this->generateDeleteHash($path, $userId, $timestamp);

        // Compare hashes using hash_equals for timing attack prevention
        return hash_equals($expectedHash, $hash);
    }

    /**
     * List images in a namespace (directory) and return info for each image
     * @param string $namespace Namespace or subdirectory (e.g. "f123")
     * @return array List of images (uploader-specific structure)
     */
    abstract public function readdir(string $namespace): array;
}

class ImageMetadata {
    public string $original_name;
    public string $resized_name;
    public string $upload_time;
    public string $mime_type;
    public int $file_size;
    public int $user_id;
    public string $image_url;
    public bool $was_resized;
    public array $messages;

    public function __construct(
        string $original_name,
        string $resized_name,
        string $mime_type,
        int $file_size,
        int $user_id,
        string $image_url = '',
        bool $was_resized = false,
        array $messages = []
    ) {
        $this->original_name = $original_name;
        $this->resized_name = $resized_name;
        $this->upload_time = gmdate('c');  // ISO 8601
        $this->mime_type = $mime_type;
        $this->file_size = $file_size;
        $this->user_id = $user_id;
        $this->image_url = $image_url;
        $this->was_resized = $was_resized;
        $this->messages = $messages;
    }

    /**
     * Create metadata from a file and its metadata
     *
     * @param string $filepath Path to the file
     * @param array $metadata File metadata containing original name, resized name, etc.
     * @param int $user_id User ID of the uploader
     * @return self
     */
    public static function createMetadata(string $filepath, array $metadata, int $user_id): self {
        return new self(
            $metadata['original'],
            $metadata['resized'],
            mime_content_type($filepath),
            filesize($filepath),
            $user_id,
            '', // Will be set by uploader
            $metadata['wasResized'] ?? false
        );
    }

    /**
     * Create metadata from a file and original filename
     *
     * @param string $filepath Path to the file
     * @param string|null $resized_filename Optional filename of the resized image, if any
     * @param int|0 $user_id Optional User ID of the uploader
     * @param string|null $image_url Optional image URL
     * @return self
     */
    public static function fromFilename(string $filepath, ?string $resized_filename = null, int $user_id = 0, string $image_url = ''): self {
        if (!$resized_filename) {
            $resized_filename = $filepath;
        }
        return new self(
            $filepath,
            $resized_filename,
            mime_content_type($filepath),
            filesize($filepath),
            $user_id,
            $image_url,
            false
        );
    }

    public function toArray(): array {
        return [
            'original_name' => $this->original_name,
            'resized_name' => $this->resized_name,
            'upload_time' => $this->upload_time,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'user_id' => $this->user_id,
            'image_url' => $this->image_url,
            'was_resized' => $this->was_resized,
            'messages' => $this->messages
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['original_name'],
            $data['resized_name'],
            $data['mime_type'],
            $data['file_size'],
            $data['user_id'],
            $data['image_url'],
            $data['was_resized'],
            $data['messages'] ?? []
        );
    }
}

interface ImageUploader {
    // ... existing methods ...

    // Add metadata support methods
    public function supports_metadata(): bool;
    public function save_metadata(string $path, ImageMetadata $metadata): bool;
    public function load_metadata(string $path): ?ImageMetadata;
}
// vim: set ts=8 sw=4 et:
