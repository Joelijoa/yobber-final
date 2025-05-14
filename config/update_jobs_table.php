<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Lire et exécuter le fichier SQL
    $sql = file_get_contents(__DIR__ . '/update_jobs_table.sql');
    
    $conn->exec($sql);
    echo "La table jobs a été mise à jour avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la table : " . $e->getMessage() . "\n";
} 