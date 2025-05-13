// Fonction pour afficher les messages flash
function showFlashMessage(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('main').insertBefore(alertDiv, document.querySelector('main').firstChild);
}

// Fonction pour gérer les formulaires AJAX
function handleFormSubmit(form, successCallback) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        try {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
            
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showFlashMessage(data.message, 'success');
                if (successCallback) successCallback(data);
            } else {
                showFlashMessage(data.message, 'danger');
            }
        } catch (error) {
            showFlashMessage('Une erreur est survenue', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
}

// Fonction pour gérer les favoris
function toggleFavorite(jobId) {
    const button = document.querySelector(`[data-job-id="${jobId}"]`);
    const icon = button.querySelector('i');
    
    fetch(`/candidate/favorites/toggle.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            icon.classList.toggle('fas');
            icon.classList.toggle('far');
            showFlashMessage(data.message, 'success');
        } else {
            showFlashMessage(data.message, 'danger');
        }
    })
    .catch(error => {
        showFlashMessage('Une erreur est survenue', 'danger');
    });
}

// Fonction pour gérer les notifications
function markNotificationAsRead(notificationId) {
    fetch(`/notifications/mark-read.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
            notification.classList.remove('unread');
            updateNotificationCount();
        }
    });
}

// Fonction pour mettre à jour le compteur de notifications
function updateNotificationCount() {
    const count = document.querySelectorAll('.notification.unread').length;
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Fonction pour gérer le tri des tableaux
function sortTable(table, column, type = 'string') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aValue = a.cells[column].textContent.trim();
        let bValue = b.cells[column].textContent.trim();
        
        if (type === 'number') {
            aValue = parseFloat(aValue) || 0;
            bValue = parseFloat(bValue) || 0;
        } else if (type === 'date') {
            aValue = new Date(aValue);
            bValue = new Date(bValue);
        }
        
        return aValue > bValue ? 1 : -1;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Fonction pour gérer la recherche en temps réel
function handleSearch(input, table) {
    input.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Initialisation des tooltips Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialisation des popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}); 