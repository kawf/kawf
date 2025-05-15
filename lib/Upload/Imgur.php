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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Client-ID ' . $this->config['client_id']
        ]);

        $post = [
            'image' => base64_encode(file_get_contents($filename)),
            'name' => basename($path),
            'title' => basename($path),
            'description' => 'Uploaded via Kawf'
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);

        if ($response === false) {
            $this->setError("CURL error ($curl_errno): $curl_error");
            return false;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['data']['link'])) {
            $this->setError("Failed to upload to Imgur: " . ($data['data']['error'] ?? 'Unknown error'));
            return false;
        }

        if ($http_code < 200 || $http_code >= 300) {
            $this->setError("Imgur upload failed with status $http_code: " . ($data['data']['error'] ?? 'Unknown error'));
            return false;
        }

        // Set the image URL in metadata
        $metadata->image_url = $data['data']['link'];

        if (!$this->save_metadata($path, $metadata)) {
            $this->setError("Failed to save metadata");
            return false;
        }

        return true;
    }

    /**
     * Delete a file by its path
     * This method handles both full URLs and path fragments
     */
    public function delete(string $path): bool {
        $this->setError("Imgur does not support deletion");
        return false;
    }

    /**
     * Delete a file using a deletion URL
     * This method handles both full URLs and path fragments
     */
    public function deleteByUrl(string $deleteUrl): bool {
        $this->setError("Imgur does not support deletion");
        return false;
    }

    /**
     * List images in a namespace (directory) and return info for each image
     * @param string $namespace Namespace or subdirectory (e.g. "f123")
     * @return array List of images with keys: url, original_name, upload_time, file_size
     */
    public function readdir(string $namespace): array {
        $this->setError("Imgur does not support directory listing");
        return [];
    }

    public function supports_metadata(): bool {
        return false;
    }

    public function save_metadata(string $path, ImageMetadata $metadata): bool {
        $this->setError("Imgur does not support metadata");
        return false;
    }

    public function load_metadata(string $path): ?ImageMetadata {
        $this->setError("Imgur does not support metadata");
        return null;
    }
}
// vim: set ts=8 sw=4 et:
