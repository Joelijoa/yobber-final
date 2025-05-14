USE jobportal;

-- Désactiver les contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 0;

-- Supprimer la table jobs si elle existe
DROP TABLE IF EXISTS jobs;

-- Créer la table jobs avec la bonne structure
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    type ENUM('CDI', 'CDD', 'Freelance', 'Stage', 'Alternance') NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    salary VARCHAR(100),
    benefits TEXT,
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Réactiver les contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1; 