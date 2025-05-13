<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id'])) {
    header('Location: jobs.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = $_POST['job_id'];

try {
    // Vérifier que l'offre appartient bien au recruteur
    $stmt = $conn->prepare("
        SELECT * FROM jobs 
        WHERE id = ? AND recruiter_id = ? AND status = 'active'
    ");
    $stmt->execute([$job_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Offre non trouvée ou impossible à fermer.");
    }

    // Fermer l'offre
    $stmt = $conn->prepare("
        UPDATE jobs 
        SET status = 'closed', updated_at = NOW() 
        WHERE id = ? AND recruiter_id = ?
    ");
    $stmt->execute([$job_id, $user_id]);

    $_SESSION['success_message'] = "L'offre a été fermée avec succès.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la fermeture de l'offre : " . $e->getMessage();
}

header('Location: jobs.php');
exit(); 