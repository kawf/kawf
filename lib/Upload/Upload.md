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

3. **Image Resizing**
   - Client-side resizing before upload
   - Configurable dimensions (640px, 1024px, 1920px)
   - Maintain aspect ratio

4. **Error Handling**
   - Consistent error reporting
   - File validation
   - Size limit checks

5. **Security**
   - Access control for uploaded images
   - Secure image deletion with signed URLs
   - Hash-based deletion tokens with secret salt

## URL Handling

### Imgur
- `url`: Full URL to the uploaded image (e.g., `https://i.imgur.com/abc123.jpg`)
- `delete_url`: Full URL to Imgur's deletion API
- `metadata_url`: Imgur doesn't support metadata

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
$hash = $uploader->generateDeleteHash($path, $timestamp);
```

The hash includes:
- File path
- Timestamp
- Secret salt
- SHA-256 hash

### Hash Verification
```php
$isValid = $uploader->verifyDeleteHash($path, $hash, $timestamp);
```

Verification checks:
1. Hash hasn't expired (default 24 hours)
2. Hash matches expected value

### Deletion Flow
1. Forum generates delete URL with query parameters:
   ```
   deletemessage.phtml?url=path/to/file&hash=abc123&t=1234567890
   ```
2. User clicks delete URL
3. `deleteimage.php` receives the URL and passes it to the uploader
4. Uploader handles the URL:
   - Determines if it's a full URL or path fragment
   - Extracts necessary parameters (url, hash, timestamp)
   - Verifies hash/credentials
   - Performs deletion
5. Uploader returns success/failure

### URL Handling
Uploaders must handle both full URLs and path fragments:
- Full URLs: "https://server.com/path?params"
- Path fragments: "path?params"

Each uploader is responsible for:
1. Detecting URL format
2. Extracting required parameters
3. Constructing the correct deletion request
4. Performing proper verification

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
$uploader = new DAV($config);
$result = $uploader->upload($filename, $namespace, $metadata);

if ($result) {
    $imageUrl = $result['url'];          // Full URL to view image
    $deleteUrl = $result['delete_url'];  // Query parameter format for deletion
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

### deleteByUrl(string $deleteUrl): bool
Delete a file using a deletion URL. The URL format varies by uploader:

- **DAV**: Expects a query string with parameters:
  ```
  url=path/to/file&hash=abc123&t=1234567890
  ```
  Where:
  - `url`: The path to the file relative to the uploader's base path
  - `hash`: A verification hash generated using `generateDeleteHash()`
  - `t`: Unix timestamp when the hash was generated

- **Imgur**: Expects either:
  ```
  url=https://imgur.com/abc123
  ```
  Or just the deletehash directly. The uploader will extract the deletehash from the URL.

The uploader is responsible for:
1. Parsing the URL format appropriate for its implementation
2. Extracting necessary parameters
3. Verifying the deletion request (e.g., checking hash validity)
4. Performing the actual deletion

Returns true if deletion was successful, false otherwise. Use `getError()` to get error details.

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

## Application Layer vs Upload Class Responsibilities

### Application Layer (Forum Software)
- Converting relative delete URLs to absolute URLs for display
- Handling deletion requests through `deleteimage.php`
- Parsing query parameters from incoming requests
- Managing user authentication and session state
- Determining if a user has permission to delete
- Constructing the initial delete URL with hash and timestamp
- Handling HTTP responses and user feedback
- Managing the upload form and file selection
- Client-side image resizing before upload
- Displaying upload progress and results

### Upload Class
- Implementing the upload interface for specific backends
- Generating and verifying deletion hashes
- Managing file storage and retrieval
- Handling backend-specific URL formats
- Managing metadata storage and retrieval
- Implementing backend-specific error handling
- Ensuring secure file operations
- Managing file paths and namespaces
- Handling backend authentication
- Implementing retry logic for failed operations

The upload class is designed to be backend-agnostic, focusing on file operations and security. The application layer handles user interaction, authentication, and URL management. This separation allows for:
- Easy addition of new storage backends
- Consistent security model across backends
- Flexible URL handling for different forum setups
- Clear separation of concerns
