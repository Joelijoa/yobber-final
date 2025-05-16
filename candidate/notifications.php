<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/public/auth/login.php');
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $user_id = getUserId();

    // Marquer toutes les notifications comme lues si demandé
    if (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE user_id = ? AND read_at IS NULL
        ");
        $stmt->execute([$user_id]);
        
        set_flash_message('success', 'Toutes les notifications ont été marquées comme lues.');
        redirect('/public/candidate/notifications.php');
        exit;
    }

    // Récupérer les notifications
    $stmt = $conn->prepare("
        SELECT *,
        DATE_FORMAT(created_at, '%d/%m/%Y à %H:%i') as formatted_date
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter les notifications non lues
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE user_id = ? AND read_at IS NULL
    ");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetchColumn();

} catch (Exception $e) {
    set_flash_message('error', "Une erreur est survenue : " . $e->getMessage());
    redirect('/public/candidate/dashboard.php');
    exit;
}

$page_title = "Mes notifications";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mes notifications</h2>
        <?php if ($unread_count > 0): ?>
            <form method="POST">
                <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php 
    $flash_message = get_flash_message();
    if ($flash_message): 
    ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>">
            <?php echo htmlspecialchars($flash_message['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">
            Vous n'avez aucune notification.
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item list-group-item-action <?php echo $notification['read_at'] === null ? 'list-group-item-primary' : ''; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></h6>
                        <small class="text-muted"><?php echo $notification['formatted_date']; ?></small>
                    </div>
                    <?php if ($notification['link']): ?>
                        <a href="/public<?php 
                            $link = htmlspecialchars($notification['link']);
                            echo strpos($link, '?') !== false ? $link . '&notification=1' : $link . '?notification=1';
                        ?>" class="btn btn-sm btn-primary mt-2">
                            Voir les détails
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 