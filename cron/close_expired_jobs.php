<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    // Initialiser la connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();

    // Récupérer et mettre à jour les offres expirées
    $stmt = $conn->prepare("
        UPDATE jobs 
        SET status = 'closed', 
            updated_at = NOW() 
        WHERE status = 'active' 
        AND expiry_date IS NOT NULL 
        AND expiry_date < CURDATE()
    ");
    
    $stmt->execute();
    
    $affected_rows = $stmt->rowCount();
    
    // Log le résultat
    $log_message = date('Y-m-d H:i:s') . " - " . $affected_rows . " offre(s) clôturée(s) automatiquement\n";
    file_put_contents(__DIR__ . '/../../logs/job_closure.log', $log_message, FILE_APPEND);
    
    echo "Succès : " . $affected_rows . " offre(s) clôturée(s)";

} catch (Exception $e) {
    // Log l'erreur
    $error_message = date('Y-m-d H:i:s') . " - Erreur : " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../../logs/job_closure.log', $error_message, FILE_APPEND);
    
    echo "Erreur : " . $e->getMessage();
}
?> 