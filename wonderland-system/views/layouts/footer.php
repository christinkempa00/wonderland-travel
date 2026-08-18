<?php
/**
 * Footer Layout Component
 */
?>

<footer class="footer glass-card" style="margin-top: auto; padding: 1rem 1.5rem; border-radius: 0;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="text-sm text-muted">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
        </div>
        <div class="text-sm text-muted">
            Version <?= APP_VERSION ?>
        </div>
    </div>
</footer>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner"></div>
</div>

<!-- Global Scripts -->
<script>
/**
 * Dropdown Handler
 */
document.addEventListener('click', function(e) {
    const dropdownBtn = e.target.closest('[data-dropdown]');
    const dropdowns = document.querySelectorAll('.dropdown');
    
    if (dropdownBtn) {
        e.preventDefault();
        const dropdown = dropdownBtn.closest('.dropdown');
        const isOpen = dropdown.classList.contains('open');
        
        // Close all dropdowns
        dropdowns.forEach(d => d.classList.remove('open'));
        
        // Toggle current
        if (!isOpen) {
            dropdown.classList.add('open');
        }
    } else {
        // Close all if clicking outside
        dropdowns.forEach(d => d.classList.remove('open'));
    }
});

/**
 * Toast Notification System
 */
const Toast = {
    container: null,
    
    init() {
        this.container = document.getElementById('toastContainer');
    },
    
    show(type, title, message, duration = 5000) {
        if (!this.container) this.init();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${this.getIcon(type)}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${this.escapeHtml(title)}</div>
                ${message ? `<div class="toast-message">${this.escapeHtml(message)}</div>` : ''}
            </div>
            <button type="button" class="toast-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        this.container.appendChild(toast);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.remove(toast);
        });
        
        // Auto remove
        if (duration > 0) {
            setTimeout(() => this.remove(toast), duration);
        }
        
        return toast;
    },
    
    remove(toast) {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    },
    
    getIcon(type) {
        const icons = {
            success: 'check',
            error: 'times',
            warning: 'exclamation',
            info: 'info'
        };
        return icons[type] || 'info';
    },
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    success(title, message) { return this.show('success', title, message); },
    error(title, message) { return this.show('error', title, message); },
    warning(title, message) { return this.show('warning', title, message); },
    info(title, message) { return this.show('info', title, message); }
};

/**
 * Loading Overlay
 */
const Loading = {
    overlay: null,
    
    init() {
        this.overlay = document.getElementById('loadingOverlay');
    },
    
    show() {
        if (!this.overlay) this.init();
        this.overlay.style.display = 'flex';
    },
    
    hide() {
        if (!this.overlay) this.init();
        this.overlay.style.display = 'none';
    }
};

/**
 * Confirm Dialog
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format Currency
 */
function formatRupiah(number) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}

/**
 * Parse Rupiah to Number
 */
function parseRupiah(string) {
    return parseInt(string.replace(/[^0-9]/g, '')) || 0;
}

/**
 * Format Date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * AJAX Helper
 */
async function fetchApi(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    };
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, mergedOptions);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Terjadi kesalahan');
        }
        
        return data;
    } catch (error) {
        Toast.error('Error', error.message);
        throw error;
    }
}

/**
 * Delete Confirmation
 */
document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('[data-delete]');
    
    if (deleteBtn) {
        e.preventDefault();
        
        const url = deleteBtn.dataset.delete;
        const message = deleteBtn.dataset.message || 'Apakah Anda yakin ingin menghapus data ini?';
        
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.innerHTML = `
                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                <input type="hidden" name="_method" value="DELETE">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
});

/**
 * Auto-format Rupiah Input
 */
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('rupiah-input')) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = new Intl.NumberFormat('id-ID').format(value);
    }
});

/**
 * Show flash messages from PHP
 */
document.addEventListener('DOMContentLoaded', function() {
    <?php
    $flashTypes = ['success', 'error', 'warning', 'info'];
    foreach ($flashTypes as $type):
        $messages = Session::getFlash($type);
        foreach ($messages as $message):
    ?>
    Toast.<?= $type ?>(
        '<?= $type === 'success' ? 'Berhasil' : ($type === 'error' ? 'Error' : ($type === 'warning' ? 'Perhatian' : 'Info')) ?>',
        '<?= addslashes($message) ?>'
    );
    <?php
        endforeach;
    endforeach;
    ?>
});

/**
 * Tab Button Handler
 */
document.addEventListener('click', function(e) {
    const tabBtn = e.target.closest('.tab-btn');
    if (tabBtn) {
        const container = tabBtn.closest('.account-tabs') || tabBtn.closest('.tabs');
        if (container) {
            container.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            tabBtn.classList.add('active');
            
            const type = tabBtn.dataset.type;
            const table = document.getElementById('accountsTable');
            if (table) {
                table.querySelectorAll('tbody tr').forEach(row => {
                    if (type === 'all' || row.dataset.type === type) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        }
    }
});

/**
 * Sidebar Toggle
 */
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
});

// Restore sidebar state
if (localStorage.getItem('sidebarCollapsed') === 'true') {
    document.body.classList.add('sidebar-collapsed');
}
</script>
