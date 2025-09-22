/**
 * Docker Image Manager
 * Handles pull and build operations with loading indicators
 */
class DockerImageManager {
    constructor() {
        this.initEventListeners();
    }

    /**
     * Initialize event listeners for forms
     */
    initEventListeners() {
        // Pull image form
        const pullImageForm = document.getElementById('pull-image-form');
        if (pullImageForm) {
            pullImageForm.addEventListener('submit', this.handlePullImageSubmit.bind(this));
        }

        // Build image form
        const buildImageForm = document.getElementById('build-image-form');
        if (buildImageForm) {
            buildImageForm.addEventListener('submit', this.handleBuildImageSubmit.bind(this));
        }
    }

    /**
     * Handle pull image form submission
     * @param {Event} event - Form submit event
     */
    async handlePullImageSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const resultContainer = document.getElementById('operation-result');
        
        try {
            // Prevent default loading spinner from being triggered
            event.stopPropagation();
            
            this.showLoading(submitButton, 'Pulling image...');
            this.showProgressContainer(resultContainer, 'Image pull in progress...');
            
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            await this.handleResponse(response, resultContainer);
        } catch (error) {
            this.showError(resultContainer, 'Error pulling image: ' + error.message);
        } finally {
            this.hideLoading(submitButton, 'Pull Image');
        }
    }

    /**
     * Handle build image form submission
     * @param {Event} event - Form submit event
     */
    async handleBuildImageSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const resultContainer = document.getElementById('operation-result');
        
        try {
            // Prevent default loading spinner from being triggered
            event.stopPropagation();
            
            this.showLoading(submitButton, 'Building image...');
            this.showProgressContainer(resultContainer, 'Image build in progress...');
            
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            await this.handleResponse(response, resultContainer);
        } catch (error) {
            this.showError(resultContainer, 'Error building image: ' + error.message);
        } finally {
            this.hideLoading(submitButton, 'Build Image');
        }
    }

    /**
     * Handle API response
     * @param {Response} response - Fetch API response
     * @param {HTMLElement} resultContainer - Container for displaying results
     */
    async handleResponse(response, resultContainer) {
        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(resultContainer, data.message || 'Operation completed successfully');
                
                // Redirect if a redirect URL is provided
                if (data.redirect) {
                    setTimeout(() => window.location.href = data.redirect, 2000);
                }
            } else {
                this.showError(resultContainer, data.message || 'Operation failed');
                if (data.command_output) {
                    this.showCommandOutput(resultContainer, data.command_output);
                }
            }
        } else {
            // If not JSON, show the raw response text
            const text = await response.text();
            resultContainer.innerHTML = `<div class="alert alert-info">${text}</div>`;
        }
    }

    /**
     * Show loading state
     * @param {HTMLElement} button - Submit button
     * @param {string} loadingText - Text to display during loading
     */
    showLoading(button, loadingText) {
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${loadingText}`;
    }

    /**
     * Hide loading state
     * @param {HTMLElement} button - Submit button
     * @param {string} originalText - Text to restore after loading
     */
    hideLoading(button, originalText) {
        button.disabled = false;
        button.textContent = originalText;
    }

    /**
     * Show progress container with loading animation
     * @param {HTMLElement} container - Result container
     * @param {string} message - Progress message
     */
    showProgressContainer(container, message) {
        container.classList.add('loading');
        container.innerHTML = `
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-terminal me-2"></i>Operation Status
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>${message}</div>
                    </div>
                </div>
            </div>`;
        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Show success message
     * @param {HTMLElement} container - Result container
     * @param {string} message - Success message
     */
    showSuccess(container, message) {
        container.classList.remove('loading');
        container.classList.add('success');
        container.innerHTML = `
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle me-2"></i>Operation Complete
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> ${message}
                </div>
            </div>`;
    }

    /**
     * Show error message
     * @param {HTMLElement} container - Result container
     * @param {string} message - Error message
     */
    showError(container, message) {
        container.classList.remove('loading');
        container.classList.add('error');
        container.innerHTML = `
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Operation Failed
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ${message}
                </div>
            </div>`;
    }

    /**
     * Show command output in a formatted box
     * @param {HTMLElement} container - Result container
     * @param {string} output - Command output text
     */
    showCommandOutput(container, output) {
        // Create the command output element
        const outputDiv = document.createElement('div');
        outputDiv.className = 'mt-3';
        
        // Format the command output - replace escaped newlines with actual line breaks
        const formattedOutput = this.formatOutput(output);
        
        outputDiv.innerHTML = `
            <div class="card command-output-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-terminal me-2"></i>Command Output</span>
                    <button class="btn btn-sm btn-outline-light toggle-output" type="button">
                        <i class="bi bi-chevron-down"></i> Show
                    </button>
                </div>
                <div class="card-body bg-dark command-output" style="display: none;">
                    <pre class="m-0"><code>${formattedOutput}</code></pre>
                </div>
            </div>`;
        
        // Find the card body in the container and append the output
        const cardBody = container.querySelector('.card-body');
        cardBody.appendChild(outputDiv);
        
        // Add toggle functionality
        const toggleBtn = outputDiv.querySelector('.toggle-output');
        const outputBody = outputDiv.querySelector('.command-output');
        
        toggleBtn.addEventListener('click', () => {
            const isHidden = outputBody.style.display === 'none';
            outputBody.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden) {
                outputBody.classList.add('show');
                toggleBtn.innerHTML = '<i class="bi bi-chevron-up"></i> Hide';
            } else {
                outputBody.classList.remove('show');
                toggleBtn.innerHTML = '<i class="bi bi-chevron-down"></i> Show';
            }
        });
    }

    /**
     * Format command output for better readability
     * @param {string} output - Raw command output
     * @returns {string} - Formatted HTML output
     */
    formatOutput(output) {
        if (!output) return '';
        
        // Replace escaped newlines with actual HTML line breaks
        let formatted = output
            .replace(/\\n/g, '\n')  // Convert escaped newlines to actual newlines
            .replace(/\n/g, '<br>') // Convert newlines to HTML breaks
            .replace(/\s{2,}/g, ' ') // Normalize excessive spaces
            .trim();
            
        // Add sections for better visual separation
        formatted = formatted.replace(
            /(Status:|Digest:|Pulling from:)/g, 
            '<div class="section">$1'
        ).replace(/<div class="section">/g, '</div><div class="section">');
        
        // Remove first empty closing div
        formatted = formatted.replace('</div>', '');
        
        // Add final closing div
        formatted += '</div>';
            
        // Highlight specific command output parts
        formatted = this.highlightCommandOutput(formatted);
        
        return formatted;
    }
    
    /**
     * Apply syntax highlighting to common Docker output patterns
     * @param {string} output - Processed output text
     * @returns {string} - Output with highlighted elements
     */
    highlightCommandOutput(output) {
        // Highlight success messages
        output = output.replace(
            /(Successfully built|Successfully tagged|Image is up to date|Download complete|Pull complete)/gi,
            '<span style="color: #4caf50; font-weight: bold;">$1</span>'
        );
        
        // Highlight layer IDs
        output = output.replace(
            /([a-f0-9]{12})/g,
            '<span style="color: #03a9f4;">$1</span>'
        );
        
        // Highlight status indicators
        output = output.replace(
            /(Status|Digest|Pulling from|Using default tag|sha256):/g,
            '<span style="color: #ff9800; font-weight: bold;">$1:</span>'
        );
        
        // Highlight errors or warnings
        output = output.replace(
            /(error|warning|failed)/gi,
            '<span style="color: #f44336; font-weight: bold;">$1</span>'
        );
        
        return output;
    }

    /**
     * Escape HTML special characters to prevent XSS
     * @param {string} html - String that might contain HTML
     * @returns {string} - Escaped string
     */
    escapeHtml(html) {
        return String(html)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Prevent default loading behavior for AJAX forms
    document.querySelectorAll('form[data-no-loading]').forEach(form => {
        form.addEventListener('submit', (e) => e.stopPropagation());
    });
    
    new DockerImageManager();
}); 