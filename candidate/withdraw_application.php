<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || $_SESSION['user_type'] !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['application_id'])) {
    header('Location: applications.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_POST['application_id'];

try {
    // Vérifier que la candidature appartient bien au candidat et est en attente
    $stmt = $conn->prepare("
        SELECT a.* FROM applications a
        WHERE a.id = ? AND a.candidate_id = ? AND a.status = 'pending'
    ");
    $stmt->execute([$application_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Candidature non trouvée ou impossible à retirer.");
    }

    // Supprimer la candidature
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);

    $_SESSION['success_message'] = "Votre candidature a été retirée avec succès.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors du retrait de la candidature : " . $e->getMessage();
}

header('Location: applications.php');
exit(); 