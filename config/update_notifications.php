<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Ajouter la colonne link si elle n'existe pas
    $conn->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS link VARCHAR(255) NULL AFTER message");
    
    // Vérifier si la colonne is_read existe
    $stmt = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
    if ($stmt->fetch()) {
        // Supprimer l'ancienne colonne is_read
        $conn->exec("ALTER TABLE notifications DROP COLUMN is_read");
    }
    
    // Ajouter la colonne read_at si elle n'existe pas
    $conn->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS read_at TIMESTAMP NULL");

    echo "La table notifications a été mise à jour avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la table notifications : " . $e->getMessage() . "\n";
} 