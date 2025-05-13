<?php
// Fonctions d'authentification
// Fonction pour obtenir l'ID de l'utilisateur connecté
function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Fonction pour obtenir le type d'utilisateur
function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier le type d'utilisateur
function isUserType($type) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $type;
}

// Fonction pour connecter un utilisateur
function loginUser($user_id, $user_type) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['last_activity'] = time();
}

// Fonction pour déconnecter un utilisateur
function logoutUser() {
    session_unset();
    session_destroy();
    session_start();
}

// Fonction pour vérifier si la session a expiré
function checkSessionExpiration() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        logoutUser();
        set_flash_message('error', 'Votre session a expiré. Veuillez vous reconnecter.');
        redirect('/auth/login.php');
    }
    $_SESSION['last_activity'] = time();
}

// Fonction pour vérifier les permissions
function checkPermission($required_type) {
    if (!isLoggedIn()) {
        set_flash_message('error', get_error_message('login_required'));
        redirect('/auth/login.php');
    }

    if (!isUserType($required_type)) {
        set_flash_message('error', get_error_message('permission_denied'));
        redirect('/auth/login.php');
    }
}

// Fonction pour obtenir les informations de l'utilisateur connecté
function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des informations utilisateur : " . $e->getMessage());
        return null;
    }
}

// Fonction pour vérifier si l'utilisateur a accès à une ressource
function hasAccess($resource_id, $resource_type) {
    global $conn;
    if (!isLoggedIn()) {
        return false;
    }

    try {
        switch ($resource_type) {
            case 'job':
                $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ? AND recruiter_id = ?");
                break;
            case 'application':
                $stmt = $conn->prepare("
                    SELECT a.id 
                    FROM applications a 
                    JOIN jobs j ON a.job_id = j.id 
                    WHERE a.id = ? AND (a.candidate_id = ? OR j.recruiter_id = ?)
                ");
                $stmt->execute([$resource_id, $_SESSION['user_id'], $_SESSION['user_id']]);
                break;
            default:
                return false;
        }

        $stmt->execute([$resource_id, $_SESSION['user_id']]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification des permissions : " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier l'accès et rediriger si nécessaire
function requireAccess($required_type, $redirect_path = '/auth/login.php') {
    if (!isLoggedIn() || $_SESSION['user_type'] !== $required_type) {
        header('Location: ' . $redirect_path);
        exit();
    }
}

// Vérifier l'expiration de la session
checkSessionExpiration(); 