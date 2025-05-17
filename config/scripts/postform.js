document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('imagefile');
    const uploadInfo = document.getElementById('uploadInfo');
    const widthSelect = document.getElementById('imageWidth');
    const messageTextarea = document.querySelector('textarea[name="message"]');
    const useCameraCheckbox = document.getElementById('useCamera');
    const cameraLabel = useCameraCheckbox.parentElement;

    if (!fileInput || !fileInput.form) return;

    // Check if device has rear camera
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: { exact: 'environment' } }
        })
        .then(stream => {
            stream.getTracks().forEach(track => track.stop());
            cameraLabel.style.display = 'table-cell';
        })
        .catch(() => {
            // hide camera label if rear camera is not available
            cameraLabel.style.display = 'none';
        });
    } else {
        // hide camera label if no camera is available
        cameraLabel.style.display = 'none';
    }

    // Handle camera mode toggle
    useCameraCheckbox.addEventListener('change', (e) => {
        if (e.target.checked) {
            fileInput.setAttribute('capture', 'environment');
        } else {
            fileInput.removeAttribute('capture');
        }
    });

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

    // Format bytes to human readable size
    function formatBytes(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(2)} ${units[unitIndex]}`;
    }

    async function handleImage(file) {
        if (!file || !file.type.startsWith('image/')) return;

        uploadInfo.textContent = `Size: ${formatBytes(file.size)}`;

        const maxWidth = parseInt(widthSelect.value);
        const resizer = new ImageResizer({ maxWidth, quality: 0.8 });
        const result = await resizer.resize(file);

        // Check resized file size against maximum allowed
        if (result.file.size > maxImageBytes) {
            uploadInfo.textContent = `Error: Resized file size (${formatBytes(result.file.size)}) exceeds maximum allowed size (${formatBytes(maxImageBytes)})`;
            fileInput.value = ''; // Clear the file input
            return;
        }

        if (file.size !== result.file.size) {
            uploadInfo.textContent += ` | Resized: ${formatBytes(result.file.size)}`;
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
