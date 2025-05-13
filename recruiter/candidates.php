<?php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les paramètres de filtrage
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? trim($_GET['job_type']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'last_application_date';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Construire la requête de base
$query = "
    SELECT DISTINCT 
        c.*,
        u.email,
        COUNT(DISTINCT a.id) as total_applications,
        MAX(a.created_at) as last_application_date,
        GROUP_CONCAT(DISTINCT j.title) as applied_jobs,
        GROUP_CONCAT(DISTINCT j.type) as job_types
    FROM candidates c
    JOIN users u ON c.user_id = u.id
    JOIN applications a ON c.user_id = a.candidate_id
    JOIN jobs j ON a.job_id = j.id
    WHERE j.recruiter_id = ?
";

$params = [$user_id];

// Ajouter les conditions de recherche
if ($search) {
    $query .= " AND (
        c.first_name LIKE ? OR 
        c.last_name LIKE ? OR 
        c.summary LIKE ? OR 
        u.email LIKE ?
    )";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($location) {
    $query .= " AND c.location LIKE ?";
    $params[] = "%$location%";
}

if ($job_type) {
    $query .= " AND j.type = ?";
    $params[] = $job_type;
}

$query .= " GROUP BY c.user_id";

// Ajouter le tri
$allowed_sort_columns = ['last_application_date', 'total_applications', 'first_name'];
$sort_by = in_array($sort_by, $allowed_sort_columns) ? $sort_by : 'last_application_date';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
$query .= " ORDER BY $sort_by $sort_order";

// Exécuter la requête
$stmt = $conn->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les types de jobs uniques pour le filtre
$stmt = $conn->prepare("
    SELECT DISTINCT j.type 
    FROM jobs j 
    WHERE j.recruiter_id = ? 
    ORDER BY j.type
");
$stmt->execute([$user_id]);
$job_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les localisations uniques pour le filtre
$stmt = $conn->prepare("
    SELECT DISTINCT c.location 
    FROM candidates c
    JOIN applications a ON c.user_id = a.candidate_id
    JOIN jobs j ON a.job_id = j.id
    WHERE j.recruiter_id = ? AND c.location IS NOT NULL
    ORDER BY c.location
");
$stmt->execute([$user_id]);
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Gérer l'export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=candidates.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nom', 'Prénom', 'Email', 'Téléphone', 'Localisation', 'Résumé', 'Nombre de candidatures', 'Dernière candidature']);
    
    foreach ($candidates as $candidate) {
        fputcsv($output, [
            $candidate['last_name'],
            $candidate['first_name'],
            $candidate['email'],
            $candidate['phone'],
            $candidate['location'],
            $candidate['summary'],
            $candidate['total_applications'],
            date('d/m/Y', strtotime($candidate['last_application_date']))
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Base de données des candidats</h2>
        <a href="?export=csv<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
           class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exporter en CSV
        </a>
    </div>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Nom, prénom, email, compétences...">
                </div>
                
                <div class="col-md-3">
                    <label for="location" class="form-label">Localisation</label>
                    <select class="form-select" id="location" name="location">
                        <option value="">Toutes les localisations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>"
                                    <?php echo $location === $loc ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="job_type" class="form-label">Type de poste</label>
                    <select class="form-select" id="job_type" name="job_type">
                        <option value="">Tous les types</option>
                        <?php foreach ($job_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"
                                    <?php echo $job_type === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Trier par</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="last_application_date" <?php echo $sort_by === 'last_application_date' ? 'selected' : ''; ?>>
                            Date de candidature
                        </option>
                        <option value="total_applications" <?php echo $sort_by === 'total_applications' ? 'selected' : ''; ?>>
                            Nombre de candidatures
                        </option>
                        <option value="first_name" <?php echo $sort_by === 'first_name' ? 'selected' : ''; ?>>
                            Nom
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="candidates.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($candidates)): ?>
        <div class="alert alert-info">
            Aucun candidat ne correspond à vos critères de recherche.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($candidates as $candidate): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                            </h5>
                            
                            <p class="text-muted">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($candidate['email']); ?><br>
                                <?php if ($candidate['phone']): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($candidate['phone']); ?><br>
                                <?php endif; ?>
                                <?php if ($candidate['location']): ?>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($candidate['location']); ?>
                                <?php endif; ?>
                            </p>

                            <?php if ($candidate['summary']): ?>
                                <p class="card-text">
                                    <strong>Résumé :</strong><br>
                                    <?php echo nl2br(htmlspecialchars(substr($candidate['summary'], 0, 150))); ?>
                                    <?php if (strlen($candidate['summary']) > 150): ?>
                                        ...
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <p class="card-text">
                                <small class="text-muted">
                                    <strong>Candidatures :</strong> <?php echo $candidate['total_applications']; ?><br>
                                    <strong>Dernière candidature :</strong> 
                                    <?php echo date('d/m/Y', strtotime($candidate['last_application_date'])); ?>
                                </small>
                            </p>

                            <div class="mt-3">
                                <?php if ($candidate['cv_path']): ?>
                                    <a href="../uploads/cv/<?php echo htmlspecialchars($candidate['cv_path']); ?>" 
                                       class="btn btn-primary btn-sm" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Voir le CV
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" 
                                        class="btn btn-info btn-sm"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#candidateModal<?php echo $candidate['user_id']; ?>">
                                    <i class="fas fa-info-circle"></i> Plus d'infos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal des détails du candidat -->
                <div class="modal fade" id="candidateModal<?php echo $candidate['user_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Profil de <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Informations personnelles</h6>
                                        <p>
                                            <strong>Email :</strong> <?php echo htmlspecialchars($candidate['email']); ?><br>
                                            <?php if ($candidate['phone']): ?>
                                                <strong>Téléphone :</strong> <?php echo htmlspecialchars($candidate['phone']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($candidate['location']): ?>
                                                <strong>Localisation :</strong> <?php echo htmlspecialchars($candidate['location']); ?><br>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Statistiques</h6>
                                        <p>
                                            <strong>Nombre de candidatures :</strong> <?php echo $candidate['total_applications']; ?><br>
                                            <strong>Dernière candidature :</strong> 
                                            <?php echo date('d/m/Y', strtotime($candidate['last_application_date'])); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($candidate['summary']): ?>
                                    <div class="mt-3">
                                        <h6>Résumé professionnel</h6>
                                        <p><?php echo nl2br(htmlspecialchars($candidate['summary'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <h6>Offres auxquelles le candidat a postulé</h6>
                                    <ul>
                                        <?php foreach (explode(',', $candidate['applied_jobs']) as $job): ?>
                                            <li><?php echo htmlspecialchars($job); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <?php if ($candidate['cv_path']): ?>
                                    <div class="mt-3">
                                        <a href="../uploads/cv/<?php echo htmlspecialchars($candidate['cv_path']); ?>" 
                                           class="btn btn-primary" target="_blank">
                                            <i class="fas fa-file-pdf"></i> Voir le CV complet
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 