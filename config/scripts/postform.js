document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('imagefile');
    const uploadInfo = document.getElementById('uploadInfo');
    const widthSelect = document.getElementById('imageWidth');
    const messageTextarea = document.querySelector('textarea[name="message"]');

    if (!fileInput || !fileInput.form) return;

    function generateTimestampFilename(originalName) {
        const now = new Date();
        const timestamp = now.toISOString()
            .replace(/T/, '-')
            .replace(/\..+/, '')
            .replace(/:/g, '-');

        // Get the extension from the original filename or default to .jpg
        const ext = originalName.includes('.')
            ? originalName.slice(originalName.lastIndexOf('.'))
            : '.jpg';

        return `pasted-${timestamp}${ext}`;
    }

    async function handleImage(file) {
        if (!file || !file.type.startsWith('image/')) return;

        uploadInfo.textContent = `Original size: ${(file.size / 1024).toFixed(2)} KB`;

        const maxWidth = parseInt(widthSelect.value);
        const resizer = new ImageResizer({ maxWidth, quality: 0.8 });
        const result = await resizer.resize(file);

        // Check resized file size against maximum allowed
        if (result.file.size > maxImageBytes) {
            uploadInfo.textContent = `Error: Resized file size (${(result.file.size / 1024).toFixed(2)} KB) exceeds maximum allowed size (${(maxImageBytes / 1024).toFixed(2)} KB)`;
            fileInput.value = ''; // Clear the file input
            return;
        }

        if (file.size !== result.file.size) {
            uploadInfo.textContent += ` | Resized size: ${(result.file.size / 1024).toFixed(2)} KB`;
        }

        // Store the resized file in the file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(result.file);
        fileInput.files = dataTransfer.files;

        // Set metadata
        const metadata = {
            original: file.name,
            resized: result.file.name,
            wasResized: result.wasResized
        };
        fileInput.form.fileMetadata.value = JSON.stringify(metadata);
    }

    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        try {
            await handleImage(file);
        } catch (error) {
            uploadInfo.textContent = 'Error resizing image: ' + error.message;
        }
    });

    // Add paste handler for images
    if (messageTextarea) {
        messageTextarea.addEventListener('paste', async (e) => {
            const items = e.clipboardData?.items;
            if (!items) return;

            for (const item of items) {
                if (item.type.startsWith('image/')) {
                    e.preventDefault();
                    const file = item.getAsFile();
                    if (!file) continue;

                    try {
                        // Create a new file with the timestamp-based name
                        const newFile = new File([file], generateTimestampFilename(file.name), {
                            type: file.type,
                            lastModified: file.lastModified
                        });

                        await handleImage(newFile);
                        // Trigger a change event on the file input to ensure the form knows about the new file
                        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                    } catch (error) {
                        uploadInfo.textContent = 'Error handling pasted image: ' + error.message;
                    }
                    break;
                }
            }
        });
    }
});
