// assets/js/main.js
class SistemKredit {
    constructor() {
        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeComponents();
        this.setupAutoHideAlerts();
    }

    initializeEventListeners() {
        // Auto-hide alerts after 5 seconds
        this.setupAutoHideAlerts();
        
        // Form validation
        this.setupFormValidation();
        
        // Table interactions
        this.setupTableInteractions();
        
        // Calculator functionality
        this.setupCalculator();
    }

    initializeComponents() {
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
    }

    setupAutoHideAlerts() {
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    }

    setupFormValidation() {
        // Real-time form validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Real-time input validation
        const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    validateField(field) {
        const isValid = field.checkValidity();
        const feedback = field.parentNode.querySelector('.invalid-feedback') || field.parentNode.querySelector('.valid-feedback');
        
        if (feedback) {
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
        }
    }

    setupTableInteractions() {
        // Add row selection functionality
        const selectableRows = document.querySelectorAll('table tbody tr[data-selectable]');
        selectableRows.forEach(row => {
            row.addEventListener('click', () => {
                row.classList.toggle('table-active');
            });
        });

        // Add search functionality to tables
        const searchInputs = document.querySelectorAll('.table-search');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.filterTable(e.target);
            });
        });
    }

    filterTable(searchInput) {
        const filter = searchInput.value.toLowerCase();
        const table = searchInput.closest('.card').querySelector('table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    setupCalculator() {
        // Kredit calculator functionality
        const calculatorForms = document.querySelectorAll('.kredit-calculator');
        calculatorForms.forEach(form => {
            form.addEventListener('input', () => {
                this.calculateKredit(form);
            });
        });
    }

    calculateKredit(form) {
        const jumlah = parseFloat(form.querySelector('[name="jumlah"]').value) || 0;
        const durasi = parseInt(form.querySelector('[name="durasi"]').value) || 0;
        const bunga = parseFloat(form.querySelector('[name="bunga"]').value) || 0;
        
        if (jumlah > 0 && durasi > 0 && bunga > 0) {
            const bungaPerBulan = bunga / 100 / 12;
            const angsuran = jumlah * (bungaPerBulan * Math.pow(1 + bungaPerBulan, durasi)) / 
                            (Math.pow(1 + bungaPerBulan, durasi) - 1);
            const totalBayar = angsuran * durasi;
            const totalBunga = totalBayar - jumlah;
            
            // Update display
            const resultElement = form.querySelector('.calculator-result');
            if (resultElement) {
                resultElement.innerHTML = `
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6>Angsuran per Bulan</h6>
                            <h4 class="text-success">Rp ${Math.round(angsuran).toLocaleString('id-ID')}</h4>
                        </div>
                        <div class="col-md-4">
                            <h6>Total Pembayaran</h6>
                            <h5>Rp ${Math.round(totalBayar).toLocaleString('id-ID')}</h5>
                        </div>
                        <div class="col-md-4">
                            <h6>Total Bunga</h6>
                            <h5 class="text-warning">Rp ${Math.round(totalBunga).toLocaleString('id-ID')}</h5>
                        </div>
                    </div>
                `;
            }
        }
    }

    // Utility functions
    formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    showLoading(element) {
        element.innerHTML = '<div class="loading-spinner"></div>';
        element.disabled = true;
    }

    hideLoading(element, originalText) {
        element.innerHTML = originalText;
        element.disabled = false;
    }

    showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to page
        const container = document.querySelector('.notification-container') || document.body;
        container.insertBefore(notification, container.firstChild);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // API functions (for future integration)
    async apiCall(url, method = 'GET', data = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(url, options);
            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            this.showNotification('Terjadi kesalahan saat memproses permintaan', 'error');
            throw error;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.sistemKredit = new SistemKredit();
    
    // Additional initialization for specific pages
    initializePageSpecificFeatures();
});

function initializePageSpecificFeatures() {
    // Dashboard specific features
    if (document.querySelector('.dashboard-stats')) {
        initializeDashboardCharts();
    }

    // Pengajuan page features
    if (document.getElementById('pengajuanForm')) {
        initializePengajuanForm();
    }

    // Admin panel features
    if (document.querySelector('.admin-panel')) {
        initializeAdminFeatures();
    }
}

function initializeDashboardCharts() {
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Example chart initialization
        const ctx = document.getElementById('statsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Pengajuan Kredit',
                        data: [12, 19, 3, 5, 2, 3],
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
}

function initializePengajuanForm() {
    const form = document.getElementById('pengajuanForm');
    const jenisKreditSelect = document.getElementById('jenis_kredit_id');
    const jumlahInput = document.getElementById('jumlah');
    const durasiInput = document.getElementById('durasi');

    function updateCalculator() {
        const selectedOption = jenisKreditSelect.options[jenisKreditSelect.selectedIndex];
        
        if (selectedOption.value) {
            const maxAmount = selectedOption.getAttribute('data-max-amount');
            const maxDuration = selectedOption.getAttribute('data-max-duration');
            const bunga = selectedOption.getAttribute('data-bunga');
            
            // Update max values
            jumlahInput.max = maxAmount;
            durasiInput.max = maxDuration;
            
            // Update info texts
            document.getElementById('maxAmountText').textContent = 
                `Maksimal: Rp ${parseInt(maxAmount).toLocaleString('id-ID')}`;
            document.getElementById('maxDurationText').textContent = 
                `Maksimal: ${maxDuration} bulan`;
            
            // Show calculator
            document.getElementById('kalkulatorSection').style.display = 'block';
            calculateEstimation();
        } else {
            document.getElementById('kalkulatorSection').style.display = 'none';
        }
    }

    function calculateEstimation() {
        const selectedOption = jenisKreditSelect.options[jenisKreditSelect.selectedIndex];
        const jumlah = parseFloat(jumlahInput.value) || 0;
        const durasi = parseInt(durasiInput.value) || 0;
        const bunga = parseFloat(selectedOption.getAttribute('data-bunga')) || 0;
        
        if (jumlah > 0 && durasi > 0 && bunga > 0) {
            const bungaPerBulan = bunga / 100 / 12;
            const angsuran = jumlah * (bungaPerBulan * Math.pow(1 + bungaPerBulan, durasi)) / 
                            (Math.pow(1 + bungaPerBulan, durasi) - 1);
            
            document.getElementById('displayJumlah').textContent = 
                `Rp ${jumlah.toLocaleString('id-ID')}`;
            document.getElementById('displayBunga').textContent = bunga;
            document.getElementById('displayAngsuran').textContent = 
                `Rp ${Math.round(angsuran).toLocaleString('id-ID')}`;
        }
    }

    // Event listeners
    jenisKreditSelect.addEventListener('change', updateCalculator);
    jumlahInput.addEventListener('input', calculateEstimation);
    durasiInput.addEventListener('input', calculateEstimation);

    // Initial update
    updateCalculator();
}

function initializeAdminFeatures() {
    // Admin-specific features
    console.log('Admin features initialized');
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SistemKredit;
}