<?php
$page_title = "À propos - JobPortal";
require_once './includes/header.php';
?>

<!-- About Hero Section -->
<section class="about-hero py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">À propos de JobPortal</h1>
                <p class="lead mb-4">Votre partenaire de confiance pour la recherche d'emploi et le recrutement.</p>
            </div>
            <div class="col-lg-6">
                <img src="../assets/images/about-hero.svg" alt="About JobPortal" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="mission-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="mission-card text-center p-4">
                    <i class="fas fa-bullseye fa-3x text-primary mb-4"></i>
                    <h3>Notre Mission</h3>
                    <p>Faciliter la mise en relation entre les talents et les entreprises en offrant une plateforme innovante et efficace.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mission-card text-center p-4">
                    <i class="fas fa-eye fa-3x text-primary mb-4"></i>
                    <h3>Notre Vision</h3>
                    <p>Devenir la référence en matière de recrutement en ligne, en offrant une expérience utilisateur exceptionnelle.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mission-card text-center p-4">
                    <i class="fas fa-heart fa-3x text-primary mb-4"></i>
                    <h3>Nos Valeurs</h3>
                    <p>Innovation, transparence, efficacité et satisfaction client sont au cœur de notre démarche.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us py-5">
    <div class="container">
        <h2 class="text-center mb-5">Pourquoi nous choisir ?</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="feature-item d-flex align-items-start">
                    <div class="feature-icon me-3">
                        <i class="fas fa-check-circle text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h4>Interface Intuitive</h4>
                        <p class="text-muted">Une plateforme facile à utiliser, que vous soyez candidat ou recruteur.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-item d-flex align-items-start">
                    <div class="feature-icon me-3">
                        <i class="fas fa-shield-alt text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h4>Sécurité Garantie</h4>
                        <p class="text-muted">Vos données sont protégées et sécurisées selon les normes les plus strictes.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-item d-flex align-items-start">
                    <div class="feature-icon me-3">
                        <i class="fas fa-chart-line text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h4>Suivi en Temps Réel</h4>
                        <p class="text-muted">Suivez l'état de vos candidatures et recevez des notifications instantanées.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-item d-flex align-items-start">
                    <div class="feature-icon me-3">
                        <i class="fas fa-users text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h4>Large Réseau</h4>
                        <p class="text-muted">Accédez à un vaste réseau de professionnels et d'entreprises.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Notre Équipe</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="team-card text-center">
                    <img src="../assets/images/team/member1.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4>Jean Dupont</h4>
                    <p class="text-muted">Fondateur & CEO</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card text-center">
                    <img src="../assets/images/team/member2.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4>Marie Martin</h4>
                    <p class="text-muted">Directrice Marketing</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card text-center">
                    <img src="../assets/images/team/member3.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4>Pierre Durand</h4>
                    <p class="text-muted">Directeur Technique</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact CTA -->
<section class="contact-cta py-5">
    <div class="container text-center">
        <h2 class="mb-4">Vous avez des questions ?</h2>
        <p class="lead mb-4">Notre équipe est là pour vous aider.</p>
        <a href="contact.php" class="btn btn-primary btn-lg">Contactez-nous</a>
    </div>
</section>

<style>
.about-hero {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 100px 0;
    margin-top: -1.5rem;
}

.mission-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    height: 100%;
    transition: transform 0.3s ease;
}

.mission-card:hover {
    transform: translateY(-5px);
}

.feature-item {
    padding: 1.5rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    height: 100%;
}

.team-card {
    padding: 2rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.team-card:hover {
    transform: translateY(-5px);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>