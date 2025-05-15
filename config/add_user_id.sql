-- Désactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS=0;

-- Supprimer les anciennes contraintes si elles existent
ALTER TABLE applications
DROP FOREIGN KEY IF EXISTS applications_ibfk_1,
DROP FOREIGN KEY IF EXISTS applications_ibfk_2,
DROP FOREIGN KEY IF EXISTS applications_ibfk_3;

ALTER TABLE favorites
DROP FOREIGN KEY IF EXISTS favorites_ibfk_1,
DROP FOREIGN KEY IF EXISTS favorites_ibfk_2,
DROP FOREIGN KEY IF EXISTS favorites_ibfk_3;

-- Ajouter la colonne user_id aux tables qui en ont besoin
ALTER TABLE applications
ADD COLUMN IF NOT EXISTS user_id INT AFTER id;

-- Copier les données de candidate_id vers user_id
UPDATE applications SET user_id = candidate_id;

-- Ajouter la colonne user_id à la table favorites
ALTER TABLE favorites
ADD COLUMN IF NOT EXISTS user_id INT AFTER id;

-- Copier les données de candidate_id vers user_id
UPDATE favorites SET user_id = candidate_id;

-- Ajouter les contraintes de clé étrangère
ALTER TABLE applications
ADD CONSTRAINT applications_ibfk_1 FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
ADD CONSTRAINT applications_ibfk_2 FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE,
ADD CONSTRAINT applications_ibfk_3 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE favorites
ADD CONSTRAINT favorites_ibfk_1 FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
ADD CONSTRAINT favorites_ibfk_2 FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE,
ADD CONSTRAINT favorites_ibfk_3 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Créer la table password_resets si elle n'existe pas
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS=1; 