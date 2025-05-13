<?php
// Configuration de l'application
define('APP_NAME', 'JobPortal');
define('APP_URL', 'http://localhost:8000');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', true); // Activer le mode débogage

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'jobportal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration des chemins
define('ROOT_PATH', dirname(dirname(__DIR__))); // Remonter d'un niveau supplémentaire
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('CV_PATH', UPLOADS_PATH . '/cv');
define('PROFILE_PICTURES_PATH', UPLOADS_PATH . '/profile_pictures');

// Configuration des sessions
define('SESSION_LIFETIME', 3600); // 1 heure
define('SESSION_NAME', 'jobportal_session');

// Configuration des emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@jobportal.com');
define('SMTP_FROM_NAME', 'JobPortal');

// Configuration des fichiers
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png']);

// Configuration des paginations
define('ITEMS_PER_PAGE', 10);

// Configuration des statuts
define('JOB_STATUS', [
    'draft' => 'Brouillon',
    'active' => 'Active',
    'closed' => 'Clôturée'
]);

define('APPLICATION_STATUS', [
    'pending' => 'En attente',
    'reviewed' => 'En cours d\'examen',
    'accepted' => 'Acceptée',
    'rejected' => 'Refusée'
]);

// Configuration des types d'utilisateurs
define('USER_TYPES', [
    'candidate' => 'Candidat',
    'recruiter' => 'Recruteur'
]);

// Configuration des notifications
define('NOTIFICATION_TYPES', [
    'application_status' => 'Statut de candidature',
    'new_message' => 'Nouveau message',
    'job_alert' => 'Alerte emploi',
    'system' => 'Système'
]);

// Configuration des messages d'erreur
define('ERROR_MESSAGES', [
    'login_required' => 'Vous devez être connecté pour accéder à cette page.',
    'permission_denied' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.',
    'invalid_request' => 'Requête invalide.',
    'file_too_large' => 'Le fichier est trop volumineux.',
    'invalid_file_type' => 'Type de fichier non autorisé.',
    'upload_failed' => 'Échec du téléchargement du fichier.',
    'database_error' => 'Une erreur est survenue lors de l\'accès à la base de données.',
    'email_error' => 'Une erreur est survenue lors de l\'envoi de l\'email.'
]);

// Configuration des messages de succès
define('SUCCESS_MESSAGES', [
    'profile_updated' => 'Votre profil a été mis à jour avec succès.',
    'job_created' => 'L\'offre d\'emploi a été créée avec succès.',
    'job_updated' => 'L\'offre d\'emploi a été mise à jour avec succès.',
    'job_deleted' => 'L\'offre d\'emploi a été supprimée avec succès.',
    'application_submitted' => 'Votre candidature a été soumise avec succès.',
    'application_updated' => 'Le statut de la candidature a été mis à jour avec succès.',
    'message_sent' => 'Votre message a été envoyé avec succès.'
]);

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les classes nécessaires
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php'; 