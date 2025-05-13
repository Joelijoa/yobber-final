<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Lire et exécuter le fichier SQL
    $sql = file_get_contents(__DIR__ . '/create_applications_table.sql');
    
    $conn->exec($sql);
    echo "La table applications a été recréée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la recréation de la table : " . $e->getMessage() . "\n";
} 