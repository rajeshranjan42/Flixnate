// Content deletion handling
function handleDelete(contentId, contentTitle) {
    // Create and show the confirmation modal
    const modal = document.createElement('div');
    modal.className = 'delete-modal';
    modal.innerHTML = `
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <h3>Confirm Deletion</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="delete-modal-body">
                <p>Are you sure you want to delete "<span class="content-title">${contentTitle}</span>"?</p>
                <p class="warning">This action cannot be undone!</p>
            </div>
            <div class="delete-modal-footer">
                <button class="cancel-btn">Cancel</button>
                <button class="delete-btn">Delete</button>
            </div>
            <div class="delete-modal-status"></div>
        </div>
    `;
    document.body.appendChild(modal);

    // Show modal with animation
    setTimeout(() => modal.classList.add('active'), 10);

    // Handle close button
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.onclick = () => closeModal(modal);

    // Handle cancel button
    const cancelBtn = modal.querySelector('.cancel-btn');
    cancelBtn.onclick = () => closeModal(modal);

    // Handle delete button
    const deleteBtn = modal.querySelector('.delete-btn');
    deleteBtn.onclick = () => proceedWithDeletion(modal, contentId);

    // Close modal if clicking outside
    modal.onclick = (e) => {
        if (e.target === modal) closeModal(modal);
    };

    // Handle Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal(modal);
    });
}

function closeModal(modal) {
    modal.classList.remove('active');
    setTimeout(() => modal.remove(), 300);
}

function proceedWithDeletion(modal, contentId) {
    const statusDiv = modal.querySelector('.delete-modal-status');
    const modalContent = modal.querySelector('.delete-modal-content');
    const deleteBtn = modal.querySelector('.delete-btn');
    const cancelBtn = modal.querySelector('.cancel-btn');

    // Disable buttons and show loading state
    deleteBtn.disabled = true;
    cancelBtn.disabled = true;
    statusDiv.innerHTML = '<div class="loading">Deleting content...</div>';
    modalContent.classList.add('loading');

    // Send delete request
    fetch(`delete-content.php?id=${contentId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(response => {
        if (response.includes('success')) {
            statusDiv.innerHTML = '<div class="success">Content deleted successfully!</div>';
            modalContent.classList.add('success');
            
            // Remove the content row from the table with animation
            const contentRow = document.querySelector(`tr[data-content-id="${contentId}"]`);
            if (contentRow) {
                contentRow.style.animation = 'fadeOutUp 0.5s ease forwards';
                setTimeout(() => contentRow.remove(), 500);
            }

            // Close modal after success
            setTimeout(() => {
                closeModal(modal);
                // Reload page to update content list
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(response || 'Failed to delete content');
        }
    })
    .catch(error => {
        statusDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
        modalContent.classList.add('error');
        
        // Re-enable buttons after error
        deleteBtn.disabled = false;
        cancelBtn.disabled = false;
    });
}
