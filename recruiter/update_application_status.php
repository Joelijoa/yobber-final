<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: ../login.php');
    exit();
}

// Vérifier si la requête est en POST et si les données nécessaires sont présentes
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['application_id']) || !isset($_POST['status'])) {
    header('Location: applications.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_POST['application_id'];
$status = $_POST['status'];
$feedback = $_POST['feedback'] ?? null;

try {
    // Vérifier que la candidature appartient à une offre du recruteur
    $stmt = $conn->prepare("
        SELECT a.id, a.candidate_id, j.title as job_title
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ? AND j.recruiter_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception("Candidature non trouvée ou non autorisée.");
    }

    // Mettre à jour le statut et le feedback
    $stmt = $conn->prepare("
        UPDATE applications 
        SET status = ?, feedback = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$status, $feedback, $application_id]);

    // Créer une notification pour le candidat
    $notification_type = 'application_status';
    $notification_title = 'Mise à jour de votre candidature';
    $notification_message = "Le statut de votre candidature pour le poste de {$application['job_title']} a été mis à jour : " . 
        match($status) {
            'pending' => 'En attente',
            'reviewed' => 'En cours d\'examen',
            'accepted' => 'Acceptée',
            'rejected' => 'Refusée',
            default => $status
        };

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, title, message, created_at)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$application['candidate_id'], $notification_type, $notification_title, $notification_message]);

    $_SESSION['success_message'] = "Le statut de la candidature a été mis à jour avec succès.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
}

header('Location: applications.php');
exit(); 