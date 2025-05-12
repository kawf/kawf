class ImageResizer {
    constructor(options = {}) {
        this.maxWidth = options.maxWidth || 1920;
        this.maxHeight = options.maxHeight || 1080;
        this.quality = options.quality || 0.8;
    }

    getExtensionFromMimeType(mimeType) {
        const mimeToExt = {
            'image/jpeg': '.jpg',
            'image/png': '.png',
            'image/gif': '.gif',
            'image/webp': '.webp'
        };
        return mimeToExt[mimeType] || '.jpg';
    }

    ensureCorrectExtension(filename, mimeType) {
        const ext = this.getExtensionFromMimeType(mimeType);
        const currentExt = filename.slice(filename.lastIndexOf('.')).toLowerCase();

        // If no extension or wrong extension, add/change it
        if (!currentExt || currentExt !== ext) {
            // Remove any existing extension
            const baseName = filename.slice(0, filename.lastIndexOf('.'));
            return baseName + ext;
        }
        return filename;
    }

    async resize(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    // Check if resizing is needed
                    if (img.width <= this.maxWidth && img.height <= this.maxHeight) {
                        resolve({
                            file: file,
                            wasResized: false
                        });
                        return;
                    }

                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    // Calculate new dimensions
                    if (width > this.maxWidth) {
                        height = Math.round((height * this.maxWidth) / width);
                        width = this.maxWidth;
                    }
                    if (height > this.maxHeight) {
                        width = Math.round((width * this.maxHeight) / height);
                        height = this.maxHeight;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    canvas.getContext('2d').drawImage(img, 0, 0, width, height);

                    // Convert to blob
                    canvas.toBlob(
                        (blob) => {
                            // Preserve original MIME type if it's a supported format
                            const mimeType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)
                                ? file.type
                                : 'image/jpeg';
                            const newFilename = this.ensureCorrectExtension(file.name, mimeType);
                            const resizedFile = new File([blob], newFilename, {
                                type: mimeType,
                                lastModified: Date.now()
                            });
                            resolve({
                                file: resizedFile,
                                wasResized: true
                            });
                        },
                        file.type === 'image/png' ? 'image/png' : 'image/jpeg',
                        this.quality
                    );
                };
                img.onerror = reject;
                img.src = e.target.result;
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }
}
