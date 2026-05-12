// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && 
            sidebar.classList.contains('active') &&
            !sidebar.contains(event.target) &&
            !mobileMenuBtn.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                    
                    // Add error message
                    let errorMsg = field.parentNode.querySelector('.field-error');
                    if (!errorMsg) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'field-error';
                        errorMsg.style.color = '#dc3545';
                        errorMsg.style.fontSize = '14px';
                        errorMsg.style.marginTop = '5px';
                        errorMsg.textContent = 'This field is required';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.style.borderColor = '#28a745';
                    
                    // Remove error message
                    const errorMsg = field.parentNode.querySelector('.field-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                alert('Please fill in all required fields!');
            }
        });
    });
    
    // Generate random numbers for forms
    function generateRandomNumber(length) {
        let result = '';
        const characters = '0123456789';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return result;
    }
    
    // Auto-generate numbers for forms
    const generateButtons = document.querySelectorAll('.generate-number');
    generateButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetField = document.getElementById(targetId);
            if (targetField) {
                targetField.value = generateRandomNumber(8);
            }
        });
    });
    
    // Date picker enhancement
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function(input) {
        // Set max date to today
        const today = new Date().toISOString().split('T')[0];
        if (!input.max) {
            input.max = today;
        }
    });
    
    // Confirm delete actions
    const deleteLinks = document.querySelectorAll('a[href*="delete"]');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Print functionality
    const printButtons = document.querySelectorAll('.print-btn');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Dashboard chart (if needed)
    function initDashboardCharts() {
        // This function can be expanded to include charts
        // Using Chart.js or similar library
        console.log('Dashboard charts initialized');
    }
    
    // Initialize charts if on dashboard
    if (document.querySelector('.dashboard-stats')) {
        initDashboardCharts();
    }
});

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}