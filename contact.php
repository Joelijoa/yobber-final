<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';

$page_title = "Contact - JobPortal";

// Traitement du formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Le nom est requis";
    }
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    if (empty($subject)) {
        $errors[] = "Le sujet est requis";
    }
    if (empty($message)) {
        $errors[] = "Le message est requis";
    }
    
    if (empty($errors)) {
        // Envoi de l'email (à implémenter)
        $to = "contact@jobportal.com";
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $email_body = "
            <h2>Nouveau message de contact</h2>
            <p><strong>Nom:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Sujet:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";
        
        if (mail($to, "Contact JobPortal: $subject", $email_body, $headers)) {
            $_SESSION['flash_message'] = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            $_SESSION['flash_type'] = "success";
            header("Location: contact.php");
            exit;
        } else {
            $errors[] = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
        }
    }
}
?>

<!-- Contact Hero Section -->
<section class="contact-hero py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Contactez-nous</h1>
                <p class="lead mb-4">Nous sommes là pour vous aider. N'hésitez pas à nous contacter pour toute question.</p>
            </div>
            <div class="col-lg-6">
                <img src="../assets/images/contact-hero.svg" alt="Contact Us" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section class="contact-form-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-4">Envoyez-nous un message</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="contact.php" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nom complet</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">Sujet</label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="contact-info">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h3 class="h5 mb-4">Informations de contact</h3>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    123 Rue de l'Emploi, 75000 Paris
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-phone text-primary me-2"></i>
                                    +33 1 23 45 67 89
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    contact@jobportal.com
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="h5 mb-4">Horaires d'ouverture</h3>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Lundi - Vendredi:</strong> 9h00 - 18h00
                                </li>
                                <li class="mb-2">
                                    <strong>Samedi:</strong> 10h00 - 16h00
                                </li>
                                <li>
                                    <strong>Dimanche:</strong> Fermé
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section py-5 bg-light">
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.292292615509614!3d48.85837007928757!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sTour%20Eiffel!5e0!3m2!1sfr!2sfr!4v1647874586708!5m2!1sfr!2sfr" 
                    width="100%" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</section>

<style>
.contact-hero {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 100px 0;
    margin-top: -1.5rem;
}

.contact-info .card {
    border: none;
    border-radius: 1rem;
}

.contact-info ul li {
    display: flex;
    align-items: center;
}

.map-section iframe {
    border-radius: 1rem;
}
</style>

<script>
// Validation du formulaire
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>