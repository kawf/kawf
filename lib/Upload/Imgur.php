<?php
namespace Kawf\Upload;

require_once(__DIR__ . '/Upload.php');

class Imgur extends Upload {
    private const IMGUR_API_URL = 'https://api.imgur.com/3/image';
    private const IMGUR_DELETE_URL = 'https://api.imgur.com/3/image/';

    public function isAvailable(): bool {
        return !empty($this->config['client_id']);
    }

    public function getMaxUploadSize(): int {
        return min(10 * 1024 * 1024, $this->getPhpUploadLimits());
    }

    protected function generateUniqueFilename(?string $namespace, string $original): ?string {
        return null; // Let Imgur handle filenames
    }

    /**
     * Perform the actual upload operation
     * @param string $filename Path to the file to upload
     * @param string $path The path where the file should be uploaded
     * @param ImageMetadata $metadata The metadata for the upload
     * @return bool True if the upload was successful
     */
    protected function doUpload(string $filename, string $path, ImageMetadata $metadata): bool {
        $curl_opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'image' => new \CURLFile($filename)
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: Client-ID ' . $this->config['client_id']
            ]
        ];

        $result = $this->makeCurlRequest('https://api.imgur.com/3/image', $curl_opts);

        if (!$result) {
            $this->error = "Failed to upload to Imgur, no result";
            return false;
        }

        if (isset($result['error'])) {
            $this->error = "Failed to upload to Imgur: " . $result['error'];
            return false;
        }

        $data = json_decode($result['response'], true);
        if (!$data || !isset($data['data']['link'])) {
            $this->error = "Invalid response from Imgur: " . $result['response'];
            return false;
        }

        // Set the image URL in metadata
        $metadata->image_url = $data['data']['link'];

        return true;
    }

    /**
     * Delete a file by its path
     * This method handles both full URLs and path fragments
     */
    public function delete(string $path): bool {
        // Extract the deletehash from the path
        $deletehash = $this->extractDeleteHash($path);
        if (!$deletehash) {
            $this->error = "Invalid path format for Imgur deletion";
            return false;
        }

        $curl_opts = [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'Authorization: Client-ID ' . $this->config['client_id']
            ]
        ];

        $result = $this->makeCurlRequest('https://api.imgur.com/3/image/' . $deletehash, $curl_opts);

        if (!$result) {
            $this->error = "Failed to delete from Imgur, no result";
            return false;
        }

        if (isset($result['error'])) {
            $this->error = "Failed to delete from Imgur: " . $result['error'];
            return false;
        }

        return true;
    }

    /**
     * Delete a file using a deletion URL
     * This method handles both full URLs and path fragments
     */
    public function deleteByUrl(string $deleteUrl): bool {
        // Handle both full URLs and path fragments
        if (strpos($deleteUrl, '?') !== false) {
            // Extract query string from URL if it's a full URL
            if (strpos($deleteUrl, '://') !== false) {
                $queryString = substr($deleteUrl, strpos($deleteUrl, '?') + 1);
            } else {
                $queryString = $deleteUrl;
            }
        } else {
            $queryString = $deleteUrl;
        }

        // Parse query parameters (parse_str handles URL decoding)
        parse_str($queryString, $query);

        // Extract parameters
        $path = $query['url'] ?? '';
        $hash = $query['hash'] ?? '';
        $timestamp = (int)($query['t'] ?? 0);

        if (empty($path)) {
            $this->setError("Missing URL parameter");
            return false;
        }

        // Verify hash before proceeding
        if (!$this->verifyDeleteHash($path, $hash, $timestamp)) {
            // Error message is already set by verifyDeleteHash
            return false;
        }

        return $this->delete($path);
    }

    /**
     * Extract the deletehash from an Imgur path
     * @param string $path The path to extract from
     * @return string|null The deletehash if found, null otherwise
     */
    private function extractDeleteHash(string $path): ?string {
        // Handle both full URLs and path fragments
        if (strpos($path, '://') !== false) {
            // Extract the last part of the URL
            $path = basename(parse_url($path, PHP_URL_PATH));
        }

        // Remove any file extension
        $path = pathinfo($path, PATHINFO_FILENAME);

        // The deletehash should be the last part of the path
        return $path;
    }

    public function supports_metadata(): bool {
        return false;
    }

    public function save_metadata(string $path, ImageMetadata $metadata): bool {
        throw new \RuntimeException("Metadata operations are not supported by Imgur uploader");
    }

    public function load_metadata(string $path): ?ImageMetadata {
        throw new \RuntimeException("Metadata operations are not supported by Imgur uploader");
    }

    /**
     * List images in a namespace (directory) and return info for each image
     * @param string $namespace Namespace or subdirectory (e.g. "f123")
     * @return array List of images with keys: url, original_name, upload_time, file_size
     */
    public function readdir(string $namespace): array {
        // Imgur doesn't support directory listing
        return [];
    }
}
// vim: set ts=8 sw=4 et:
