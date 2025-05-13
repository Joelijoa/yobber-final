<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/auth.php';

// Définir le chemin de base s'il n'est pas déjà défini
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'JobPortal'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/index.php">
                <i class="fas fa-briefcase text-primary me-2"></i>JobPortal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/jobs.php">
                            <i class="fas fa-search me-1"></i>Offres
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">
                            <i class="fas fa-info-circle me-1"></i>À propos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact.php">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>
                </ul>

                <?php if (isLoggedIn()): ?>
                    <!-- Menu utilisateur connecté -->
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" type="button" id="userMenu" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars(getCurrentUser()['first_name'] . ' ' . getCurrentUser()['last_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isUserType('candidate')): ?>
                                <li><a class="dropdown-item" href="../candidate/dashboard.php">Tableau de bord</a></li>
                                <li><a class="dropdown-item" href="../candidate/profile.php">Mon profil</a></li>
                                <li><a class="dropdown-item" href="../candidate/applications">Mes candidatures</a></li>
                                <li><a class="dropdown-item" href="../candidate/favorites.php">Favoris</a></li>
                            <?php elseif (isUserType('recruiter')): ?>
                                <li><a class="dropdown-item" href="../recruiter/dashboard.php">Tableau de bord</a></li>
                                <li><a class="dropdown-item" href="../recruiter/profile/">Profil entreprise</a></li>
                                <li><a class="dropdown-item" href="../recruiter/jobs/">Mes offres</a></li>
                                <li><a class="dropdown-item" href="../recruiter/applications/">Candidatures</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/auth/logout.php">Déconnexion</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Boutons de connexion/inscription -->
                    <div class="d-flex gap-2">
                        <a href="/auth/login.php" class="btn btn-outline-primary">Connexion</a>
                        <a href="/auth/register.php" class="btn btn-primary">Inscription</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="container mt-5 pt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php 
                if (is_array($_SESSION['flash_message'])) {
                    echo '<ul class="mb-0">';
                    foreach ($_SESSION['flash_message'] as $message) {
                        echo '<li>' . htmlspecialchars($message) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo htmlspecialchars($_SESSION['flash_message']);
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?> 