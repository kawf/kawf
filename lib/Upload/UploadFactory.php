<?php
namespace Kawf\Upload;

require_once(__DIR__ . '/DAV.php');
require_once(__DIR__ . '/Imgur.php');

class UploadFactory {
    /**
     * Create an uploader instance based on the configured service
     *
     * @param array $config The upload configuration
     * @return Upload|null The uploader instance or null if no service is configured
     */
    public static function create(array $config): ?Upload {
        // Check DAV first since it's the primary service
        if ($config['dav']['enabled']) {
            return new DAV($config['dav']);
        }

        // Fall back to Imgur if configured
        if ($config['imgur']['enabled']) {
            return new Imgur($config['imgur']);
        }

        error_log("create: No upload service configured: " . print_r($config, true));
        return null;
    }

    /**
     * Get the maximum upload size across all configured services
     *
     * @param array $config The upload configuration
     * @return int Maximum upload size in bytes
     */
    public static function getMaxUploadSize(array $config): int {
        $max_size = PHP_INT_MAX;

        if ($config['dav']['enabled']) {
            $dav = new DAV($config);
            return min($max_size, $dav->getMaxUploadSize());
        }

        if ($config['imgur']['enabled']) {
            $imgur = new Imgur($config);
            return min($max_size, $imgur->getMaxUploadSize());
        }

        error_log("getMaxUploadSize: No upload service configured: " . print_r($config, true));
        return $max_size;
    }
}
// vim: set ts=8 sw=4 et:
