<?php
namespace Kawf\Upload;

require_once(__DIR__ . '/Upload.php');

class DAV extends Upload {
    public function isAvailable(): bool {
        return !empty($this->config['url']) &&
               !empty($this->config['username']) &&
               !empty($this->config['password']);
    }

    public function getMaxUploadSize(): int {
        return $this->getPhpUploadLimits();
    }

    protected function generateUniqueFilename(?string $namespace, string $original): ?string {
        if ($namespace) {
            return $namespace . '/' . $original;
        }
        return parent::generateUniqueFilename($namespace, $original);
    }

    protected function ensureRemoteDirectories($remote_path) {
        // Remove leading/trailing slashes and split into parts
        $path = trim($remote_path, '/');
        $parts = explode('/', $path);
        if (count($parts) <= 1) return true; // No directory to create
        $base_url = rtrim($this->config['url'], '/');
        $current = '';
        $created = false;
        // Create each directory in the path except the last (the file)
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $current .= '/' . $parts[$i];
            $url = $base_url . $current;
            // Check if directory exists (HEAD request)
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->config['username'] . ':' . $this->config['password']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code == 200 || $http_code == 301 || $http_code == 302) {
                continue; // Directory exists
            }
            // Try to create directory (MKCOL)
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'MKCOL');
            curl_setopt($ch, CURLOPT_USERPWD, $this->config['username'] . ':' . $this->config['password']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $mkcol_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($mkcol_code != 201 && $mkcol_code != 405) { // 201 Created, 405 Method Not Allowed (already exists)
                $this->setError("Failed to create directory: $url (HTTP $mkcol_code)");
                return false;
            }
            $created = true;
        }
        return true;
    }

    /**
     * Private utility method to perform the actual deletion
     * This method assumes the path is already in the correct format
     */
    private function performDelete(string $remote_path): bool {
        $url = rtrim($this->config['url'], '/') . '/' . $remote_path;
        // Delete the main file
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);

        if (isset($result['error'])) {
            $this->setError("Failed to delete file '$remote_path': " . $result['error']);
            return false;
        }

        // Also delete the metadata file if it exists
        $metadata_url = $url . '.json';
        $this->makeCurlRequest($metadata_url, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);
        return true;
    }

    /**
     * Delete a file by its path
     * This method always prepends the config path
     */
    public function delete(string $path): bool {
        $remote_path = $this->config['path'] . '/' . $path;
        return $this->performDelete($remote_path);
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

        // For URL-based deletion, we need to prepend the config path
        $remote_path = $this->config['path'] . '/' . $path;
        return $this->performDelete($remote_path);
    }

    public function supports_metadata(): bool {
        return true;
    }

    public function save_metadata(string $path, ImageMetadata $metadata): bool {
        $metadata_path = $this->get_metadata_path($path);
        $data = $metadata->toArray();
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";

        $url = rtrim($this->config['url'], '/') . '/' . $metadata_path;
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        if (!$result) {
            $this->setError("Failed to save metadata for $path: " . $this->getError());
            return false;
        }
        return true;
    }

    public function load_metadata(string $path): ?ImageMetadata {
        $metadata_path = $this->get_metadata_path($path);
        $url = rtrim($this->config['url'], '/') . '/' . $metadata_path;

        $result = $this->makeCurlRequest($url, [
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);

        if (!$result) {
            $this->setError("Failed to load metadata for $path (URL: $url): " . $this->getError());
            return null;
        }
        if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            $this->setError("Non-2xx response for $path: " . $result['response']);
            return null;
        }

        $data = json_decode($result['response'], true);
        if (!$data) {
            $this->setError("Failed to decode metadata JSON for $path. Response: " . $result['response']);
            return null;
        }

        return ImageMetadata::fromArray($data);
    }

    private function get_metadata_path(string $path): string {
        return $path . '.json';
    }

    public function upload(string $filename, ?string $namespace = null, ?ImageMetadata $metadata = null): ?array {
        if (!$this->isAvailable() || !$this->validateFile($filename)) {
            return null;
        }

        // Use the provided metadata or create new metadata
        if (!$metadata) {
            $metadata = ImageMetadata::fromFilename($filename);
        }

        // Generate path using namespace and original filename
        $path = $this->generateUniqueFilename($namespace, $metadata->original_name);
        $remote_path = $this->config['path'] . '/' . $path;

        // Ensure all parent directories exist
        if (!$this->ensureRemoteDirectories($remote_path)) {
            $this->error = "Failed to ensure remote directories for $remote_path";
            return null;
        }

        // Upload the image file
        $curl_path = $this->config['url'] . '/' . $remote_path;
        $curl_opts =[
            CURLOPT_PUT => true,
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_INFILE => fopen($filename, 'r'),
            CURLOPT_INFILESIZE => filesize($filename)
        ];
        $result = $this->makeCurlRequest($curl_path, $curl_opts);

        if (!$result) {
            $this->error = "Failed to upload to DAV, no result";
            return null;
        }

        // Set the image URL in metadata
        $metadata->image_url = $path;

        if (!$this->save_metadata($remote_path, $metadata)) {
            $this->error = "Failed to save metadata";
            return null;
        }

        // Create a structured delete URL that can be used in changes
        $timestamp = time();
        $deletehash = $this->generateDeleteHash($path, $timestamp);

        // Return relative path for deletion - forum software will prepend its base URL
        $delete_url = 'deleteimage.phtml?url=' . urlencode($path) . '&hash=' . $deletehash . '&t=' . $timestamp;

        return [
            'url' => rtrim($this->config['public_url'], '/') . '/' . $remote_path,
            'delete_url' => $delete_url,
            'metadata_url' => $remote_path
        ];
    }

    /**
     * List images in a namespace (directory) and return info for each image
     * @param string $namespace Namespace or subdirectory (e.g. "f123")
     * @return array List of images with keys: url, original_name, upload_time, file_size
     */
    public function readdir(string $namespace): array {
        $images = [];
        $base_url = rtrim($this->config['public_url'], '/');
        $path = rtrim($this->config['path'] . '/' . $namespace, '/') . '/';
        $url = rtrim($this->config['url'], '/') . '/' . $path;
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_HTTPHEADER => ['Depth: 1']
        ]);
        if (isset($result['response'])) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($result['response']);
            if ($xml === false) {
                error_log("[DAV::readdir] Failed to parse XML response");
                foreach (libxml_get_errors() as $error) {
                    error_log("[DAV::readdir] XML error: " . $error->message);
                }
                libxml_clear_errors();
            } else {
                $xml->registerXPathNamespace('d', 'DAV:');
                $prefix = parse_url($url, PHP_URL_PATH);
                $hrefs = $xml->xpath('//d:response/d:href');
                foreach ($hrefs as $hrefObj) {
                    $href = (string)$hrefObj;
                    if (strpos($href, $prefix) === 0) {
                        $img_path = urldecode(substr($href, strlen($prefix)));
                    } else {
                        $img_path = urldecode($href);
                    }
                    // Skip directories and metadata files
                    if (strpos($img_path, '/') !== false || strpos($img_path, '.json') !== false || $img_path === '') {
                        continue;
                    }
                    // Get metadata if available, using the full relative path
                    $metadata = null;
                    if ($this->supports_metadata()) {
                        $metadata = $this->load_metadata($path . $img_path);
                    }
                    $images[] = [
                        'img' => $img_path,
                        'path' => $namespace . '/' . $img_path,
                        'url' => $base_url . '/' . $path . $img_path,
                        'metadata' => $metadata
                    ];
                }
            }
        }
        // Sort by upload_time (newest first)
        usort($images, function($a, $b) {
            $md_a = $a['metadata'];
            $md_b = $b['metadata'];
            return strtotime($md_b->upload_time) - strtotime($md_a->upload_time);
        });
        return $images;
    }
}
// vim: set ts=8 sw=4 et:
