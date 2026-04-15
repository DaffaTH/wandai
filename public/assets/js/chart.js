/*
 * WANDAI System - Source Code Reference
 * Developed by: BPS Kabupaten Paniai (M. Daffa Taufiq H.)
 * Year: 2025
 * Original Author: Paniai Team
 * Provided as reference for internal learning purposes.
 */

// Global Chart.js Defaults
Chart.defaults.font.family = 'Poppins, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.legend.position = 'top';
Chart.defaults.plugins.legend.labels.padding = 15;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: 'bold' };
Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };

// Wandai Color Palette
const WANDAIColors = {
    primary: '#F3C623',
    secondary: '#ff7043',
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#3b82f6',
    light: '#f8fafc',
    dark: '#1e293b',
    
    // Additional colors for charts
    palette: [
        '#F3C623', '#ff7043', '#10b981', '#3b82f6', 
        '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'
    ],
    
    // Gradient colors
    gradients: {
        primary: ['rgba(243, 198, 35, 0.8)', 'rgba(243, 198, 35, 0.2)'],
        secondary: ['rgba(255, 112, 67, 0.8)', 'rgba(255, 112, 67, 0.2)'],
        success: ['rgba(16, 185, 129, 0.8)', 'rgba(16, 185, 129, 0.2)'],
        info: ['rgba(59, 130, 246, 0.8)', 'rgba(59, 130, 246, 0.2)']
    }
};

/**
 * Create gradient for chart background
 */
function createGradient(ctx, colors) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, colors[0]);
    gradient.addColorStop(1, colors[1]);
    return gradient;
}

/**
 * Create Line Chart
 */
function createLineChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error('Canvas not found:', canvasId);
        return null;
    }
    
    const defaultOptions = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                }
            }
        }
    };
    
    const config = $.extend(true, {}, defaultOptions, options);
    
    return new Chart(ctx, config);
}

/**
 * Create Bar Chart
 */
function createBarChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error('Canvas not found:', canvasId);
        return null;
    }
    
    const defaultOptions = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                }
            }
        }
    };
    
    const config = $.extend(true, {}, defaultOptions, options);
    
    return new Chart(ctx, config);
}

/**
 * Create Pie Chart
 */
function createPieChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error('Canvas not found:', canvasId);
        return null;
    }
    
    const defaultOptions = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    const config = $.extend(true, {}, defaultOptions, options);
    
    return new Chart(ctx, config);
}

/**
 * Create Doughnut Chart
 */
function createDoughnutChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error('Canvas not found:', canvasId);
        return null;
    }
    
    const defaultOptions = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    const config = $.extend(true, {}, defaultOptions, options);
    
    return new Chart(ctx, config);
}

/**
 * Create Radar Chart
 */
function createRadarChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) {
        console.error('Canvas not found:', canvasId);
        return null;
    }
    
    const defaultOptions = {
        type: 'radar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                r: {
                    beginAtZero: true
                }
            }
        }
    };
    
    const config = $.extend(true, {}, defaultOptions, options);
    
    return new Chart(ctx, config);
}

/**
 * Update chart data dynamically
 */
function updateChartData(chart, newLabels, newData) {
    if (!chart) {
        console.error('Chart instance not found');
        return;
    }
    
    chart.data.labels = newLabels;
    
    if (Array.isArray(newData[0])) {
        // Multiple datasets
        newData.forEach((data, index) => {
            if (chart.data.datasets[index]) {
                chart.data.datasets[index].data = data;
            }
        });
    } else {
        // Single dataset
        chart.data.datasets[0].data = newData;
    }
    
    chart.update('active');
}

/**
 * Create monthly performance chart
 */
function createMonthlyPerformanceChart(canvasId, monthlyData) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    
    const data = {
        labels: months,
        datasets: [{
            label: 'Target',
            data: monthlyData.target,
            borderColor: WANDAIColors.warning,
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            borderWidth: 2,
            tension: 0.4
        }, {
            label: 'Realisasi',
            data: monthlyData.realisasi,
            borderColor: WANDAIColors.success,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            tension: 0.4
        }]
    };
    
    return createLineChart(canvasId, data);
}

/**
 * Create kegiatan status pie chart
 */
function createKegiatanStatusChart(canvasId, statusData) {
    const data = {
        labels: ['Pending', 'Berjalan', 'Selesai', 'Dibatalkan'],
        datasets: [{
            data: [
                statusData.pending || 0,
                statusData.berjalan || 0,
                statusData.selesai || 0,
                statusData.dibatalkan || 0
            ],
            backgroundColor: [
                WANDAIColors.warning,
                WANDAIColors.info,
                WANDAIColors.success,
                WANDAIColors.danger
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
    
    return createDoughnutChart(canvasId, data);
}

/**
 * Create tim performance bar chart
 */
function createTimPerformanceChart(canvasId, timData) {
    const data = {
        labels: timData.map(t => t.name),
        datasets: [{
            label: 'Jumlah Kegiatan',
            data: timData.map(t => t.jumlah_kegiatan),
            backgroundColor: WANDAIColors.palette,
            borderWidth: 1,
            borderColor: '#fff'
        }]
    };
    
    return createBarChart(canvasId, data, {
        options: {
            indexAxis: 'y', // Horizontal bar
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Create progress gauge chart
 */
function createProgressGauge(canvasId, percentage, label) {
    const data = {
        labels: ['Selesai', 'Sisa'],
        datasets: [{
            data: [percentage, 100 - percentage],
            backgroundColor: [WANDAIColors.success, '#e2e8f0'],
            borderWidth: 0
        }]
    };
    
    const options = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '75%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        },
        plugins: [{
            id: 'centerText',
            beforeDraw: function(chart) {
                const ctx = chart.ctx;
                const width = chart.width;
                const height = chart.height;
                
                ctx.restore();
                const fontSize = (height / 114).toFixed(2);
                ctx.font = `bold ${fontSize}em Poppins`;
                ctx.textBaseline = 'middle';
                
                const text = percentage + '%';
                const textX = Math.round((width - ctx.measureText(text).width) / 2);
                const textY = height / 2;
                
                ctx.fillStyle = '#1e293b';
                ctx.fillText(text, textX, textY);
                
                // Label below
                ctx.font = `${fontSize * 0.5}em Poppins`;
                ctx.fillStyle = '#64748b';
                const labelX = Math.round((width - ctx.measureText(label).width) / 2);
                ctx.fillText(label, labelX, textY + 30);
                
                ctx.save();
            }
        }]
    };
    
    const ctx = document.getElementById(canvasId);
    return new Chart(ctx, options);
}

/**
 * Destroy chart instance
 */
function destroyChart(chart) {
    if (chart && typeof chart.destroy === 'function') {
        chart.destroy();
    }
}

/**
 * Export chart as image
 */
function exportChartAsImage(chart, filename = 'chart.png') {
    if (!chart || !chart.canvas) {
        console.error('Invalid chart instance');
        return;
    }
    
    const url = chart.canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = filename;
    link.href = url;
    link.click();
}

// Export functions to global scope
window.WANDAIColors = WANDAIColors;
window.createGradient = createGradient;
window.createLineChart = createLineChart;
window.createBarChart = createBarChart;
window.createPieChart = createPieChart;
window.createDoughnutChart = createDoughnutChart;
window.createRadarChart = createRadarChart;
window.updateChartData = updateChartData;
window.createMonthlyPerformanceChart = createMonthlyPerformanceChart;
window.createKegiatanStatusChart = createKegiatanStatusChart;
window.createTimPerformanceChart = createTimPerformanceChart;
window.createProgressGauge = createProgressGauge;
window.destroyChart = destroyChart;
window.exportChartAsImage = exportChartAsImage;
