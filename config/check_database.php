<?php
require_once 'config.php';
require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Vérifier si la table users existe
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        echo "Création de la table users...\n";
        $conn->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                type ENUM('candidate', 'recruiter', 'admin') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }

    // Vérifier si la table applications existe
    $stmt = $conn->query("SHOW TABLES LIKE 'applications'");
    if (!$stmt->fetch()) {
        echo "Création de la table applications...\n";
        $conn->exec("
            CREATE TABLE applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_id INT NOT NULL,
                candidate_id INT NOT NULL,
                cv_path VARCHAR(255) NOT NULL,
                cover_letter_path VARCHAR(255) NOT NULL,
                message TEXT,
                status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
                feedback TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
                FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    // Vérifier si la table notifications existe
    $stmt = $conn->query("SHOW TABLES LIKE 'notifications'");
    if (!$stmt->fetch()) {
        echo "Création de la table notifications...\n";
        $conn->exec("
            CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    // Vérifier si la table jobs existe
    $stmt = $conn->query("SHOW TABLES LIKE 'jobs'");
    if (!$stmt->fetch()) {
        echo "Création de la table jobs...\n";
        $conn->exec("
            CREATE TABLE jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recruiter_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                requirements TEXT NOT NULL,
                location VARCHAR(255) NOT NULL,
                type ENUM('full-time', 'part-time', 'contract', 'internship') NOT NULL,
                salary_min DECIMAL(10,2),
                salary_max DECIMAL(10,2),
                benefits TEXT,
                status ENUM('draft', 'active', 'closed') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    echo "Vérification de la base de données terminée.\n";
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 