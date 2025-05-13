<?php
// Chemin de base de l'application
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}

// URL de base (à adapter selon le déploiement)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

// Autres constantes globales utiles
// define('APP_NAME', 'JobPortal');
// define('APP_ENV', 'development'); 