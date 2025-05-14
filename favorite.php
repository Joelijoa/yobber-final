<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
requireAccess('candidate');

// Vérifier si la requête est de type POST et si l'ID du job est fourni
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

$user_id = getUserId();
$job_id = (int)$_POST['job_id'];

try {
    // Vérifier si l'offre existe
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Cette offre n\'existe pas.');
    }

    // Vérifier si l'offre est déjà en favoris
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([$job_id, $user_id]);
    $favorite = $stmt->fetch();

    if ($favorite) {
        // Supprimer des favoris
        $stmt = $conn->prepare("DELETE FROM favorites WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$job_id, $user_id]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Ajouter aux favoris
        $stmt = $conn->prepare("INSERT INTO favorites (job_id, candidate_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$job_id, $user_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 