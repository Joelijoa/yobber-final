<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Vérifier que l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['application_id']) || !isset($data['summary'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    // Récupérer les informations de la candidature pour le titre
    $stmt = $conn->prepare("
        SELECT 
            j.title as job_title,
            u.first_name,
            u.last_name
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.candidate_id = u.id
        WHERE a.id = ? AND j.recruiter_id = ?
        LIMIT 1
    ");
    $stmt->execute([$data['application_id'], getUserId()]);
    $application = $stmt->fetch();

    if (!$application) {
        throw new Exception('Candidature non trouvée');
    }

    // Préparer le HTML pour le PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Résumé CV - ' . htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) . '</title>
        <style>
            body {
                font-family: "Helvetica", "Arial", sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 2cm;
            }
            h1 {
                color: #2c3e50;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            h4 {
                color: #2980b9;
                margin-top: 20px;
                margin-bottom: 10px;
            }
            .cv-summary-section {
                margin-bottom: 20px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #7f8c8d;
                margin-top: 30px;
                border-top: 1px solid #bdc3c7;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Résumé du CV</h1>
            <p><strong>Poste : ' . htmlspecialchars($application['job_title']) . '</strong></p>
            <p><strong>Candidat : ' . htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) . '</strong></p>
        </div>
        
        ' . $data['summary'] . '
        
        <div class="footer">
            <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
        </div>
    </body>
    </html>
    ';

    // Configurer DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('isRemoteEnabled', true);

    // Créer le PDF
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // En-têtes pour le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="resume_cv_' . $data['application_id'] . '.pdf"');
    
    echo $dompdf->output();

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 