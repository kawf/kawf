# Image Upload Library

## Overview

The Image Upload Library provides a unified interface for handling image uploads across different backends (WebDAV, Imgur). It handles file uploads, metadata management, and secure deletion URLs.

## Design Goals

1. **Abstraction**
   - Support multiple upload backends (DAV, Imgur)
   - Common interface for all uploaders
   - Easy to add new upload backends

2. **Filename Handling**
   - Prevent filename collisions
   - Support namespacing (e.g. by forum ID and user ID)
   - Preserve original filenames where possible
   - Allow overwrite option

3. **Image Resizing**
   - Client-side resizing before upload
   - Configurable dimensions (640px, 1024px, 1920px)
   - Maintain aspect ratio
   - Show original and resized file sizes

4. **Error Handling**
   - Consistent error reporting
   - File validation
   - Size limit checks
   - HTTP error handling

5. **Security**
   - Access control for uploaded images
   - Future: Proxy all image requests through forum server
   - Prevent unauthorized access to image server
   - Secure image deletion with signed URLs
   - Hash-based deletion tokens with secret salt

## URL Handling

The library handles URLs differently based on the backend:

### Imgur
- `url`: Full URL to the uploaded image (e.g., `https://i.imgur.com/abc123.jpg`)
- `delete_url`: Full URL to Imgur's deletion API (e.g., `https://api.imgur.com/3/image/abc123`)
- `metadata_url`: Full URL to the image (Imgur doesn't support metadata)

### WebDAV
- `url`: Full URL to the uploaded image (e.g., `https://images.example.com/path/to/image.jpg`)
- `delete_url`: Query parameter format for deletion (e.g., `deleteimage.phtml?url=path/to/image&hash=xxx&t=yyy`)
- `metadata_url`: Relative path to metadata file (e.g., `path/to/image.jpg.json`)

The forum software is responsible for:
1. Converting relative delete URLs to absolute URLs
2. Handling deletion requests through `deleteimage.php`
3. Parsing query parameters and passing raw values to uploaders

## Deletion Security

### Hash Generation
The base Upload class provides secure deletion hashes that can be verified without database storage:

```php
$hash = $uploader->generateDeleteHash($path, $userId, $timestamp);
```

The hash includes:
- File path
- User ID
- Timestamp
- Secret salt
- SHA-256 hash

### Hash Verification
```php
$isValid = $uploader->verifyDeleteHash($path, $hash, $timestamp, $userId);
```

Verification checks:
1. Hash hasn't expired (default 24 hours)
2. Hash matches expected value
3. User has permission to delete

### Deletion Flow
1. Forum generates delete URL with query parameters
2. User clicks delete URL
3. `deleteimage.php` parses query parameters
4. Uploader verifies hash and performs deletion
5. Uploader has no knowledge of URLs, only handles raw paths

## Configuration

### WebDAV
```php
$config = [
    'url' => 'https://dav.example.com',  // WebDAV server URL
    'username' => 'user',
    'password' => 'pass',
    'path' => 'uploads',                 // Base path for uploads
    'public_url' => 'https://images.example.com',  // Public URL for images
    'delete_salt' => 'your-secret-salt'  // For hash generation and verification
];
```

### Imgur
```php
$config = [
    'client_id' => 'your-client-id'
];
```

## Usage Example

```php
// Create uploader
$uploader = new DAV($config);

// Upload file
$result = $uploader->upload($filename, $namespace, $metadata);

if ($result) {
    $imageUrl = $result['url'];          // Full URL to view image
    $deleteUrl = $result['delete_url'];  // Query parameter format for deletion
    $metadataUrl = $result['metadata_url']; // Path to metadata
}
```

## Error Handling

All uploaders provide consistent error handling:
```php
if (!$result) {
    $error = $uploader->getError();
    // Handle error
}
```

## Future Improvements

1. **Directory Creation**
   - Add MKCOL support for WebDAV
   - Check server support
   - Handle creation failures

2. **Collision Prevention**
   - Check for existing files
   - Add overwrite option
   - Handle collision errors

3. **User Preferences**
   - Save preferred image size
   - Remember last used namespace
   - Custom quality settings

4. **Error Recovery**
   - Retry failed uploads
   - Resume interrupted uploads
   - Better error messages

5. **Access Control**
   - Implement image request proxy through forum server
   - Add authentication checks for image access
   - Cache proxied images for performance
   - Support private images visible only to specific users/forums

## Notes

- WebDAV directory creation support varies by server
- Imgur has a 10MB file size limit
- Client-side resizing reduces server load
- Namespacing helps organize uploads (DAV only)
- Original filenames are preserved for DAV but not Imgur
- Deletion security requires:
  - Secret salt for hash generation
  - URL signing mechanism
  - Token expiration handling
  - Permission validation
  - Clear separation between URL handling and file operations
