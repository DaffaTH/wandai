/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

// ============================================
// Global Configuration
// ============================================
const WANDAI = {
    baseUrl: window.location.origin,
    apiUrl: window.location.origin + '/api',
    version: '1.0.0',
    debug: true
};

// ============================================
// Utility Functions
// ============================================

/**
 * Show loading spinner
 */
function showLoading(message = 'Memuat...') {
    const loadingHtml = `
        <div id="loading-overlay" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        ">
            <div style="
                background: white;
                padding: 2rem;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            ">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-muted">${message}</div>
            </div>
        </div>
    `;
    
    if (!document.getElementById('loading-overlay')) {
        document.body.insertAdjacentHTML('beforeend', loadingHtml);
    }
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toastId = 'toast-' + Date.now();
    const bgColors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };
    
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgColors[type]} border-0" 
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icons[type]} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

/**
 * Confirm dialog with SweetAlert2 style
 */
function confirmDialog(title, text, callback) {
    if (confirm(`${title}\n\n${text}`)) {
        callback();
    }
}

/**
 * Format number to Indonesian format
 */
function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

/**
 * Format currency to Rupiah
 */
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Format date to Indonesian format
 */
function formatDate(dateString, withTime = false) {
    const date = new Date(dateString);
    const options = {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    };
    
    if (withTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return new Intl.DateTimeFormat('id-ID', options).format(date);
}

/**
 * Calculate days remaining
 */
function daysRemaining(deadlineDate) {
    const today = new Date();
    const deadline = new Date(deadlineDate);
    const diffTime = deadline - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

/**
 * Get deadline status badge
 */
function getDeadlineBadge(deadlineDate) {
    const days = daysRemaining(deadlineDate);
    
    if (days < 0) {
        return `<span class="badge bg-danger">Terlambat ${Math.abs(days)} hari</span>`;
    } else if (days === 0) {
        return `<span class="badge bg-warning">Hari ini!</span>`;
    } else if (days <= 7) {
        return `<span class="badge bg-warning">H-${days}</span>`;
    } else {
        return `<span class="badge bg-success">${days} hari lagi</span>`;
    }
}

/**
 * Validate Indonesian NIK (16 digits)
 */
function validateNIK(nik) {
    const nikPattern = /^[0-9]{16}$/;
    return nikPattern.test(nik);
}

/**
 * Validate Indonesian phone number
 */
function validatePhone(phone) {
    const phonePattern = /^(\+62|62|0)[0-9]{9,12}$/;
    return phonePattern.test(phone.replace(/\s/g, ''));
}

/**
 * Validate email
 */
function validateEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}

/**
 * Sanitize HTML to prevent XSS
 */
function sanitizeHTML(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * AJAX Helper with error handling
 */
function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showToast('Terjadi kesalahan jaringan', 'error');
            throw error;
        });
}

// ============================================
// Form Validation Enhancements
// ============================================

/**
 * Auto-validate form on submit
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
                
                showToast('Mohon lengkapi semua field yang wajib diisi', 'warning');
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Real-time validation for NIK
 */
function initNIKValidation() {
    const nikInputs = document.querySelectorAll('input[name="nik"]');
    
    nikInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validateNIK(this.value)) {
                this.classList.add('is-invalid');
                showToast('NIK harus 16 digit angka', 'warning');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Auto-format: hanya angka, max 16 digit
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 16);
        });
    });
}

/**
 * Real-time validation for phone
 */
function initPhoneValidation() {
    const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"], input[name*="telepon"]');
    
    phoneInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validatePhone(this.value)) {
                this.classList.add('is-invalid');
                showToast('Format nomor telepon tidak valid', 'warning');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
}

// ============================================
// DataTable Enhancements
// ============================================

/**
 * Initialize DataTables with Indonesian language
 */
function initDataTable(selector, options = {}) {
    const defaultOptions = {
        language: {
            lengthMenu: "Tampilkan _MENU_ entri",
            search: "Cari:",
            info: "Menampilkan _START_—_END_ dari _TOTAL_ entri",
            infoEmpty: "Tidak ada data",
            infoFiltered: "(difilter dari _MAX_ total entri)",
            paginate: {
                previous: "Sebelumnya",
                next: "Berikutnya",
                first: "Pertama",
                last: "Terakhir"
            },
            emptyTable: "Tidak ada data tersedia",
            zeroRecords: "Tidak ada data yang cocok"
        },
        pageLength: 10,
        responsive: true,
        dom: "<'row g-2 align-items-center mb-2'<'col-auto'l><'col ms-auto text-end'f>>" +
             "rt" +
             "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>"
    };
    
    return $(selector).DataTable({ ...defaultOptions, ...options });
}

// ============================================
// Chart Utilities
// ============================================

/**
 * Create Chart with default options
 */
function createChart(ctx, config) {
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Poppins, sans-serif'
                    }
                }
            }
        }
    };
    
    const mergedConfig = {
        ...config,
        options: {
            ...defaultOptions,
            ...(config.options || {})
        }
    };
    
    return new Chart(ctx, mergedConfig);
}

// ============================================
// Page-Specific Utilities
// ============================================

/**
 * Export table to Excel (simple version)
 */
function exportToExcel(tableId, filename = 'export.xlsx') {
    showLoading('Mengekspor data...');
    
    setTimeout(() => {
        const table = document.getElementById(tableId);
        const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(wb, filename);
        hideLoading();
        showToast('Data berhasil diekspor', 'success');
    }, 500);
}

/**
 * Print current page
 */
function printPage() {
    window.print();
}

// ============================================
// Initialize on DOM Ready
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialize form validation
    initFormValidation();
    initNIKValidation();
    initPhoneValidation();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    // Console welcome message
    if (WANDAI.debug) {
        console.log('%c🚀 WANDAI System v' + WANDAI.version, 'color: #F3C623; font-size: 16px; font-weight: bold;');
        console.log('%cDeveloped by BPS Kabupaten Boven Digoel', 'color: #666; font-size: 12px;');
    }
});

// ============================================
// Export functions to global scope
// ============================================
window.WANDAI = WANDAI;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.showToast = showToast;
window.confirmDialog = confirmDialog;
window.formatNumber = formatNumber;
window.formatRupiah = formatRupiah;
window.formatDate = formatDate;
window.daysRemaining = daysRemaining;
window.getDeadlineBadge = getDeadlineBadge;
window.validateNIK = validateNIK;
window.validatePhone = validatePhone;
window.validateEmail = validateEmail;
window.sanitizeHTML = sanitizeHTML;
window.debounce = debounce;
window.ajaxRequest = ajaxRequest;
window.initDataTable = initDataTable;
window.createChart = createChart;
window.exportToExcel = exportToExcel;
window.printPage = printPage;
