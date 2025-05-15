<?php
$page_title = "CV Reçus";
$extra_css = [];  // On n'a plus besoin des CSS de PDF.js
$extra_js = [];   // On n'a plus besoin des JS de PDF.js

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';

// Vérifier que l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    header('Location: /auth/login.php');
    exit;
}

// Récupérer les candidatures pour les offres du recruteur
$stmt = $conn->prepare("
    SELECT 
        a.id as application_id,
        a.created_at as application_date,
        a.status as application_status,
        a.cv_path,
        a.cover_letter_path,
        j.title as job_title,
        u.first_name,
        u.last_name,
        u.email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.candidate_id = u.id
    WHERE j.recruiter_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([getUserId()]);
$applications = $stmt->fetchAll();

// Traitement de la demande de résumé de CV
$cv_summary = '';
if (isset($_POST['generate_summary']) && isset($_POST['application_id'])) {
    // Ici nous ajouterons l'appel à l'API pour générer le résumé
    // Pour l'instant, c'est un placeholder
    $cv_summary = "Le résumé du CV sera généré ici via une API d'IA";
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-5">
    <div class="row">
        <!-- Liste des candidatures -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Candidatures reçues</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Candidat</th>
                                    <th>Poste</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                <tr class="application-row">
                                    <td>
                                        <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($app['application_date'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary view-cv" 
                                            data-application-id="<?php echo $app['application_id']; ?>"
                                            title="Voir le CV">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prévisualisation du CV -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Prévisualisation du CV</h5>
                    <button class="btn btn-sm btn-outline-secondary" id="btnDownloadCV" style="display: none;">
                        <i class="fas fa-download me-2"></i>Télécharger
                    </button>
                </div>
                <div class="card-body">
                    <div id="pdfViewer" class="pdf-viewer">
                        <div id="initialMessage" class="text-center text-muted py-5">
                            <i class="fas fa-file-pdf fa-3x mb-3"></i>
                            <p>Sélectionnez un CV pour le visualiser</p>
                        </div>
                        <embed id="pdfEmbed" src="" type="application/pdf" width="100%" height="600px" style="display: none;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé du CV -->
        <div class="col-lg-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Résumé</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-3" id="btnGenerateSummary" style="display: none;">
                        <i class="fas fa-magic me-2"></i>Générer
                    </button>
                    <button class="btn btn-outline-primary w-100" id="btnExportSummary" style="display: none;">
                        <i class="fas fa-file-export me-2"></i>Exporter
                    </button>
                    <div id="summaryContent" class="mt-3">
                        <div id="summaryInitialMessage" class="text-center text-muted">
                            <small>Sélectionnez un CV et cliquez sur "Générer" pour obtenir un résumé</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pdf-viewer {
    background-color: #f8f9fa;
    border-radius: 4px;
}

.application-row {
    cursor: pointer;
}

.application-row:hover {
    background-color: rgba(0,123,255,0.1);
}

#summaryContent {
    font-size: 0.9em;
    max-height: 500px;
    overflow-y: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pdfEmbed = document.getElementById('pdfEmbed');
    const initialMessage = document.getElementById('initialMessage');
    const btnDownloadCV = document.getElementById('btnDownloadCV');
    const btnGenerateSummary = document.getElementById('btnGenerateSummary');
    const btnExportSummary = document.getElementById('btnExportSummary');
    const summaryContent = document.getElementById('summaryContent');
    let currentApplicationId = null;

    // Gestionnaire pour les boutons "voir CV"
    document.querySelectorAll('.view-cv').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentApplicationId = this.dataset.applicationId;
            
            // Mise à jour de l'UI
            document.querySelectorAll('.application-row').forEach(r => 
                r.classList.remove('table-active'));
            this.closest('.application-row').classList.add('table-active');
            
            // Afficher le PDF
            const pdfUrl = `/public/api/get-cv.php?id=${currentApplicationId}`;
            initialMessage.style.display = 'none';
            pdfEmbed.style.display = 'block';
            pdfEmbed.src = pdfUrl;
            
            // Afficher les boutons
            btnDownloadCV.style.display = 'inline-block';
            btnGenerateSummary.style.display = 'inline-block';
            btnExportSummary.style.display = 'none';
            
            // Réinitialiser le contenu du résumé
            summaryContent.innerHTML = '<div class="text-center text-muted"><small>Cliquez sur "Générer" pour obtenir un résumé</small></div>';
        });
    });

    // Gestionnaire pour le bouton de génération de résumé
    btnGenerateSummary.addEventListener('click', function() {
        if (!currentApplicationId) return;

        // Afficher un indicateur de chargement
        summaryContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

        // Appeler l'API pour générer le résumé
        fetch(`/public/api/generate-cv-summary.php?id=${currentApplicationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher le résumé
                    const summary = data.summary;
                    let html = `
                        <div class="cv-summary">
                            <h6>Candidat</h6>
                            <p>${summary.candidate.name}<br>
                            <small class="text-muted">${summary.candidate.email}</small></p>
                            
                            <h6>Poste</h6>
                            <p>${summary.job.title}</p>
                            
                            <h6>Résumé</h6>
                            <p>${summary.summary}</p>
                        </div>
                    `;
                    summaryContent.innerHTML = html;
                    btnExportSummary.style.display = 'inline-block';
                } else {
                    summaryContent.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                summaryContent.innerHTML = '<div class="alert alert-danger">Une erreur est survenue lors de la génération du résumé</div>';
            });
    });

    // Gestionnaire pour le bouton de téléchargement
    btnDownloadCV.addEventListener('click', function() {
        if (currentApplicationId) {
            window.open(`/public/api/get-cv.php?id=${currentApplicationId}`, '_blank');
        }
    });

    // Gestionnaire pour le bouton d'export du résumé
    btnExportSummary.addEventListener('click', function() {
        const summaryText = summaryContent.innerText;
        const blob = new Blob([summaryText], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'resume-cv.txt';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 