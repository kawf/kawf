/**
 * Image action handlers for the image browser
 */

function deleteImage(forum, path, imageName) {
  console.log('deleteImage called with:', { forum, path, imageName });

  if (confirm('Are you sure you want to delete "' + imageName + '"?')) {
    fetch('/' + forum + '/deleteimage.phtml', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        path: path
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove the image card from the DOM
        const imageCard = document.querySelector(`.image-card img[alt="${imageName}"]`).closest('.image-card');
        imageCard.remove();

        // If no images left, show the no_images message
        const imageGrid = document.querySelector('.image-grid');
        if (imageGrid && !imageGrid.querySelector('.image-card')) {
          const noImages = document.createElement('p');
          noImages.textContent = 'No images found in this forum.';
          imageGrid.parentNode.insertBefore(noImages, imageGrid);
          imageGrid.remove();
        }
      } else {
        alert(data.error || 'Failed to delete image. Please try again.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to delete image. Please try again.');
    });
  }
}

function copyImageLink(imageUrl) {
  navigator.clipboard.writeText(imageUrl).then(() => {
    // Show a temporary success message
    const btn = document.activeElement;
    const originalText = btn.textContent;
    btn.textContent = '✓';
    btn.style.backgroundColor = '#fff';
    setTimeout(() => {
      btn.textContent = originalText;
      btn.style.backgroundColor = '';
    }, 1000);
  }).catch(err => {
    console.error('Failed to copy image URL:', err);
    alert('Failed to copy image URL. Please try again.');
  });
}
