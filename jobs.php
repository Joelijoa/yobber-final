<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/header.php';

// Récupération des filtres
$search = $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';

// Construction de la requête SQL
$sql = "SELECT j.*, r.company_name, r.company_logo
        FROM jobs j
        LEFT JOIN recruiter_profiles r ON j.recruiter_id = r.user_id
        WHERE j.status = 'active'";
$params = [];
if ($search) {
    $sql .= " AND (j.title LIKE :search OR j.description LIKE :search OR r.company_name LIKE :search)";
    $params['search'] = "%$search%";
}
if ($location) {
    $sql .= " AND j.location LIKE :location";
    $params['location'] = "%$location%";
}
if ($type) {
    $sql .= " AND j.type = :type";
    $params['type'] = $type;
}
$sql .= " ORDER BY j.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Récupérer les favoris de l'utilisateur connecté (si candidat)
$favorites = [];
if (isLoggedIn() && isUserType('candidate')) {
    $stmtFav = $conn->prepare("SELECT job_id FROM favorites WHERE candidate_id = ?");
    $stmtFav->execute([$_SESSION['user_id']]);
    $favorites = array_column($stmtFav->fetchAll(), 'job_id');
}
?>
<div class="container py-5">
    <h1 class="mb-4">Offres d'emploi</h1>
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" class="form-control" name="q" placeholder="Mot-clé, entreprise..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="location" placeholder="Lieu" value="<?php echo htmlspecialchars($location); ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="type">
                <option value="">Type de contrat</option>
                <option value="full-time" <?php if($type==='full-time') echo 'selected'; ?>>Temps plein</option>
                <option value="part-time" <?php if($type==='part-time') echo 'selected'; ?>>Temps partiel</option>
                <option value="contract" <?php if($type==='contract') echo 'selected'; ?>>Contrat</option>
                <option value="internship" <?php if($type==='internship') echo 'selected'; ?>>Stage</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </div>
    </form>
    <div class="row g-4">
        <?php if (count($jobs) > 0): ?>
            <?php foreach ($jobs as $job): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php if (!empty($job['company_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>" class="company-logo me-3" style="width: 50px; height: 50px; object-fit: contain;">
                                <?php else: ?>
                                    <div class="company-logo-placeholder me-3 bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-building text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <h6 class="card-subtitle text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                                </div>
                            </div>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <?php echo htmlspecialchars($job['location']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($job['type']); ?></span>
                                <small class="text-muted">Publié le <?php echo date('d/m/Y', strtotime($job['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">Voir plus</a>
                            <?php if (isLoggedIn() && isUserType('candidate')): ?>
                                <button type="button" 
                                        class="btn btn-link favorite-btn p-0 ms-2" 
                                        data-job-id="<?php echo htmlspecialchars($job['id']); ?>"
                                        data-is-favorite="<?php echo in_array($job['id'], $favorites) ? 'true' : 'false'; ?>"
                                        title="<?php echo in_array($job['id'], $favorites) ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>">
                                    <i class="fa<?php echo in_array($job['id'], $favorites) ? 's' : 'r'; ?> fa-heart text-danger"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">Aucune offre ne correspond à vos critères.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, initialisation des favoris...');
    
    // Attacher les événements à tous les boutons de favoris
    document.querySelectorAll('[data-job-id]').forEach(button => {
        console.log('Bouton trouvé:', button);
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Clic sur le bouton de favori');
            toggleFavorite(this);
        });
    });
});

function toggleFavorite(button) {
    console.log('Fonction toggleFavorite appelée');
    const jobId = button.dataset.jobId;
    if (!jobId) {
        console.error('ID de l\'offre manquant');
        return;
    }

    // Désactiver le bouton pendant la requête
    button.disabled = true;
    console.log('Envoi de la requête pour le job ID:', jobId);

    fetch('favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `job_id=${encodeURIComponent(jobId)}`,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Statut de la réponse:', response.status);
        return response.text().then(text => {
            console.log('Réponse brute:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e);
                throw new Error('Réponse invalide du serveur');
            }
        });
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            const icon = button.querySelector('i');
            if (data.action === 'added') {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.dataset.isFavorite = 'true';
                button.title = 'Retirer des favoris';
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.dataset.isFavorite = 'false';
                button.title = 'Ajouter aux favoris';
            }
        } else {
            throw new Error(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur détaillée:', error);
        alert('Une erreur est survenue lors de la mise à jour des favoris. Veuillez réessayer.');
    })
    .finally(() => {
        button.disabled = false;
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<style>
.company-logo-placeholder {
    background-color: #f3f3f3;
    border-radius: 0.5rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style> 