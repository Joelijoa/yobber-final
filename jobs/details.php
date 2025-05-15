<!-- Toast pour les notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="favoriteToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-heart me-2 text-danger"></i>
            <strong class="me-auto">Favoris</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<script>
function toggleFavorite(button) {
    const jobId = button.getAttribute('data-job-id');
    
    fetch('/public/favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `job_id=${jobId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'icône du bouton
            const icon = button.querySelector('i');
            if (data.action === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('active');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('active');
            }

            // Afficher le toast
            const toast = new bootstrap.Toast(document.getElementById('favoriteToast'));
            document.getElementById('toastMessage').textContent = data.message;
            toast.show();
        } else {
            // En cas d'erreur
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la mise à jour des favoris.');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 