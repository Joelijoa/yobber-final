<?php
// Vérifier si la session n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    // Configuration de la session (doit être fait avant session_start)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
}

// Définition du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Inclusion des fichiers nécessaires dans l'ordre
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/auth.php';

// Vérification de l'expiration de la session
if (isLoggedIn()) {
    checkSessionExpiration();
}

// Configurer le gestionnaire d'erreurs
function error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $error_message = sprintf(
        "Erreur [%d] : %s\nFichier : %s\nLigne : %d",
        $errno,
        $errstr,
        $errfile,
        $errline
    );

    error_log($error_message);

    if (ini_get('display_errors')) {
        echo '<div class="alert alert-danger" style="margin: 20px;">';
        echo '<strong>Erreur :</strong> Une erreur est survenue. Veuillez réessayer plus tard.';
        echo '</div>';
    }

    return true;
}

// Configurer le gestionnaire d'exceptions
function exception_handler($exception) {
    $error_message = sprintf(
        "Exception : %s\nFichier : %s\nLigne : %d\nTrace :\n%s",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    error_log($error_message);

    if (ini_get('display_errors')) {
        echo '<div class="alert alert-danger" style="margin: 20px;">';
        echo '<strong>Erreur :</strong> Une erreur est survenue. Veuillez réessayer plus tard.';
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo '<br><small>' . htmlspecialchars($exception->getMessage()) . '</small>';
        }
        echo '</div>';
    }
}

set_error_handler('error_handler');
set_exception_handler('exception_handler');

// Créer les répertoires nécessaires s'ils n'existent pas
$directories = [
    UPLOADS_PATH,
    CV_PATH,
    PROFILE_PICTURES_PATH
];

foreach ($directories as $directory) {
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

// Vérifier la connexion à la base de données
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données.");
}

// Fonction pour vérifier le token CSRF
function checkCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ne pas vérifier le token CSRF pour les uploads de fichiers
        if (!empty($_FILES)) {
            return;
        }
        
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            set_flash_message('error', get_error_message('invalid_request'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
        }
    }
}

// Fonction pour nettoyer les données
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return clean_input($data);
}

// Nettoyer les données POST et GET
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);

// Vérifier le token CSRF pour les requêtes POST
checkCsrfToken(); 