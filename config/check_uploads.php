<?php
require_once 'config.php';

$upload_dirs = [
    PUBLIC_PATH . '/uploads',
    PUBLIC_PATH . '/uploads/applications',
    PUBLIC_PATH . '/uploads/cv',
    PUBLIC_PATH . '/uploads/profile_pictures'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            echo "Erreur lors de la création du dossier : $dir\n";
            echo "Message d'erreur : " . error_get_last()['message'] . "\n";
        } else {
            echo "Dossier créé : $dir\n";
        }
    }
    
    if (!is_writable($dir)) {
        if (chmod($dir, 0777)) {
            echo "Permissions corrigées pour : $dir\n";
        } else {
            echo "Impossible de modifier les permissions pour : $dir\n";
            echo "Message d'erreur : " . error_get_last()['message'] . "\n";
        }
    } else {
        echo "Les permissions sont correctes pour : $dir\n";
    }
}

echo "Configuration des dossiers d'upload terminée.\n"; 