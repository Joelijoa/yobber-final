USE jobportal;

-- Ajouter les colonnes manquantes à la table applications
ALTER TABLE applications
ADD COLUMN cv_path VARCHAR(255) AFTER candidate_id,
ADD COLUMN cover_letter_path VARCHAR(255) AFTER cv_path,
ADD COLUMN message TEXT AFTER cover_letter_path; 