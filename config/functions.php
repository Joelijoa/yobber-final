<?php
// Fonction pour obtenir le chemin absolu
function get_path($path) {
    return ROOT_PATH . '/' . ltrim($path, '/');
}

// Fonction pour obtenir l'URL absolue
function get_url($path) {
    return APP_URL . '/' . ltrim($path, '/');
}

// Fonction pour obtenir l'URL courante
function get_current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . parse_url($uri, PHP_URL_PATH);
}

// Fonction pour obtenir le message d'erreur
function get_error_message($key) {
    return ERROR_MESSAGES[$key] ?? 'Une erreur est survenue.';
}

// Fonction pour obtenir le message de succès
function get_success_message($key) {
    return SUCCESS_MESSAGES[$key] ?? 'Opération réussie.';
}

// Fonction pour vérifier si le fichier est autorisé
function is_allowed_file($filename, $type = 'file') {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($type === 'image') {
        return in_array($extension, ALLOWED_IMAGE_TYPES);
    }
    return in_array($extension, ALLOWED_FILE_TYPES);
}

// Fonction pour formater la taille du fichier
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Fonction pour nettoyer les entrées
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour générer un token CSRF
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Fonction pour rediriger
function redirect($path) {
    if (strpos($path, 'http') !== 0) {
        $path = '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit();
}

// Fonction pour afficher un message flash
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fonction pour récupérer et supprimer le message flash
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Convertit une chaîne de taille de fichier (comme '2M') en bytes
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
} 