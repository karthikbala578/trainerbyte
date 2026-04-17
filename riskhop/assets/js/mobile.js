// ============================================================================
// RISKHOP - MOBILE MENU AND RESPONSIVE FUNCTIONALITY
// File: assets/js/mobile.js
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== MOBILE MENU TOGGLE ==========
    initMobileMenu();
    
    // ========== HANDLE WINDOW RESIZE ==========
    window.addEventListener('resize', handleResize);
    
    // ========== PREVENT IOS ZOOM ==========
    preventIOSZoom();
});

// ============================================================================
// INITIALIZE MOBILE MENU
// ============================================================================
function initMobileMenu() {
    const headerLeft = document.querySelector('.admin-header .header-left');
    
    if (!headerLeft) return;
    
    // Only create menu toggle on mobile
    if (window.innerWidth <= 767) {
        createMenuToggle(headerLeft);
    }
}

// ============================================================================
// CREATE MENU TOGGLE BUTTON AND OVERLAY
// ============================================================================
function createMenuToggle(headerLeft) {
    // Check if toggle already exists
    if (document.querySelector('.mobile-menu-toggle')) return;
    
    // Create hamburger button
    const menuToggle = document.createElement('button');
    menuToggle.className = 'mobile-menu-toggle';
    menuToggle.innerHTML = '☰';
    menuToggle.setAttribute('aria-label', 'Toggle menu');
    menuToggle.setAttribute('type', 'button');
    
    // Insert before h2
    headerLeft.insertBefore(menuToggle, headerLeft.firstChild);
    
    // Create overlay if it doesn't exist
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // Setup event listeners
    setupMenuListeners();
}

// ============================================================================
// SETUP MENU EVENT LISTENERS
// ============================================================================
function setupMenuListeners() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (!menuToggle || !sidebar) return;
    
    // Toggle menu on button click
    menuToggle.addEventListener('click', function() {
        toggleMenu();
    });
    
    // Close menu on overlay click
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeMenu();
        });
    }
    
    // Close menu when clicking sidebar links
    const menuLinks = sidebar.querySelectorAll('a');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 767) {
                closeMenu();
            }
        });
    });
}

// ============================================================================
// TOGGLE MENU OPEN/CLOSE
// ============================================================================
function toggleMenu() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (!sidebar) return;
    
    const isOpen = sidebar.classList.contains('open');
    
    if (isOpen) {
        closeMenu();
    } else {
        openMenu();
    }
}

// ============================================================================
// OPEN MENU
// ============================================================================
function openMenu() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar) {
        sidebar.classList.add('open');
    }
    
    if (overlay) {
        overlay.classList.add('active');
    }
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = 'hidden';
}

// ============================================================================
// CLOSE MENU
// ============================================================================
function closeMenu() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar) {
        sidebar.classList.remove('open');
    }
    
    if (overlay) {
        overlay.classList.remove('active');
    }
    
    // Restore body scroll
    document.body.style.overflow = '';
}

// ============================================================================
// HANDLE WINDOW RESIZE
// ============================================================================
function handleResize() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const headerLeft = document.querySelector('.admin-header .header-left');
    
    if (window.innerWidth > 767) {
        // Desktop view - close menu and remove mobile elements
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Remove mobile menu toggle if exists
        if (menuToggle) {
            menuToggle.remove();
        }
    } else {
        // Mobile view - create menu toggle if doesn't exist
        if (!menuToggle && headerLeft) {
            createMenuToggle(headerLeft);
        }
    }
}

// ============================================================================
// PREVENT IOS ZOOM ON INPUT FOCUS
// ============================================================================
function preventIOSZoom() {
    // Check if device is iOS
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
        const viewportMeta = document.querySelector('meta[name="viewport"]');
        
        if (viewportMeta) {
            // Store original viewport content
            const originalContent = viewportMeta.getAttribute('content');
            
            // Prevent zoom on input focus
            document.addEventListener('focusin', function(e) {
                if (e.target.tagName === 'INPUT' || 
                    e.target.tagName === 'TEXTAREA' || 
                    e.target.tagName === 'SELECT') {
                    viewportMeta.setAttribute('content', 
                        'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0'
                    );
                }
            });
            
            // Restore viewport after blur
            document.addEventListener('focusout', function(e) {
                if (e.target.tagName === 'INPUT' || 
                    e.target.tagName === 'TEXTAREA' || 
                    e.target.tagName === 'SELECT') {
                    viewportMeta.setAttribute('content', originalContent);
                }
            });
        }
    }
}

// ============================================================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ============================================================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        
        if (href === '#') return;
        
        e.preventDefault();
        
        const target = document.querySelector(href);
        
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ============================================================================
// TABLE HORIZONTAL SCROLL INDICATOR
// ============================================================================
function addTableScrollIndicator() {
    const tables = document.querySelectorAll('.modern-table-container');
    
    tables.forEach(container => {
        const table = container.querySelector('.modern-table');
        
        if (!table) return;
        
        // Check if table is scrollable
        if (table.scrollWidth > container.clientWidth) {
            container.style.position = 'relative';
            
            // Add scroll indicator
            const indicator = document.createElement('div');
            indicator.className = 'scroll-indicator';
            indicator.innerHTML = '← Scroll →';
            indicator.style.cssText = `
                position: absolute;
                bottom: 10px;
                right: 10px;
                background: rgba(0,0,0,0.7);
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                pointer-events: none;
                opacity: 0.8;
            `;
            
            container.appendChild(indicator);
            
            // Hide indicator when scrolled
            container.addEventListener('scroll', function() {
                if (this.scrollLeft > 0) {
                    indicator.style.opacity = '0';
                } else {
                    indicator.style.opacity = '0.8';
                }
            });
        }
    });
}

// Add scroll indicators after page load
if (window.innerWidth <= 767) {
    window.addEventListener('load', addTableScrollIndicator);
}