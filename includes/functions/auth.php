<?php
/**
 * Fonctions d'aide pour la gestion de l'authentification
 */

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est un candidat
 * @return bool
 */
function isCandidate() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'candidate';
}

/**
 * Vérifie si l'utilisateur est un recruteur
 * @return bool
 */
function isRecruiter() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'recruiter';
}

/**
 * Vérifie si l'utilisateur est un administrateur
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Récupère le type d'utilisateur
 * @return string|null
 */
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Récupère l'ID de l'utilisateur
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Récupère le nom de l'utilisateur
 * @return string|null
 */
function getUserName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Récupère l'email de l'utilisateur
 * @return string|null
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Vérifie si l'utilisateur a accès à une page
 * @param string|array $allowed_types Types d'utilisateurs autorisés
 * @return bool
 */
function hasAccess($allowed_types) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($allowed_types)) {
        return in_array(getUserType(), $allowed_types);
    }
    
    return getUserType() === $allowed_types;
}

/**
 * Redirige l'utilisateur s'il n'a pas accès à une page
 * @param string|array $allowed_types Types d'utilisateurs autorisés
 * @param string $redirect_url URL de redirection
 */
function requireAccess($allowed_types, $redirect_url = '/public/index.php') {
    if (!hasAccess($allowed_types)) {
        header('Location: ' . BASE_PATH . $redirect_url);
        exit;
    }
}

/**
 * Vérifie le token "remember me"
 * @return bool
 */
function checkRememberToken() {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // Vérification du token
    $query = "SELECT u.* FROM users u 
              INNER JOIN remember_tokens rt ON u.id = rt.user_id 
              WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Connexion automatique
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Mise à jour du token
        $new_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $query = "UPDATE remember_tokens SET token = ?, expires_at = ? WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$new_token, $expires_at, $user['id']]);
        
        setcookie('remember_token', $new_token, strtotime('+30 days'), '/', '', true, true);
        
        return true;
    }
    
    return false;
}

/**
 * Génère un token CSRF
 * @return string
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 * @param string $token Token à vérifier
 * @return bool
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie si la requête est une requête AJAX
 * @return bool
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Envoie une réponse JSON
 * @param mixed $data Données à envoyer
 * @param int $status_code Code de statut HTTP
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Envoie une réponse d'erreur JSON
 * @param string $message Message d'erreur
 * @param int $status_code Code de statut HTTP
 */
function sendJsonError($message, $status_code = 400) {
    sendJsonResponse(['error' => $message], $status_code);
}

/**
 * Envoie une réponse de succès JSON
 * @param mixed $data Données à envoyer
 * @param string $message Message de succès
 */
function sendJsonSuccess($data = null, $message = 'Opération réussie') {
    sendJsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
} 