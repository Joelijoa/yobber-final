<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Headers pour JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';

// Fonction pour renvoyer une réponse JSON
function sendJsonResponse($success, $data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

// Vérifier que l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Vérifier que l'ID de l'application est fourni
$application_id = $_GET['id'] ?? null;
if (!$application_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    // Récupérer les informations du CV et du candidat
    $stmt = $conn->prepare("
        SELECT 
            a.cv_path,
            a.cover_letter_path,
            u.first_name,
            u.last_name,
            u.email,
            j.title as job_title,
            j.recruiter_id
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.candidate_id = u.id
        WHERE a.id = ? AND j.recruiter_id = ?
    ");
    $stmt->execute([$application_id, getUserId()]);
    $result = $stmt->fetch();

    if (!$result) {
        http_response_code(404);
        echo json_encode(['error' => 'Application non trouvée']);
        exit;
    }

    // Générer un résumé basique (à améliorer avec une vraie IA si nécessaire)
    $summary = [
        'candidate' => [
            'name' => $result['first_name'] . ' ' . $result['last_name'],
            'email' => $result['email']
        ],
        'job' => [
            'title' => $result['job_title']
        ],
        'summary' => "Résumé du CV de " . $result['first_name'] . " " . $result['last_name'] . " pour le poste de " . $result['job_title']
    ];

    // Renvoyer le résumé en JSON
    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    error_log("Erreur dans generate-cv-summary.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la génération du résumé']);
    exit;
} 