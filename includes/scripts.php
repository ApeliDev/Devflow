<script>
            // modal.js - Handles project submission modal functionality

// DOM Elements
const projectModal = document.getElementById('projectModal');
const successModal = document.getElementById('successModal');
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('projectFiles');
const filePreview = document.getElementById('filePreview');
const fileList = document.getElementById('fileList');
const addAnotherFileBtn = document.getElementById('addAnotherFileBtn');
const projectForm = document.getElementById('projectForm');

// Modal Functions
function openModal() {
    projectModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    projectModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openSuccessModal() {
    successModal.classList.remove('hidden');
    triggerConfetti();
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    successModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Confetti effect for success modal
function triggerConfetti() {
    const confettiContainer = document.getElementById('confettiContainer');
    confettiContainer.innerHTML = '';
    
    for (let i = 0; i < 100; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
        confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 50%)`;
        confettiContainer.appendChild(confetti);
    }
}

// File Upload Handling
fileUploadArea.addEventListener('click', () => fileInput.click());

fileUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileUploadArea.classList.add('border-2', 'border-green-500', 'bg-green-50');
});

fileUploadArea.addEventListener('dragleave', () => {
    fileUploadArea.classList.remove('border-2', 'border-green-500', 'bg-green-50');
});

fileUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUploadArea.classList.remove('border-2', 'border-green-500', 'bg-green-50');
    
    if (e.dataTransfer.files.length > 0 && validateFiles(e.dataTransfer.files)) {
        fileInput.files = e.dataTransfer.files;
        updateFilePreview();
    }
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0 && validateFiles(fileInput.files)) {
        updateFilePreview();
    }
});

// Validate files before upload
function validateFiles(files) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/zip'
    ];
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt', 'zip'];
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileExt = file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
            showAlert(`File "${file.name}" is too large. Maximum size is 10MB.`, 'error');
            return false;
        }
        
        if (!allowedExtensions.includes(fileExt) || !allowedTypes.includes(file.type)) {
            showAlert(`File type not supported: "${file.name}". Please upload PDF, JPG, PNG, DOC, DOCX, TXT, or ZIP files.`, 'error');
            return false;
        }
    }
    
    return true;
}

// Update file preview list
function updateFilePreview() {
    fileList.innerHTML = '';
    
    if (fileInput.files.length > 0) {
        filePreview.classList.remove('hidden');
        addAnotherFileBtn.classList.remove('hidden');
        
        Array.from(fileInput.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between bg-gray-50 p-3 rounded-lg';
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'flex items-center';
            
            let iconClass = 'bi-file-earmark';
            if (file.type.includes('image')) iconClass = 'bi-file-image';
            else if (file.type.includes('pdf')) iconClass = 'bi-file-earmark-pdf';
            else if (file.type.includes('word')) iconClass = 'bi-file-earmark-word';
            else if (file.type.includes('zip')) iconClass = 'bi-file-earmark-zip';
            else if (file.type.includes('text')) iconClass = 'bi-file-earmark-text';
            
            fileInfo.innerHTML = `
                <i class="bi ${iconClass} text-gray-500 mr-3"></i>
                <div>
                    <p class="text-sm font-medium text-gray-700 truncate" style="max-width: 200px;">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                </div>
            `;
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'text-gray-400 hover:text-red-500';
            removeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeFile(index);
            });
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            fileList.appendChild(fileItem);
        });
    } else {
        filePreview.classList.add('hidden');
        addAnotherFileBtn.classList.add('hidden');
    }
}

// Remove file from list
function removeFile(index) {
    const dt = new DataTransfer();
    const files = fileInput.files;
    
    for (let i = 0; i < files.length; i++) {
        if (index !== i) {
            dt.items.add(files[i]);
        }
    }
    
    fileInput.files = dt.files;
    updateFilePreview();
}

// Add Another File button functionality
addAnotherFileBtn.addEventListener('click', () => {
    const newFileInput = document.createElement('input');
    newFileInput.type = 'file';
    newFileInput.multiple = true;
    newFileInput.className = 'hidden';
    
    newFileInput.click();
    
    newFileInput.addEventListener('change', () => {
        if (newFileInput.files.length > 0 && validateFiles(newFileInput.files)) {
            const dt = new DataTransfer();
            
            // Add existing files
            for (let i = 0; i < fileInput.files.length; i++) {
                dt.items.add(fileInput.files[i]);
            }
            
            // Add new files
            for (let i = 0; i < newFileInput.files.length; i++) {
                dt.items.add(newFileInput.files[i]);
            }
            
            fileInput.files = dt.files;
            updateFilePreview();
        }
    });
});

// Show alert message
function showAlert(message, type = 'error') {
    // Remove any existing alerts
    const existingAlert = document.querySelector('.form-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `form-alert p-4 mb-6 rounded-lg ${type === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'}`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <i class="bi ${type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Insert after the form header or at the top of the form
    const formHeader = projectForm.querySelector('h3');
    if (formHeader) {
        formHeader.parentNode.insertBefore(alertDiv, formHeader.nextSibling);
    } else {
        projectForm.prepend(alertDiv);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Form submission with AJAX
projectForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitButton = document.getElementById('submitButton');
    const originalButtonText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="spinner mr-2"></div> Processing...';
    submitButton.disabled = true;
    
    try {
        // Validate required fields
        const requiredFields = ['fullName', 'email', 'institution', 'course', 'projectType', 'projectDescription'];
        for (const field of requiredFields) {
            if (!this.elements[field].value.trim()) {
                throw new Error(`Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()} field`);
            }
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(this.elements['email'].value)) {
            throw new Error('Please enter a valid email address');
        }
        
        // Validate agreement checkbox
        if (!this.elements['agreeTerms'].checked) {
            throw new Error('You must agree to the academic integrity policy');
        }
        
        // Validate due date if provided
        if (this.elements['dueDate'].value) {
            const dueDate = new Date(this.elements['dueDate'].value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (dueDate < today) {
                throw new Error('Due date must be in the future');
            }
        }
        
        const formData = new FormData(this);
        
        // Add files to form data
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('projectFiles[]', fileInput.files[i]);
        }
        
        const response = await fetch('submit_project.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to submit form. Please try again.');
        }
        
        // Success - show success modal
        closeModal();
        openSuccessModal();
        
        // Reset form
        this.reset();
        filePreview.classList.add('hidden');
        fileList.innerHTML = '';
        
    } catch (error) {
        showAlert(error.message, 'error');
        console.error('Submission error:', error);
        
        // Scroll to the alert
        const alert = document.querySelector('.form-alert');
        if (alert) {
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } finally {
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
    }
});

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === projectModal) {
        closeModal();
    }
    if (e.target === successModal) {
        closeSuccessModal();
    }
});

// Spinner animation style
const spinnerStyle = document.createElement('style');
spinnerStyle.textContent = `
    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(0,0,0,.1);
        border-radius: 50%;
        border-top-color: #000;
        animation: spin 1s ease-in-out infinite;
    }
    .confetti {
        position: absolute;
        width: 10px;
        height: 10px;
        opacity: 0;
        animation: fall linear forwards;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    @keyframes fall {
        to {
            transform: translateY(100vh) rotate(720deg);
            opacity: 1;
        }
    }
    .form-alert {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(spinnerStyle);

// Initialize date picker with min date of today
document.addEventListener('DOMContentLoaded', () => {
    const dueDateInput = document.getElementById('dueDate');
    if (dueDateInput) {
        const today = new Date().toISOString().split('T')[0];
        dueDateInput.min = today;
    }
});
</script>