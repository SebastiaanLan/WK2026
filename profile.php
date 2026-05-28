<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Zorg ervoor dat gebruiker ingelogd is
requireLogin();

$user = currentUser();
$errors = [];
$success = '';

// Verwerk formulier voor naamwijziging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_name') {
    $newName = trim($_POST['new_name'] ?? '');
    
    // Back-end validatie
    if (strlen($newName) < 2) {
        $errors[] = 'Naam moet minimaal 2 karakters lang zijn.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            if ($stmt->execute([$newName, $user['id']])) {
                // Update SESSION met nieuwe naam
                $_SESSION['user_name'] = $newName;
                $user['name'] = $newName;
                $success = 'je naam is succesvol bijgewerkt';
            }
        } catch (PDOException $e) {
            $errors[] = 'Er is een fout opgetreden bij het bijwerken van je naam.';
        }
    }
}

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

    <!-- Succesmelding/Foutmelding -->
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

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

    <!-- Formulier voor naamwijziging -->
    <div class="form-card" style="margin-bottom: 48px;">
        <h2 class="form-title">Naam aanpassen</h2>
        <form method="POST" id="updateNameForm" style="margin-top: 24px;">
            <input type="hidden" name="action" value="update_name">
            
            <div style="margin-bottom: 16px;">
                <label for="newName" class="form-label">Nieuwe naam</label>
                <input 
                    type="text" 
                    id="newName" 
                    name="new_name" 
                    class="form-input" 
                    value="<?= htmlspecialchars($user['name']) ?>" 
                    minlength="2"
                    maxlength="100"
                    required
                    placeholder="Voer je nieuwe naam in"
                >
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">Minimaal 2 karakters</small>
            </div>

            <button type="submit" class="btn btn-primary">Naam opslaan</button>
        </form>
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
