<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Ajouter les colonnes manquantes à la table applications
    $sql = "
        ALTER TABLE applications
        ADD COLUMN IF NOT EXISTS cv_path VARCHAR(255) AFTER candidate_id,
        ADD COLUMN IF NOT EXISTS cover_letter_path VARCHAR(255) AFTER cv_path,
        ADD COLUMN IF NOT EXISTS message TEXT AFTER cover_letter_path
    ";
    
    $conn->exec($sql);
    echo "La table applications a été mise à jour avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la base de données : " . $e->getMessage() . "\n";
} 