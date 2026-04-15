/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

// Global DataTables Defaults
$.extend(true, $.fn.dataTable.defaults, {
    language: {
        lengthMenu: "Tampilkan _MENU_ entri",
        search: "Cari:",
        info: "Menampilkan _START_—_END_ dari _TOTAL_ entri",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(difilter dari _MAX_ total entri)",
        paginate: {
            previous: "‹",
            next: "›",
            first: "«",
            last: "»"
        },
        emptyTable: "Tidak ada data tersedia dalam tabel",
        zeroRecords: "Tidak ada data yang cocok dengan pencarian",
        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        loadingRecords: "Memuat data...",
        aria: {
            sortAscending: ": aktifkan untuk mengurutkan kolom naik",
            sortDescending: ": aktifkan untuk mengurutkan kolom turun"
        }
    },
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
    responsive: true,
    autoWidth: false,
    dom: "<'row g-2 align-items-center mb-3'<'col-auto'l><'col-12 col-md-auto ms-md-auto'f>>" +
         "rt" +
         "<'row g-2 align-items-center mt-3'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
    drawCallback: function() {
        // Style pagination after draw
        $('.dataTables_paginate .pagination').addClass('pagination-sm');
    }
});

/**
 * Initialize standard DataTable
 */
function initStandardDataTable(selector, options = {}) {
    const defaultOptions = {
        order: [[0, 'asc']],
        columnDefs: [
            { 
                targets: 'no-sort', 
                orderable: false 
            },
            { 
                targets: 'text-center', 
                className: 'text-center' 
            }
        ]
    };
    
    const mergedOptions = $.extend(true, {}, defaultOptions, options);
    
    const table = $(selector).DataTable(mergedOptions);
    
    // Style enhancements
    enhanceDataTableUI(selector);
    
    return table;
}

/**
 * Initialize DataTable with server-side processing
 */
function initServerSideDataTable(selector, ajaxUrl, columns, options = {}) {
    const defaultOptions = {
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'POST',
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX error:', error);
                showToast('Gagal memuat data', 'error');
            }
        },
        columns: columns
    };
    
    const mergedOptions = $.extend(true, {}, defaultOptions, options);
    
    const table = $(selector).DataTable(mergedOptions);
    
    enhanceDataTableUI(selector);
    
    return table;
}

/**
 * Initialize DataTable with export buttons
 */
function initDataTableWithExport(selector, options = {}) {
    const defaultOptions = {
        dom: "<'row g-2 mb-3'<'col-auto'l><'col-12 col-md-auto'B><'col-12 col-md-auto ms-md-auto'f>>" +
             "rt" +
             "<'row g-2 mt-3'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                },
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i> Print',
                className: 'btn btn-primary btn-sm',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            },
            {
                extend: 'colvis',
                text: '<i class="bi bi-eye me-1"></i> Kolom',
                className: 'btn btn-secondary btn-sm'
            }
        ]
    };
    
    const mergedOptions = $.extend(true, {}, defaultOptions, options);
    
    const table = $(selector).DataTable(mergedOptions);
    
    enhanceDataTableUI(selector);
    
    return table;
}

/**
 * Enhance DataTable UI
 */
function enhanceDataTableUI(selector) {
    const wrapper = $(selector + '_wrapper');
    
    // Style length select
    wrapper.find('.dataTables_length select')
        .addClass('form-select form-select-sm')
        .css('width', 'auto');
    
    // Style search input
    wrapper.find('.dataTables_filter input')
        .addClass('form-control form-control-sm')
        .attr('placeholder', 'Cari data...')
        .css('width', '100%')
        .css('max-width', '280px');
    
    // Style info text
    wrapper.find('.dataTables_info')
        .addClass('text-muted small');
    
    // Style pagination
    wrapper.find('.dataTables_paginate')
        .addClass('pagination-sm');
    
    // Add icons to pagination
    wrapper.find('.paginate_button.previous').html('<i class="bi bi-chevron-left"></i>');
    wrapper.find('.paginate_button.next').html('<i class="bi bi-chevron-right"></i>');
}

/**
 * Reload DataTable with new data
 */
function reloadDataTable(tableId) {
    const table = $(tableId).DataTable();
    table.ajax.reload(null, false); // false = keep current page
}

/**
 * Clear search and reload
 */
function clearSearchDataTable(tableId) {
    const table = $(tableId).DataTable();
    table.search('').draw();
}

/**
 * Custom filtering for status
 */
function filterByStatus(tableId, columnIndex, statusValue) {
    const table = $(tableId).DataTable();
    
    if (statusValue === 'all') {
        table.column(columnIndex).search('').draw();
    } else {
        table.column(columnIndex).search(statusValue).draw();
    }
}

/**
 * Custom filtering for date range
 */
function filterByDateRange(tableId, columnIndex, startDate, endDate) {
    const table = $(tableId).DataTable();
    
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            const date = new Date(data[columnIndex]);
            const start = startDate ? new Date(startDate) : null;
            const end = endDate ? new Date(endDate) : null;
            
            if (!start && !end) return true;
            if (!start && date <= end) return true;
            if (!end && date >= start) return true;
            if (date >= start && date <= end) return true;
            
            return false;
        }
    );
    
    table.draw();
    
    // Clear custom filter after use
    $.fn.dataTable.ext.search.pop();
}

/**
 * Add row click handler
 */
function addRowClickHandler(tableId, callback) {
    $(tableId + ' tbody').on('click', 'tr', function() {
        const table = $(tableId).DataTable();
        const data = table.row(this).data();
        
        if (data) {
            callback(data);
        }
    });
}

/**
 * Get selected rows
 */
function getSelectedRows(tableId) {
    const table = $(tableId).DataTable();
    const selected = [];
    
    table.rows('.selected').every(function() {
        selected.push(this.data());
    });
    
    return selected;
}

/**
 * Highlight row
 */
function highlightRow(tableId, rowIndex) {
    const table = $(tableId).DataTable();
    const row = table.row(rowIndex);
    
    $(row.node()).addClass('table-warning');
    
    setTimeout(() => {
        $(row.node()).removeClass('table-warning');
    }, 2000);
}

/**
 * Custom render functions
 */
const DataTableRender = {
    // Render badge status
    status: function(data) {
        const statusMap = {
            'aktif': '<span class="badge bg-success">Aktif</span>',
            'nonaktif': '<span class="badge bg-secondary">Nonaktif</span>',
            'pending': '<span class="badge bg-warning">Pending</span>',
            'berjalan': '<span class="badge bg-info">Berjalan</span>',
            'selesai': '<span class="badge bg-success">Selesai</span>',
            'dibatalkan': '<span class="badge bg-danger">Dibatalkan</span>',
            'disetujui': '<span class="badge bg-success">Disetujui</span>',
            'ditolak': '<span class="badge bg-danger">Ditolak</span>'
        };
        
        return statusMap[data.toLowerCase()] || `<span class="badge bg-secondary">${data}</span>`;
    },
    
    // Render date in Indonesian format
    date: function(data) {
        if (!data) return '-';
        
        const date = new Date(data);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(date);
    },
    
    // Render number with thousand separator
    number: function(data) {
        return new Intl.NumberFormat('id-ID').format(data);
    },
    
    // Render currency (Rupiah)
    currency: function(data) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(data);
    },
    
    // Render action buttons
    actions: function(id, editUrl, deleteUrl) {
        return `
            <div class="btn-group btn-group-sm" role="group">
                <a href="${editUrl}?id=${id}" class="btn btn-warning btn-sm" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                </a>
                <a href="${deleteUrl}?id=${id}" 
                   class="btn btn-danger btn-sm" 
                   title="Hapus"
                   onclick="return confirm('Yakin ingin menghapus?')">
                    <i class="bi bi-trash-fill"></i>
                </a>
            </div>
        `;
    }
};

// Auto-initialize DataTables on page load
$(document).ready(function() {
    // Auto-initialize tables with class 'datatable'
    if ($('.datatable').length > 0) {
        $('.datatable').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                initStandardDataTable(this);
            }
        });
    }
    
    // Auto-initialize tables with class 'datatable-export'
    if ($('.datatable-export').length > 0) {
        $('.datatable-export').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                initDataTableWithExport(this);
            }
        });
    }
});

// Export functions
window.initStandardDataTable = initStandardDataTable;
window.initServerSideDataTable = initServerSideDataTable;
window.initDataTableWithExport = initDataTableWithExport;
window.reloadDataTable = reloadDataTable;
window.clearSearchDataTable = clearSearchDataTable;
window.filterByStatus = filterByStatus;
window.filterByDateRange = filterByDateRange;
window.addRowClickHandler = addRowClickHandler;
window.getSelectedRows = getSelectedRows;
window.highlightRow = highlightRow;
window.DataTableRender = DataTableRender;
