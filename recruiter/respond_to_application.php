<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté et est un recruteur
if (!isLoggedIn() || !isUserType('recruiter')) {
    set_flash_message('error', 'Accès non autorisé.');
    redirect('/public/auth/login.php');
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Méthode non autorisée.');
    redirect('/public/recruiter/applications.php');
    exit;
}

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('error', 'Token CSRF invalide.');
    redirect('/public/recruiter/applications.php');
    exit;
}

// Vérifier les données requises
if (!isset($_POST['application_id']) || !isset($_POST['action']) || !isset($_POST['feedback'])) {
    set_flash_message('error', 'Données manquantes.');
    redirect('/public/recruiter/applications.php');
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $application_id = (int)$_POST['application_id'];
    $action = $_POST['action'];
    $feedback = trim($_POST['feedback']);
    $user_id = getUserId();

    // Vérifier que la candidature existe et appartient à une offre du recruteur
    $stmt = $conn->prepare("
        SELECT a.*, j.title as job_title, j.recruiter_id, a.candidate_id
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ? AND j.recruiter_id = ?
    ");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Candidature non trouvée ou accès non autorisé.');
    }

    // Vérifier que la candidature n'a pas déjà été traitée
    if ($application['status'] !== 'pending' && $application['status'] !== 'reviewed') {
        throw new Exception('Cette candidature a déjà été traitée.');
    }

    // Démarrer la transaction
    $conn->beginTransaction();

    // Mettre à jour le statut et le feedback
    $new_status = $action === 'accept' ? 'accepted' : 'rejected';
    $stmt = $conn->prepare("
        UPDATE applications 
        SET status = ?, 
            feedback = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$new_status, $feedback, $application_id]);

    // Créer une notification pour le candidat
    $notification_message = $new_status === 'accepted' 
        ? "Votre candidature pour le poste \"{$application['job_title']}\" a été acceptée. Consultez les détails pour plus d'informations."
        : "Votre candidature pour le poste \"{$application['job_title']}\" n'a pas été retenue. Consultez les détails pour plus d'informations.";

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, message, link, created_at)
        VALUES (?, 'application_response', ?, ?, NOW())
    ");
    $stmt->execute([
        $application['candidate_id'],
        $notification_message,
        "candidate/view_application.php?id=" . $application_id
    ]);

    // Valider la transaction
    $conn->commit();

    set_flash_message('success', 'Votre réponse a été envoyée avec succès.');

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
}

// Rediriger vers la page de détails de la candidature
redirect("/public/recruiter/view_application.php?id=" . $application_id);
exit;