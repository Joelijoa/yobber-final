<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';

// Vérifier que l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    http_response_code(403);
    exit('Accès non autorisé');
}

// Vérifier que l'ID de l'application est fourni
$application_id = $_GET['id'] ?? null;
if (!$application_id) {
    http_response_code(400);
    exit('ID manquant');
}

try {
    // Récupérer le chemin du CV
    $stmt = $conn->prepare("
        SELECT a.cv_path, j.recruiter_id
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ? AND j.recruiter_id = ?
    ");
    $stmt->execute([$application_id, getUserId()]);
    $result = $stmt->fetch();

    if (!$result) {
        http_response_code(404);
        exit('CV non trouvé');
    }

    $cv_path = __DIR__ . '/../' . $result['cv_path'];

    // Vérifier que le fichier existe
    if (!file_exists($cv_path)) {
        http_response_code(404);
        exit('Fichier non trouvé');
    }

    // Envoyer le fichier PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="cv.pdf"');
    header('Cache-Control: public, max-age=0');
    readfile($cv_path);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    exit('Erreur lors de la récupération du CV');
} 