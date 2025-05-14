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
            $this->setError("Failed to upload to Imgur: " . $this->getError());
            return null;
        }

        $data = json_decode($result['response'], true);
        if (!isset($data['data']['link'])) {
            $this->setError("Invalid response from Imgur: " . ($data['data']['error'] ?? 'Unknown error'));
            return null;
        }

        // Create metadata if not provided
        if (!$metadata) {
            $metadata = ImageMetadata::fromFilename($filename);
        }
        $metadata->image_url = $data['data']['link'];

        return [
            'url' => $data['data']['link'],
            'delete_url' => self::IMGUR_DELETE_URL . $data['data']['deletehash'],
            'metadata_url' => null  // Imgur doesn't support metadata
        ];
    }

    public function supports_metadata(): bool {
        return false;
    }

    public function save_metadata(string $path, ImageMetadata $metadata): bool {
        throw new \RuntimeException("Imgur does not support metadata storage");
    }

    public function load_metadata(string $path): ?ImageMetadata {
        throw new \RuntimeException("Imgur does not support metadata retrieval");
    }

    public function delete(string $path): bool {
        $this->setError("Imgur does not support path-based deletion");
        return false;
    }

    public function deleteByUrl(string $deleteUrl): bool {
        if (!$this->isAvailable()) {
            return false;
        }

        // Handle both full URLs and path fragments
        if (strpos($deleteUrl, '?') !== false) {
            // Extract query string from URL if it's a full URL
            if (strpos($deleteUrl, '://') !== false) {
                $queryString = substr($deleteUrl, strpos($deleteUrl, '?') + 1);
            } else {
                $queryString = $deleteUrl;
            }
            parse_str($queryString, $query);
            $imgurUrl = $query['url'] ?? '';
        } else {
            $imgurUrl = $deleteUrl;
        }

        // Extract deletehash from Imgur URL
        if (strpos($imgurUrl, 'imgur.com') !== false) {
            $deletehash = basename($imgurUrl);
        } else {
            $deletehash = $imgurUrl;
        }

        if (empty($deletehash)) {
            $this->setError("Invalid Imgur delete URL");
            return false;
        }

        $url = self::IMGUR_DELETE_URL . $deletehash;
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Client-ID ' . $this->config['client_id']]
        ]);

        if (!$result) {
            $this->setError("Failed to delete image from Imgur: " . $this->getError());
            return false;
        }

        $data = json_decode($result['response'], true);
        if (!isset($data['success']) || !$data['success']) {
            $this->setError("Imgur deletion failed: " . ($data['data']['error'] ?? 'Unknown error'));
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
