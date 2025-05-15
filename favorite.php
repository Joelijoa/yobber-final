<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
requireAccess('candidate');

// Vérifier si la requête est de type POST et si l'ID du job est fourni
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

$candidate_id = getUserId();
$job_id = (int)$_POST['job_id'];

try {
    // Initialiser la connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Vérifier si l'offre existe
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Cette offre n\'existe pas.');
    }

    // Vérifier si l'offre est déjà en favoris
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([$job_id, $candidate_id]);

    $response = ['success' => true];

    if ($stmt->fetch()) {
        // Si déjà en favoris, supprimer
        $stmt = $conn->prepare("DELETE FROM favorites WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$job_id, $candidate_id]);
        $response['action'] = 'removed';
        $response['message'] = 'Offre retirée des favoris';
    } else {
        // Sinon, ajouter aux favoris
        $stmt = $conn->prepare("INSERT INTO favorites (job_id, candidate_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$job_id, $candidate_id]);
        $response['action'] = 'added';
        $response['message'] = 'Offre ajoutée aux favoris';
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue : ' . $e->getMessage()
    ]);
}
?> 