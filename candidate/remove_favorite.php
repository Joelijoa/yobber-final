<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || $_SESSION['user_type'] !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['job_id'])) {
    header('Location: favorites.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = $_POST['job_id'];

try {
    // Vérifier que le favori appartient bien au candidat
    $stmt = $conn->prepare("
        SELECT * FROM favorites 
        WHERE job_id = ? AND candidate_id = ?
    ");
    $stmt->execute([$job_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Favori non trouvé.");
    }

    // Supprimer le favori
    $stmt = $conn->prepare("DELETE FROM favorites WHERE job_id = ? AND candidate_id = ?");
    $stmt->execute([$job_id, $user_id]);

    $_SESSION['success_message'] = "L'offre a été retirée de vos favoris.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression du favori : " . $e->getMessage();
}

header('Location: favorites.php');
exit(); 