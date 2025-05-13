<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Lire et exécuter le fichier SQL
    $sql = file_get_contents(__DIR__ . '/create_notifications_table.sql');
    
    $conn->exec($sql);
    echo "La table notifications a été créée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table : " . $e->getMessage() . "\n";
} 