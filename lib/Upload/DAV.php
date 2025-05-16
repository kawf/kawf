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

    /**
     * Construct a URL with proper path encoding
     *
     * Examples:
     * Input: base_url="http://localhost:8080", path="/1", config[path]="path"
     * Output: "http://localhost:8080/path/1"
     *
     * Input: base_url="http://localhost:8080", path="1/1/foo.png", config[path]="path"
     * Output: "http://localhost:8080/path/1/1/foo.png"
     *
     * Input: base_url="https://images.path.org", path="1/1/foo.png", config[path]="path"
     * Output: "https://images.path.org/path/1/1/foo.png"
     *
     * @param string $base_url The base URL to prepend
     * @param string $path The path to encode and append
     * @return string The complete URL with encoded path
     */
    protected function getUrl(string $base_url, string $path): string {
        // Normalize path by removing leading/trailing slashes
        // Example: "/1" -> "1", "1/1/foo.png" -> "1/1/foo.png"
        $path = trim($path, '/');

        // Split path into segments and encode each one
        // Example: "1/1/foo.png" -> ["1", "1", "foo.png"] -> ["1", "1", "foo%bar.png"]
        $segments = explode('/', $path);
        $encoded_segments = array_map('rawurlencode', $segments);

        // Prepend config path if it exists
        // Example: config[path]="path" -> ["path"] -> ["path", "1", "1", "foo%bar.png"]
        if (!empty($this->config['path'])) {
            $config_segments = explode('/', $this->config['path']);
            $encoded_config = array_map('rawurlencode', $config_segments);
            $encoded_segments = array_merge($encoded_config, $encoded_segments);
        }

        // Join everything together with slashes
        // Example: "http://localhost:8080" + "/" + "path/1/1/foo%bar.png"
        $result = rtrim($base_url, '/') . '/' . implode('/', $encoded_segments);
        return $result;
    }

    /**
     * Ensure all parent directories exist for a path
     * @param string $path The path to ensure directories for
     * Example: "1/1/foo bar.png" -> creates directories "1" and "1/1"
     */
    protected function ensureRemoteDirectories($path) {
        // Remove leading/trailing slashes and split into parts
        $path = trim($path, '/');
        $parts = explode('/', $path);
        if (count($parts) <= 1) return true; // No directory to create
        $base_url = rtrim($this->config['url'], '/');
        $current = '';
        $created = false;
        // Create each directory in the path except the last (the file)
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $current .= '/' . $parts[$i];
            $url = $this->getUrl($this->config['url'], $current);
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
     * Perform the actual deletion
     * This method assumes the path is already in the correct format (e.g. already includes config['path'])
     */
    public function delete(string $path): bool {
        $url = $this->getUrl($this->config['url'], $path);
        // Delete the main file
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);

        if (isset($result['error'])) {
            $this->setError("Failed to delete file '$path': " . $result['error']);
            return false;
        }

        // Also delete the metadata file if it exists
        $metadata_path = $url . '.json';
        $this->makeCurlRequest($metadata_path, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);
        return true;
    }

    /**
     * Delete a file using a deletion URL
     * @param string $deleteUrl The deletion URL or path
     * Example: "deleteimage.phtml?url=1/1/foo%20bar.png&hash=abc&t=123"
     *         -> deletes "/path/1/1/foo bar.png"
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
        return $this->delete($remote_path);
    }

    public function supports_metadata(): bool {
        return true;
    }

    /**
     * Save metadata for an uploaded file
     * @param string $path The path of the file
     * Example: "1/1/foo bar.png" -> saves to "/path/1/1/foo bar.png.json"
     */
    public function save_metadata(string $path, ImageMetadata $metadata): bool {
        $metadata_path = $this->get_metadata_path($path);
        $data = $metadata->toArray();
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";

        $url = $this->getUrl($this->config['url'], $metadata_path);
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        if (isset($result['error'])) {
            $this->setError("Failed to save metadata: " . $result['error']);
            return false;
        }

        if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            $this->setError("WebDAV metadata save failed with status " . $result['http_code'] . ": " . $result['response']);
            return false;
        }

        return true;
    }

    /**
     * Load metadata for an uploaded file
     * @param string $path The path of the file
     * Example: "1/1/foo bar.png" -> loads from "/path/1/1/foo bar.png.json"
     */
    public function load_metadata(string $path): ?ImageMetadata {
        $metadata_path = $this->get_metadata_path($path);
        $url = $this->getUrl($this->config['url'], $metadata_path);

        $result = $this->makeCurlRequest($url, [
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);

        if (isset($result['error'])) {
            $this->setError("Failed to load metadata: " . $result['error']);
            return null;
        }

        if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            $this->setError("WebDAV metadata load failed with status " . $result['http_code'] . ": " . $result['response']);
            return null;
        }

        $data = json_decode($result['response'], true);
        if (!$data) {
            $this->setError("Failed to decode metadata JSON for $path. Response: " . $result['response']);
            return null;
        }

        return ImageMetadata::fromArray($data);
    }

    /**
     * Get the metadata path for a file
     * @param string $path The path of the file
     * Example: "1/1/foo bar.png" -> returns "1/1/foo bar.png.json"
     */
    private function get_metadata_path(string $path): string {
        return $path . '.json';
    }

    /**
     * Perform the actual upload operation
     * @param string $filename Path to the file to upload
     * @param string $path The path where the file should be uploaded
     * Example: $path="1/1/foo bar.png" -> uploads to "/path/1/1/foo bar.png"
     * @param ImageMetadata $metadata The metadata for the upload
     * @return string|null The path of the uploaded file, or null if the upload failed
     */
    public function doUpload(string $filename, string $path, ImageMetadata $metadata): ?string {
        // Ensure all parent directories exist
        if (!$this->ensureRemoteDirectories($path)) {
            $this->setError("Failed to ensure remote directories for $path");
            return null;
        }

        // Upload the image file
        $curl_path = $this->getUrl($this->config['url'], $path);
        $curl_opts =[
            CURLOPT_PUT => true,
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_INFILE => fopen($filename, 'r'),
            CURLOPT_INFILESIZE => filesize($filename)
        ];
        $result = $this->makeCurlRequest($curl_path, $curl_opts);

        if (isset($result['error'])) {
            $this->setError("Failed to upload to DAV: " . $result['error']);
            return null;
        }

        if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            $this->setError("WebDAV upload failed with status " . $result['http_code'] . ": " . $result['response']);
            return null;
        }

        // Set the image URL in metadata
        $metadata->image_url = $path;

        if (!$this->save_metadata($path, $metadata)) {
            $this->setError("Failed to save metadata");
            return null;
        }

        return $this->getUrl($this->config['public_url'], $path);
    }

    /**
     * List images in a namespace (directory) and return info for each image
     * @param string $namespace Namespace or subdirectory (e.g. "f123")
     * @return array List of images with keys: url, original_name, upload_time, file_size
     */
    public function readdir(string $namespace): array {
        $images = [];
        $base_url = rtrim($this->config['public_url'], '/');
        // Don't manually add config path, let getUrl handle it
        $path = rtrim($namespace, '/') . '/';
        // PROPFIND requires a trailing slash!
        $url = rtrim($this->getUrl($this->config['url'], $path), '/') . '/';

        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password'],
            CURLOPT_HTTPHEADER => ['Depth: 1']
        ]);

        // 404 is expected for directories that have not been created yet, other errors are not
        if ($result['http_code'] === 404) {
            return []; // Directory doesn't exist yet, return empty list
        }

        // Now check for other CURL errors
        if (isset($result['error'])) {
            $this->setError("PROPFIND $url failed: " . $result['error']);
            return [];
        }

        // Check for other non-200 response codes
        if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            $this->setError("PROPFIND $url failed with status " . $result['http_code'] . ": " . $result['response']);
            return [];
        }

        if (isset($result['response'])) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($result['response']);
            if ($xml === false) {
                $this->setError("Failed to parse PROPFIND $url response");
                foreach (libxml_get_errors() as $error) {
                    error_log("[DAV::readdir] XML error: " . $error->message);
                }
                libxml_clear_errors();
                return [];
            }
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
                    // Don't add config path here, let getUrl handle it
                    $metadata = $this->load_metadata($path . $img_path);
                }
                $images[] = [
                    'img' => $img_path,
                    'path' => $namespace . '/' . $img_path,
                    'url' => $this->getUrl($this->config['public_url'], $path . $img_path),
                    'metadata' => $metadata
                ];
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
