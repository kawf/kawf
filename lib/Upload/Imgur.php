<?php
namespace Kawf\Upload;

require_once(__DIR__ . '/Upload.php');

class Imgur extends Upload {
    private const IMGUR_API_URL = 'https://api.imgur.com/3/image';
    private const IMGUR_DELETE_URL = 'https://api.imgur.com/3/image/';

    public function isAvailable(): bool {
        if (empty($this->config['client_id'])) {
            $this->error = "client_id not set";
            return false;
        }
        return true;
    }

    public function getMaxUploadSize(): int {
        return min(10 * 1024 * 1024, $this->getPhpUploadLimits());
    }

    protected function generateUniqueFilename(?string $namespace, string $original): ?string {
        return null; // Let Imgur handle filenames
    }

    public function upload(string $filename, ?string $namespace = null, ?ImageMetadata $metadata = null): ?array {
        if (!$this->isAvailable() || !$this->validateFile($filename)) {
            return null;
        }

        $result = $this->makeCurlRequest(self::IMGUR_API_URL, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Client-ID ' . $this->config['client_id']],
            CURLOPT_POSTFIELDS => ['image' => file_get_contents($filename)]
        ]);

        if (!$result) {
            // IMGUR is just refusing everything right now.
            $this->error = "Try reducing file size";
            return null;
        }

        $data = json_decode($result['response'], true);
        if (!isset($data['data']['link'])) {
            $this->error = 'Invalid response from Imgur';
            return null;
        }

        // Create metadata if not provided
        if (!$metadata) {
            $metadata = ImageMetadata::fromFilename($filename);
        }
        $metadata->image_url = $data['data']['link'];

        return [
            'url' => $data['data']['link'],
            'delete_url' => self::IMGUR_DELETE_URL . $data['data']['deletehash']
        ];
    }

    public function supports_metadata(): bool {
        return false;
    }

    public function save_metadata(string $path, ImageMetadata $metadata): bool {
        return false;
    }

    public function load_metadata(string $path): ?ImageMetadata {
        return null;
    }

    public function delete(string $deletehash): bool {
        if (!$this->isAvailable()) {
            return false;
        }

        $url = self::IMGUR_DELETE_URL . $deletehash;
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Client-ID ' . $this->config['client_id']]
        ]);

        if (!$result) {
            $this->error = "Failed to delete image from Imgur";
            return false;
        }

        $data = json_decode($result['response'], true);
        if (!isset($data['success']) || !$data['success']) {
            $this->error = 'Imgur deletion failed: ' . ($data['data']['error'] ?? 'Unknown error');
            return false;
        }

        return true;
    }

    /**
     * Imgur does not support directory listing; always returns empty array
     * @param string $namespace
     * @return array
     */
    public function readdir(string $namespace): array {
        return [];
    }
}
// vim: set ts=8 sw=4 et:
