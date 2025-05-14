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

## Path and Namespace Conventions

- **Namespace**: A logical grouping, often used as a directory prefix (e.g., `forumid/userid` or `wayot/1/1`). Used to organize files on the server.
- **Path**: The full relative path to a file, including the namespace and filename (e.g., `wayot/1/1/0430-175430.jpg`).
- **Metadata Path**: The full relative path to the metadata file, which is the image path with `.json` appended (e.g., `wayot/1/1/0430-175430.jpg.json`).

**Best Practice:**
- Always pass the full relative path (namespace + filename) to all uploader methods that operate on files or metadata (e.g., `load_metadata`, `save_metadata`, `delete`).
- The uploader will handle appending `.json` for metadata and constructing the full URL as needed.

**Example:**
- Namespace: `wayot/1/1`
- Filename: `0430-175430.jpg`
- Full path: `wayot/1/1/0430-175430.jpg`
- Metadata path: `wayot/1/1/0430-175430.jpg.json`

## Image Deletion

- **Authenticated User Deletion:**
  - When a user is authenticated via the forum, image deletion can bypass hash checking and permission is granted based on session/user context.
  - This allows for a more seamless user experience in the image browser.
  - The path format must be `userId/forumId/filename` for proper namespace verification.
  - The delete endpoint is `/<forum>/deleteimage.phtml` and expects a POST request with JSON data containing the path.

- **External API Deletion (`deleteimage.phtml`):**
  - The `deleteimage.phtml` endpoint is intended for external API usage, where hash checking and other security measures are enforced.
  - This endpoint is suitable for deletion links sent via email, bots, or other non-authenticated contexts.
  - The delete URL format is `deleteimage.phtml?url=path&hash=xxx&t=yyy`.

- **Direct Deletion from Image Browser:**
  - If the user is authenticated and the image browser provides a "delete" URL for an image, the browser can call the uploader's `delete` method directly, bypassing hash checks.
  - This should only be allowed for users with proper permissions (e.g., image owner, moderator).
  - The JavaScript function `deleteImage(forum, path, imageName)` handles the deletion with proper confirmation and error handling.

- **User Confirmation and Clickjacking Protection:**
  - To prevent accidental or malicious deletions (e.g., someone tricking a user into clicking an `images?delete=img` link), always require explicit user confirmation (e.g., a JavaScript `confirm('Are you sure?')` dialog) before performing the delete action in the browser.
  - Consider additional CSRF protection if using GET/POST requests for deletion.

- **Summary:**
  - Use direct delete for authenticated users in the browser (with confirmation).
  - Use `deleteimage.phtml` for external/API deletion (with hash checking).
  - Always protect users from accidental or tricked deletions.
  - Maintain proper namespace verification for all deletion paths.
