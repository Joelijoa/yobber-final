<?php
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté et est un candidat
if (!isLoggedIn() || $_SESSION['user_type'] !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Récupérer les paramètres actuels
$stmt = $conn->prepare("
    SELECT u.email, u.notification_preferences,
           c.job_alert_preferences, c.privacy_settings
    FROM users u
    JOIN candidates c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Mise à jour des préférences de notification
        $notification_preferences = [
            'email_notifications' => isset($_POST['email_notifications']),
            'application_updates' => isset($_POST['application_updates']),
            'new_messages' => isset($_POST['new_messages']),
            'job_recommendations' => isset($_POST['job_recommendations'])
        ];

        $stmt = $conn->prepare("
            UPDATE users 
            SET notification_preferences = ?
            WHERE id = ?
        ");
        $stmt->execute([json_encode($notification_preferences), $user_id]);

        // Mise à jour des préférences d'alerte d'emploi
        $job_alert_preferences = [
            'job_types' => $_POST['job_types'] ?? [],
            'locations' => $_POST['locations'] ?? [],
            'keywords' => $_POST['keywords'] ?? [],
            'frequency' => $_POST['alert_frequency'] ?? 'daily'
        ];

        $stmt = $conn->prepare("
            UPDATE candidates 
            SET job_alert_preferences = ?
            WHERE user_id = ?
        ");
        $stmt->execute([json_encode($job_alert_preferences), $user_id]);

        // Mise à jour des paramètres de confidentialité
        $privacy_settings = [
            'profile_visibility' => $_POST['profile_visibility'] ?? 'public',
            'show_contact_info' => isset($_POST['show_contact_info']),
            'show_application_history' => isset($_POST['show_application_history'])
        ];

        $stmt = $conn->prepare("
            UPDATE candidates 
            SET privacy_settings = ?
            WHERE user_id = ?
        ");
        $stmt->execute([json_encode($privacy_settings), $user_id]);

        // Mise à jour du mot de passe si fourni
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                throw new Exception("Le mot de passe actuel est requis pour changer le mot de passe.");
            }

            // Vérifier le mot de passe actuel
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_hash = $stmt->fetchColumn();

            if (!password_verify($_POST['current_password'], $current_hash)) {
                throw new Exception("Le mot de passe actuel est incorrect.");
            }

            // Mettre à jour le mot de passe
            $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
        }

        $conn->commit();
        $success_message = "Paramètres mis à jour avec succès !";
        
        // Rafraîchir les données
        $stmt = $conn->prepare("
            SELECT u.email, u.notification_preferences,
                   c.job_alert_preferences, c.privacy_settings
            FROM users u
            JOIN candidates c ON u.id = c.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Erreur lors de la mise à jour des paramètres : " . $e->getMessage();
    }
}

// Décoder les préférences JSON
$notification_preferences = json_decode($settings['notification_preferences'] ?? '{}', true);
$job_alert_preferences = json_decode($settings['job_alert_preferences'] ?? '{}', true);
$privacy_settings = json_decode($settings['privacy_settings'] ?? '{}', true);
?>

<div class="container mt-4">
    <h2>Paramètres du compte</h2>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-4">
                    <h4>Préférences de notification</h4>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="email_notifications" 
                               name="email_notifications" 
                               <?php echo ($notification_preferences['email_notifications'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">
                            Recevoir des notifications par email
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="application_updates" 
                               name="application_updates"
                               <?php echo ($notification_preferences['application_updates'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="application_updates">
                            Mises à jour des candidatures
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="new_messages" 
                               name="new_messages"
                               <?php echo ($notification_preferences['new_messages'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="new_messages">
                            Nouveaux messages
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="job_recommendations" 
                               name="job_recommendations"
                               <?php echo ($notification_preferences['job_recommendations'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="job_recommendations">
                            Recommandations d'offres d'emploi
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <h4>Alertes d'emploi</h4>
                    <div class="mb-3">
                        <label class="form-label">Types de contrats</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="job_types[]" value="CDI"
                                   <?php echo in_array('CDI', $job_alert_preferences['job_types'] ?? []) ? 'checked' : ''; ?>>
                            <label class="form-check-label">CDI</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="job_types[]" value="CDD"
                                   <?php echo in_array('CDD', $job_alert_preferences['job_types'] ?? []) ? 'checked' : ''; ?>>
                            <label class="form-check-label">CDD</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="job_types[]" value="Freelance"
                                   <?php echo in_array('Freelance', $job_alert_preferences['job_types'] ?? []) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Freelance</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Localisations</label>
                        <input type="text" class="form-control" name="locations" 
                               value="<?php echo htmlspecialchars(implode(', ', $job_alert_preferences['locations'] ?? [])); ?>"
                               placeholder="Séparez les villes par des virgules">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mots-clés</label>
                        <input type="text" class="form-control" name="keywords" 
                               value="<?php echo htmlspecialchars(implode(', ', $job_alert_preferences['keywords'] ?? [])); ?>"
                               placeholder="Séparez les mots-clés par des virgules">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fréquence des alertes</label>
                        <select class="form-select" name="alert_frequency">
                            <option value="daily" <?php echo ($job_alert_preferences['frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>
                                Quotidienne
                            </option>
                            <option value="weekly" <?php echo ($job_alert_preferences['frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>
                                Hebdomadaire
                            </option>
                            <option value="monthly" <?php echo ($job_alert_preferences['frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>
                                Mensuelle
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <h4>Paramètres de confidentialité</h4>
                    <div class="mb-3">
                        <label class="form-label">Visibilité du profil</label>
                        <select class="form-select" name="profile_visibility">
                            <option value="public" <?php echo ($privacy_settings['profile_visibility'] ?? '') === 'public' ? 'selected' : ''; ?>>
                                Public
                            </option>
                            <option value="private" <?php echo ($privacy_settings['profile_visibility'] ?? '') === 'private' ? 'selected' : ''; ?>>
                                Privé
                            </option>
                        </select>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="show_contact_info" 
                               name="show_contact_info"
                               <?php echo ($privacy_settings['show_contact_info'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_contact_info">
                            Afficher mes informations de contact
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="show_application_history" 
                               name="show_application_history"
                               <?php echo ($privacy_settings['show_application_history'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_application_history">
                            Afficher mon historique de candidatures
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <h4>Changer le mot de passe</h4>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 