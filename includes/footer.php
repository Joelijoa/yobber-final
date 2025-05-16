    </main>

    <!-- Footer -->
    <footer class="bg-white border-top mt-auto py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="mb-4">
                    <i class="fa-solid fa-person-walking-luggage"></i> Yobber
                    </h5>
                    <p class="text-muted mb-4">
                        Trouvez votre prochain emploi ou le candidat idéal pour votre entreprise avec Yobber, 
                        la plateforme de recrutement innovante.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted hover-primary" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-muted hover-primary" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-muted hover-primary" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-muted hover-primary" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="mb-4">Navigation</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="/public/index.php" class="text-muted text-decoration-none hover-primary">Accueil</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/jobs.php" class="text-muted text-decoration-none hover-primary">Offres d'emploi</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/about.php" class="text-muted text-decoration-none hover-primary">À propos</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/contact.php" class="text-muted text-decoration-none hover-primary">Contact</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="mb-4">Pour les candidats</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="/public/register.php?type=candidate" class="text-muted text-decoration-none hover-primary">Créer un compte</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/candidate/profile.php" class="text-muted text-decoration-none hover-primary">Mon profil</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/candidate/favorites.php" class="text-muted text-decoration-none hover-primary">Mes favoris</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/candidate/applications.php" class="text-muted text-decoration-none hover-primary">Mes candidatures</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="mb-4">Pour les recruteurs</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="/public/register.php?type=recruiter" class="text-muted text-decoration-none hover-primary">Créer un compte</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/recruiter/profile.php" class="text-muted text-decoration-none hover-primary">Mon entreprise</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/recruiter/jobs.php" class="text-muted text-decoration-none hover-primary">Gérer mes offres</a>
                        </li>
                        <li class="mb-2">
                            <a href="/public/recruiter/applications.php" class="text-muted text-decoration-none hover-primary">Candidatures reçues</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="mb-4">Contact</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-muted">
                            <i class="fas fa-map-marker-alt me-2"></i>Paris, France
                        </li>
                        <li class="mb-2">
                            <a href="mailto:contact@yobber.com" class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-envelope me-2"></i>contact@yobber.com
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="tel:+33123456789" class="text-muted text-decoration-none hover-primary">
                                <i class="fas fa-phone me-2"></i>+33 1 23 45 67 89
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted mb-0">
                        © <?php echo date('Y'); ?> Yobber. Tous droits réservés.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="/public/privacy.php" class="text-muted text-decoration-none hover-primary">Confidentialité</a>
                        </li>
                        <li class="list-inline-item ms-3">
                            <a href="/public/terms.php" class="text-muted text-decoration-none hover-primary">Conditions d'utilisation</a>
                        </li>
                        <li class="list-inline-item ms-3">
                            <a href="/public/cookies.php" class="text-muted text-decoration-none hover-primary">Cookies</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
    .hover-primary {
        transition: color 0.3s ease;
    }
    .hover-primary:hover {
        color: var(--primary-color) !important;
    }
    footer {
        font-size: 0.9rem;
    }
    </style>
</body>
</html> 