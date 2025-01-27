document.addEventListener('DOMContentLoaded', () => {
    // File upload progress and preview
    const uploadForm = document.getElementById('uploadForm');
    const progressBar = document.querySelector('.progress-bar-inner');
    const preview = document.querySelector('.preview');

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(uploadForm);

            try {
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();

                if (result.filePath) {
                    const fileType = result.filePath.split('.').pop().toLowerCase();
                    preview.innerHTML = ''; // Clear previous preview
                    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp'].includes(fileType)) {
                        preview.innerHTML = `<img src="${result.filePath}" alt="Uploaded Image" class="preview-image">`;
                    } else if (['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv'].includes(fileType)) {
                        preview.innerHTML = `<video controls class="preview-video"><source src="${result.filePath}" type="video/${fileType}"></video>`;
                    } else if (['mp3', 'wav', 'ogg', 'wma'].includes(fileType)) {
                        preview.innerHTML = `<audio controls class="preview-audio"><source src="${result.filePath}" type="audio/${fileType}"></audio>`;
                    } else if (['pdf'].includes(fileType)) {
                        preview.innerHTML = `<embed src="${result.filePath}" type="application/pdf" width="100%" height="600px" />`;
                    } else if (['txt'].includes(fileType)) {
                        const response = await fetch(result.filePath);
                        const text = await response.text();
                        preview.innerHTML = `<pre class="preview-text">${text}</pre>`;
                    } else {
                        preview.innerHTML = `<p>File uploaded successfully: <a href="${result.filePath}" target="_blank">${result.filePath}</a></p>`;
                    }
                } else if (result.error) {
                    alert(result.error);
                }
            } catch (error) {
                alert('Error uploading file: ' + error.message);
            }
        });

        uploadForm.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                progressBar.style.width = `${percentComplete}%`;
            }
        });
    }

    // Sign out functionality
    const signOutButton = document.querySelector('button[name="action"][value="signout"]');
    if (signOutButton) {
        signOutButton.addEventListener('click', async (event) => {
            event.preventDefault();
            console.log('Sign out button clicked');

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        'action': 'signout'
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                console.log('Sign out response:', result);

                if (result.status === 'success') {
                    console.log('Sign out successful, redirecting...');
                    window.location.href = 'index.php';
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error signing out:', error);
                alert('Error signing out: ' + error.message);
            }
        });
    }

    // Admin functionality
    const adminDeleteUserForm = document.getElementById('adminDeleteUserForm');
    const adminDeleteFileForm = document.getElementById('adminDeleteFileForm');

    if (adminDeleteUserForm) {
        adminDeleteUserForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(adminDeleteUserForm);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                console.log('Delete user response:', result);

                if (result.status === 'success') {
                    showAlert('User deleted successfully', 'success');
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                showAlert('Error deleting user: ' + error.message);
            }
        });
    }

    if (adminDeleteFileForm) {
        adminDeleteFileForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(adminDeleteFileForm);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                console.log('Delete file response:', result);

                if (result.status === 'success') {
                    showAlert('File deleted successfully', 'success');
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error deleting file:', error);
                showAlert('Error deleting file: ' + error.message);
            }
        });
    }

    // Sign up and sign in functionality
    const signinForm = document.getElementById('signinForm');
    if (signinForm) {
        signinForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(signinForm);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                console.log('Sign in response:', result);

                if (result.status === 'success') {
                    console.log('Sign in successful, redirecting...');
                    window.location.href = 'index.php';
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error signing in:', error);
                alert('Error signing in: ' + error.message);
            }
        });
    }

    // Admin login functionality
    const adminButton = document.getElementById('adminButton');
    const adminModal = document.getElementById('adminModal');
    const adminModalClose = document.querySelector('.admin-modal-close');
    const adminLoginForm = document.getElementById('adminLoginForm');

    if (adminButton) {
        adminButton.addEventListener('click', () => {
            adminModal.style.display = 'block';
        });
    }

    if (adminModalClose) {
        adminModalClose.addEventListener('click', () => {
            adminModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === adminModal) {
            adminModal.style.display = 'none';
        }
    });

    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(adminLoginForm);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                console.log('Admin login response:', result);

                if (result.status === 'success') {
                    console.log('Admin login successful, redirecting...');
                    window.location.href = 'index.php';
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error logging in as admin:', error);
                alert('Error logging in as admin: ' + error.message);
            }
        });
    }

    // Utility function to show alerts
    const showAlert = (message, type = 'error') => {
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        document.body.appendChild(alertBox);
        setTimeout(() => {
            alertBox.remove();
        }, 3000);
    };

    // Improved error handling
    const handleError = (error) => {
        console.error(error);
        showAlert(error.message);
    };
});
