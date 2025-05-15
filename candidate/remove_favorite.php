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
    // Vérifier si le favori existe et appartient à l'utilisateur
    $stmt = $conn->prepare("
        SELECT id 
        FROM favorites 
        WHERE job_id = ? AND user_id = ?
    ");
    $stmt->execute([$job_id, $user_id]);

    if ($stmt->fetch()) {
        // Supprimer le favori
        $stmt = $conn->prepare("DELETE FROM favorites WHERE job_id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);
        
        set_flash_message('success', 'L\'offre a été retirée de vos favoris.');
    } else {
        set_flash_message('error', 'Cette offre n\'est pas dans vos favoris.');
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression du favori : " . $e->getMessage();
}

header('Location: favorites.php');
exit(); 