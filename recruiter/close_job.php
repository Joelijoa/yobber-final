<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    set_flash_message('error', 'Accès non autorisé.');
    redirect('/auth/login.php');
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Méthode non autorisée.');
    redirect('/recruiter/jobs.php');
    exit;
}

try {
    // Initialiser la connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();
    
    $user_id = getUserId();

    // Vérifier le CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        throw new Exception('Token CSRF invalide.');
    }

    // Vérifier si l'ID de l'offre est fourni
    if (!isset($_POST['job_id']) || !is_numeric($_POST['job_id'])) {
        throw new Exception('ID de l\'offre invalide.');
    }

    $job_id = (int)$_POST['job_id'];

    // Vérifier que l'offre appartient bien au recruteur
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM jobs 
        WHERE id = :job_id AND recruiter_id = :user_id
    ");
    $stmt->execute([
        'job_id' => $job_id,
        'user_id' => $user_id
    ]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        throw new Exception('Offre non trouvée ou accès non autorisé.');
    }

    if ($job['status'] === 'closed') {
        throw new Exception('Cette offre est déjà clôturée.');
    }

    // Mettre à jour le statut de l'offre
    $stmt = $conn->prepare("
        UPDATE jobs 
        SET status = 'closed', 
            updated_at = NOW() 
        WHERE id = :job_id AND recruiter_id = :user_id
    ");
    $stmt->execute([
        'job_id' => $job_id,
        'user_id' => $user_id
    ]);

    set_flash_message('success', 'L\'offre a été clôturée avec succès.');

} catch (Exception $e) {
    set_flash_message('error', 'Erreur lors de la clôture de l\'offre : ' . $e->getMessage());
}

// Rediriger vers la page des offres
redirect('/recruiter/jobs.php');
exit;
?> 