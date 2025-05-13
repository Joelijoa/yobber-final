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

// Marquer toutes les notifications comme lues
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    header('Location: notifications.php');
    exit();
}

// Récupérer les notifications
$stmt = $conn->prepare("
    SELECT n.*, 
           DATE_FORMAT(n.created_at, '%d/%m/%Y %H:%i') as notification_date,
           CASE 
               WHEN n.type = 'application_status' THEN 'Statut de candidature'
               WHEN n.type = 'new_message' THEN 'Nouveau message'
               WHEN n.type = 'job_recommendation' THEN 'Recommandation d\'offre'
               ELSE n.type
           END as type_fr
    FROM notifications n
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les notifications non lues
$stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mes Notifications</h2>
        <?php if ($unread_count > 0): ?>
            <a href="?mark_all_read" class="btn btn-outline-primary">
                Tout marquer comme lu
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore de notifications.
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?php echo htmlspecialchars($notification['type_fr']); ?></h5>
                        <small class="text-muted"><?php echo $notification['notification_date']; ?></small>
                    </div>
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                    <?php if ($notification['link']): ?>
                        <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm btn-primary mt-2">
                            Voir détails
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 