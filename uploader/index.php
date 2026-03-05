<?php
// uploader/index.php - Reusable Uploader Component
$res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
?>
<div class="upload-container">
    <div class="upload-area" id="drop-zone">
        <i class="fas fa-cloud-upload-alt upload-icon"></i>
        <span class="upload-text">Drag & Drop Receipt Screenshot</span>
        <span class="upload-hint">or click to browse from files</span>
        <input type="file" id="file-input" accept="image/*">
    </div>

    <div class="preview-container" id="preview-container">
        <img src="" alt="Preview" class="preview-image" id="preview-img">
        <div class="upload-progress" id="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <button class="btn-upload" id="upload-btn">Confirm & Upload Receipt</button>
    </div>

    <div id="status-msg" class="status-message"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const previewContainer = document.getElementById('preview-container');
    const previewImg = document.getElementById('preview-img');
    const uploadBtn = document.getElementById('upload-btn');
    const progressBar = document.getElementById('progress-bar');
    const progressContainer = document.getElementById('progress-container');
    const statusMsg = document.getElementById('status-msg');
    const resId = <?php echo $res_id; ?>;

    // Trigger file input
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag and drop events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
    });

    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length) handleFiles(files[0]);
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) handleFiles(this.files[0]);
    });

    function handleFiles(file) {
        if (!file.type.startsWith('image/')) {
            alert('Please upload an image file.');
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
            uploadBtn.style.display = 'block';
            statusMsg.textContent = '';
        };
        reader.readAsDataURL(file);
    }

    uploadBtn.addEventListener('click', () => {
        const file = fileInput.files[0] || (dropZone.files && dropZone.files[0]);
        if (!file && !previewImg.src) return;

        const formData = new FormData();
        // If file was from drag & drop and not in input, we might need to handle it differently 
        // but drop actually doesn't populate fileInput automatically. 
        // Let's ensure we have the file blob.
        
        let fileToUpload = fileInput.files[0];
        if(!fileToUpload && dropZone._droppedFile) {
            fileToUpload = dropZone._droppedFile;
        }

        if(!fileToUpload) {
             // Fallback for drag-drop file reference
             const dataTransfer = new DataTransfer();
             // This is tricky without storing the file ref. Let's fix handleFiles.
        }

        performUpload(fileToUpload);
    });

    // Modified handleFiles to store reference
    function handleFiles(file) {
        if (!file.type.startsWith('image/')) {
            showStatus('Please upload an image file.', 'error');
            return;
        }
        window._pendingFile = file; // Simple global store for this demo
        
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
            uploadBtn.style.display = 'block';
            statusMsg.textContent = '';
        };
        reader.readAsDataURL(file);
    }
    
    // Re-assign because of the fix
    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length) handleFiles(files[0]);
    });

    function performUpload(file) {
        file = file || window._pendingFile;
        if(!file) return;

        const formData = new FormData();
        formData.append('receipt', file);
        formData.append('res_id', resId);

        progressContainer.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/uploader/upload.php', true);

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                progressBar.style.width = percent + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showStatus('Receipt uploaded successfully!', 'success');
                        uploadBtn.style.display = 'none';
                    } else {
                        showStatus(response.error || 'Upload failed.', 'error');
                        uploadBtn.disabled = false;
                        uploadBtn.textContent = 'Confirm & Upload Receipt';
                    }
                } catch (e) {
                    showStatus('Server error. Please try again.', 'error');
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Confirm & Upload Receipt';
                }
            }
        };

        xhr.send(formData);
    }

    function showStatus(msg, type) {
        statusMsg.textContent = msg;
        statusMsg.className = 'status-message ' + type;
    }
});
</script>