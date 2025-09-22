/**
 * Docker Portal - Modern UI JavaScript
 * Handles loading indicators, UI interactions, and UX enhancements
 */

document.addEventListener('DOMContentLoaded', () => {
  // Initialize UI components
  initSidebar();
  initTooltips();
  initFormValidation();
  initLoadingIndicators();
  initSearchFunctionality();
  initToasts();
  initAjaxLinks();
});

/**
 * Sidebar initialization and functionality
 */
function initSidebar() {
  const sidebar = document.getElementById('sidebar');
  const sidebarCollapse = document.getElementById('sidebarCollapse');
  const content = document.getElementById('content');
  
  // Set the active link based on current path
  const currentPath = window.location.pathname;
  const sidebarLinks = document.querySelectorAll('#sidebar ul li a');
  
  sidebarLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (currentPath === href || (href !== '/' && currentPath.startsWith(href))) {
      link.classList.add('active');
    }
  });
  
  // Toggle sidebar
  if (sidebarCollapse) {
    sidebarCollapse.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      document.body.classList.toggle('sidebar-active');
    });
  }
  
  // Close sidebar on mobile when clicking outside
  document.addEventListener('click', (e) => {
    const isMobile = window.innerWidth <= 992;
    const clickedOutsideSidebar = !sidebar.contains(e.target) && !sidebarCollapse.contains(e.target);
    
    if (isMobile && clickedOutsideSidebar && !sidebar.classList.contains('active')) {
      sidebar.classList.add('active');
      document.body.classList.remove('sidebar-active');
    }
  });
}

/**
 * Initialize tooltips
 */
function initTooltips() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

/**
 * Form validation
 */
function initFormValidation() {
  const forms = document.querySelectorAll('.needs-validation');
  
  forms.forEach(form => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      
      form.classList.add('was-validated');
    }, false);
  });
}

/**
 * Loading indicators
 */
function initLoadingIndicators() {
  // Create loading spinner container if it doesn't exist
  if (!document.querySelector('.spinner-container')) {
    const spinnerContainer = document.createElement('div');
    spinnerContainer.className = 'spinner-container';
    
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    
    spinnerContainer.appendChild(spinner);
    document.body.appendChild(spinnerContainer);
  }
  
  // Add loading indicators to forms that don't have no-loading or data-no-loading attributes
  const forms = document.querySelectorAll('form:not(.no-loading):not([data-no-loading])');
  forms.forEach(form => {
    form.addEventListener('submit', (event) => {
      // Check if the form submission was already handled by another script
      if (!event.defaultPrevented) {
        showLoading();
      }
    });
  });
  
  // Add loading indicators to links with data-loading attribute
  const loadingLinks = document.querySelectorAll('a[data-loading="true"]');
  loadingLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      if (!e.ctrlKey && !e.metaKey) { // Allow opening in new tab without loading
        showLoading();
      }
    });
  });
}

/**
 * Show loading spinner
 */
function showLoading() {
  const spinnerContainer = document.querySelector('.spinner-container');
  if (spinnerContainer) {
    spinnerContainer.classList.add('show');
  }
}

/**
 * Hide loading spinner
 */
function hideLoading() {
  const spinnerContainer = document.querySelector('.spinner-container');
  if (spinnerContainer) {
    spinnerContainer.classList.remove('show');
  }
}

/**
 * Search functionality
 */
function initSearchFunctionality() {
  const searchInput = document.getElementById('recherche');
  if (!searchInput) return;
  
  const items = document.querySelectorAll('.server');
  if (items.length === 0) return;
  
  searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    
    items.forEach(item => {
      const title = item.querySelector('h4').textContent.toLowerCase();
      const info = item.querySelector('.infotxt') ? item.querySelector('.infotxt').textContent.toLowerCase() : '';
      
      if (title.includes(searchTerm) || info.includes(searchTerm)) {
        item.style.display = '';
      } else {
        item.style.display = 'none';
      }
    });
  });
}

/**
 * Toast notifications
 */
function initToasts() {
  // Create toast container if it doesn't exist
  if (!document.querySelector('.toast-container')) {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);
  }
}

/**
 * Show a toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of notification (success, error, warning, info)
 * @param {number} duration - Duration in milliseconds
 */
function showToast(message, type = 'info', duration = 3000) {
  const toastContainer = document.querySelector('.toast-container');
  if (!toastContainer) return;
  
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.setAttribute('role', 'alert');
  
  const typeIcons = {
    success: '<i class="fas fa-check-circle"></i>',
    error: '<i class="fas fa-exclamation-circle"></i>',
    warning: '<i class="fas fa-exclamation-triangle"></i>',
    info: '<i class="fas fa-info-circle"></i>'
  };
  
  const typeColors = {
    success: 'var(--success-color)',
    error: 'var(--danger-color)',
    warning: 'var(--warning-color)',
    info: 'var(--info-color)'
  };
  
  toast.innerHTML = `
    <div class="toast-body">
      <span style="color: ${typeColors[type] || typeColors.info}; margin-right: 8px;">
        ${typeIcons[type] || typeIcons.info}
      </span>
      ${message}
    </div>
  `;
  
  toastContainer.appendChild(toast);
  
  // Trigger animation
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);
  
  // Remove toast after duration
  setTimeout(() => {
    toast.classList.remove('show');
    
    toast.addEventListener('transitionend', () => {
      toast.remove();
    });
  }, duration);
}

/**
 * Ajax links - Handle actions without full page reload
 */
function initAjaxLinks() {
  const ajaxLinks = document.querySelectorAll('a[data-ajax="true"]');
  
  ajaxLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      
      const url = link.getAttribute('href');
      const confirmMessage = link.getAttribute('data-confirm');
      
      if (confirmMessage && !confirm(confirmMessage)) {
        return;
      }
      
      showLoading();
      
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showToast(data.message || 'Operation completed successfully', 'success');
            
            // Reload content if specified
            if (data.reload) {
              setTimeout(() => {
                window.location.reload();
              }, 1000);
            }
            
            // Redirect if specified
            if (data.redirect) {
              setTimeout(() => {
                window.location.href = data.redirect;
              }, 1000);
            }
          } else {
            showToast(data.message || 'Operation failed', 'error');
          }
        })
        .catch(error => {
          showToast('An error occurred: ' + error.message, 'error');
        })
        .finally(() => {
          hideLoading();
        });
    });
  });
}

/**
 * DarkMode toggle functionality 
 */
function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');
  
  // Save preference to localStorage
  const isDarkMode = document.body.classList.contains('dark-mode');
  localStorage.setItem('darkMode', isDarkMode ? 'enabled' : 'disabled');
}

/**
 * Initialize dark mode
 */
function initDarkMode() {
  // Check for saved preference
  const darkMode = localStorage.getItem('darkMode');
  
  if (darkMode === 'enabled') {
    document.body.classList.add('dark-mode');
  }
  
  // Add dark mode toggle button in navbar
  const navbar = document.querySelector('.navbar .container-fluid');
  if (navbar) {
    const darkModeBtn = document.createElement('button');
    darkModeBtn.className = 'btn btn-icon ms-2';
    darkModeBtn.innerHTML = '<i class="fas fa-moon"></i>';
    darkModeBtn.setAttribute('title', 'Toggle Dark Mode');
    darkModeBtn.setAttribute('data-bs-toggle', 'tooltip');
    darkModeBtn.setAttribute('data-bs-placement', 'bottom');
    
    darkModeBtn.addEventListener('click', toggleDarkMode);
    
    navbar.appendChild(darkModeBtn);
  }
}

// Function to format relative time (e.g., "2 minutes ago")
function timeAgo(date) {
  const seconds = Math.floor((new Date() - date) / 1000);
  
  let interval = Math.floor(seconds / 31536000);
  if (interval > 1) return interval + ' years ago';
  if (interval === 1) return '1 year ago';
  
  interval = Math.floor(seconds / 2592000);
  if (interval > 1) return interval + ' months ago';
  if (interval === 1) return '1 month ago';
  
  interval = Math.floor(seconds / 86400);
  if (interval > 1) return interval + ' days ago';
  if (interval === 1) return '1 day ago';
  
  interval = Math.floor(seconds / 3600);
  if (interval > 1) return interval + ' hours ago';
  if (interval === 1) return '1 hour ago';
  
  interval = Math.floor(seconds / 60);
  if (interval > 1) return interval + ' minutes ago';
  if (interval === 1) return '1 minute ago';
  
  if (seconds < 10) return 'just now';
  
  return Math.floor(seconds) + ' seconds ago';
} 