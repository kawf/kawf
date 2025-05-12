document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('imagefile');
    const uploadInfo = document.getElementById('uploadInfo');
    const widthSelect = document.getElementById('imageWidth');

    if (!fileInput || !fileInput.form) return;

    async function handleImage(file) {
        if (!file || !file.type.startsWith('image/')) return;

        uploadInfo.textContent = `Original size: ${(file.size / 1024).toFixed(2)} KB`;

        const maxWidth = parseInt(widthSelect.value);
        const resizer = new ImageResizer({ maxWidth, quality: 0.8 });
        const result = await resizer.resize(file);

        if (file.size !== result.file.size) {
            uploadInfo.textContent += ` | Resized size: ${(result.file.size / 1024).toFixed(2)} KB`;
        }

        // Store the resized file data
        const reader = new FileReader();
        reader.onload = (e) => {
            fileInput.form.fileData.value = e.target.result;
        };
        reader.readAsDataURL(result.file);

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
});
