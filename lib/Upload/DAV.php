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
                $this->error = "Failed to create directory: $url (HTTP $mkcol_code)";
                return false;
            }
            $created = true;
        }
        return true;
    }

    public function delete(string $path): bool {
        if (!$this->isAvailable()) {
            return false;
        }

        $remote_path = $this->config['path'] . '/' . $path;
        $url = rtrim($this->config['url'], '/') . '/' . $remote_path;

        // Delete the main file
        $result = $this->makeCurlRequest($url, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_USERPWD => $this->config['username'] . ':' . $this->config['password']
        ]);

        if (!$result) {
            $this->error = "Failed to delete file from DAV";
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
            error_log("Failed to save metadata for $path");
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
            error_log("Failed to load metadata for $path");
            return null;
        }

        $data = json_decode($result, true);
        if (!$data) {
            error_log("Failed to decode metadata JSON for $path");
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

        return [
            'url' => rtrim($this->config['public_url'], '/') . '/' . $remote_path,
            'delete_url' => $path, // temporary, will be replaced with a signed URL
            'metadata_url' => $remote_path
        ];
    }
}
// vim: set ts=8 sw=4 et:
