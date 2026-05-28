<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Zorg ervoor dat gebruiker ingelogd is
requireLogin();

$user = currentUser();

// Haal gebruikersgegevens op met COUNT queries
$stats = [
    'pools'       => 0,
    'predictions' => 0,
];

try {
    // Aantal pools waaraan gebruiker meedoet
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pool_members WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['pools'] = (int)$stmt->fetchColumn();

    // Aantal ingevoerde voorspellingen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM predictions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $stats['predictions'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Stil: stats blijven 0 als query faalt
}

$pageTitle = 'Mijn Profiel';
include __DIR__ . '/includes/header.php';
?>

<div class="container">

    <div class="page-header">
        <div>
            <div class="page-eyebrow">Profiel</div>
            <h1 class="page-title">Mijn Account</h1>
            <p class="page-desc">Bekijk je accountgegevens en statistieken.</p>
        </div>
    </div>

    <div class="form-card" style="margin-bottom: 48px;">
        <h2 class="form-title">Accountgegevens</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 24px;">
            <div>
                <div class="stat-label" style="color: var(--field);">Naam</div>
                <p style="font-size: 16px; color: var(--text); margin-top: 8px;"><?= htmlspecialchars($user['name']) ?></p>
            </div>
            <div>
                <div class="stat-label" style="color: var(--accent);">E-mailadres</div>
                <p style="font-size: 16px; color: var(--text); margin-top: 8px; word-break: break-all;"><?= htmlspecialchars($user['email']) ?></p>
            </div>
        </div>
    </div>

    <div class="stat-row">
        <div class="stat stat-accent">
            <div class="stat-label">Poules</div>
            <div class="stat-value"><?= $stats['pools'] ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Voorspellingen</div>
            <div class="stat-value"><?= $stats['predictions'] ?></div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
