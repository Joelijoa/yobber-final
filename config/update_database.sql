-- Supprimer les contraintes existantes
SET FOREIGN_KEY_CHECKS=0;

-- Mise à jour de la table applications
ALTER TABLE applications
DROP FOREIGN KEY IF EXISTS applications_ibfk_1,
DROP FOREIGN KEY IF EXISTS applications_ibfk_2;

-- Renommer la colonne user_id en candidate_id si elle existe
ALTER TABLE applications 
CHANGE COLUMN user_id candidate_id INT NOT NULL;

-- Ajouter les nouvelles contraintes
ALTER TABLE applications
ADD CONSTRAINT applications_ibfk_1 FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
ADD CONSTRAINT applications_ibfk_2 FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE;

-- Mise à jour de la table favorites
ALTER TABLE favorites
DROP FOREIGN KEY IF EXISTS favorites_ibfk_1,
DROP FOREIGN KEY IF EXISTS favorites_ibfk_2;

-- Renommer la colonne user_id en candidate_id si elle existe
ALTER TABLE favorites
CHANGE COLUMN user_id candidate_id INT NOT NULL;

-- Ajouter les nouvelles contraintes
ALTER TABLE favorites
ADD CONSTRAINT favorites_ibfk_1 FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
ADD CONSTRAINT favorites_ibfk_2 FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS=1; 
ALTER TABLE favorites DROP COLUMN IF EXISTS user_id; 