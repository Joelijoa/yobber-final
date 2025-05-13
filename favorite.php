<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/config/database.php';

// Empêcher toute sortie avant les en-têtes
ob_start();

// Définir l'en-tête Content-Type
header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || !isUserType('candidate')) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Accès non autorisé'
    ]);
    exit;
}

// Vérifier si l'ID de l'offre est fourni
if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'ID de l\'offre invalide'
    ]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $job_id = intval($_POST['job_id']);
    $user_id = getUserId();

    // Vérifier si l'offre existe
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    if (!$stmt->fetch()) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Cette offre n\'existe pas'
        ]);
        exit;
    }

    // Vérifier si l'offre est déjà dans les favoris
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([$job_id, $user_id]);
    $favorite = $stmt->fetch();

    ob_end_clean();
    
    if ($favorite) {
        // Supprimer des favoris
        $stmt = $conn->prepare("DELETE FROM favorites WHERE job_id = ? AND candidate_id = ?");
        $stmt->execute([$job_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'isFavorite' => false,
            'message' => 'Offre retirée des favoris'
        ]);
    } else {
        // Ajouter aux favoris
        $stmt = $conn->prepare("INSERT INTO favorites (job_id, candidate_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$job_id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'isFavorite' => true,
            'message' => 'Offre ajoutée aux favoris'
        ]);
    }
} catch (PDOException $e) {
    ob_end_clean();
    error_log('Erreur favorite.php : ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la mise à jour des favoris'
    ]);
}
?> 