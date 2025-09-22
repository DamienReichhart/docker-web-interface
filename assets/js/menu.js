/**
 * This file is maintained for backward compatibility
 * All functionality has been moved to app.js for better organization
 */

// Redirect to app.js
console.log('menu.js: Functionality moved to app.js');

// Initialize sidebar when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if app.js is loaded
    if (typeof initSidebar === 'function') {
        // App.js is loaded, do nothing
    } else {
        console.warn('app.js not loaded, falling back to basic sidebar functionality');
        
        // Basic sidebar functionality as fallback
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('active');
                    document.body.classList.toggle('sidebar-active');
                }
            });
        }
    }
});
