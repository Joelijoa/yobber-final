-- Désactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS=0;

-- Ajouter la colonne read_at à la table notifications
ALTER TABLE notifications
ADD COLUMN IF NOT EXISTS read_at TIMESTAMP NULL DEFAULT NULL AFTER is_read;

-- Mettre à jour read_at en fonction de is_read
UPDATE notifications 
SET read_at = CASE 
    WHEN is_read = 1 THEN created_at 
    ELSE NULL 
END;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS=1; 