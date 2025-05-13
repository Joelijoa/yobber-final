<?php
session_start();

// Suppression du cookie "remember me" s'il existe
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destruction de la session
session_destroy();

// Redirection vers la page d'accueil
header('Location: /');
exit; 