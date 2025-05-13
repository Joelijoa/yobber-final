<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/header.php';

// Récupérer toutes les entreprises partenaires
$stmt = $pdo->query("SELECT user_id, company_name, company_logo, company_description FROM recruiter_profiles ORDER BY company_name ASC");
$companies = $stmt->fetchAll();
?>
<div class="container py-5">
    <h1 class="mb-4">Entreprises partenaires</h1>
    <div class="row g-4">
        <?php if (count($companies) > 0): ?>
            <?php foreach ($companies as $company): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <?php if ($company['company_logo']): ?>
                                <img src="<?php echo htmlspecialchars($company['company_logo']); ?>" alt="Logo entreprise" style="max-width: 80px; max-height: 80px;" class="mb-3">
                            <?php endif; ?>
                            <h5 class="mb-1"><?php echo htmlspecialchars($company['company_name']); ?></h5>
                            <?php if ($company['company_description']): ?>
                                <p class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($company['company_description'], 0, 120, '...')); ?></p>
                            <?php endif; ?>
                            <a href="company-profile.php?id=<?php echo $company['user_id']; ?>" class="btn btn-outline-primary btn-sm mt-2">Voir le profil</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">Aucune entreprise partenaire enregistrée.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?> 