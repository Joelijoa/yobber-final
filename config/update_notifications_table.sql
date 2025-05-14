USE jobportal;

-- Ajouter la colonne link
ALTER TABLE notifications
ADD COLUMN IF NOT EXISTS link VARCHAR(255) NULL AFTER message;

-- Remplacer is_read par read_at
ALTER TABLE notifications
DROP COLUMN IF EXISTS is_read,
ADD COLUMN IF NOT EXISTS read_at TIMESTAMP NULL; 