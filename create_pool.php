<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$errors      = [];
$name        = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'Naam is verplicht';
    } else if (mb_strlen($name) <= 3 || mb_strlen($name) >= 100) {
        $errors[] = 'Naam moet minimaal 3 tekens zijn en maximaal 100 tekens';
    }
    
    if (mb_strlen($name) >= 500) {
        $errors[] = 'Beschrijving mag maximaal 500 tekens zijn';
    }

    if (empty($errors)) {
        do {
            $code = strtoupper(bin2hex(random_bytes(4)));
            
            $stmt = $pdo->prepare("
                SELECT id FROM pools
                WHERE access_code = ?
            ");
            $stmt->execute([$code]);
        } while ($stmt->fetch());

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO pools 
                (name, description, access_code, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $code, $user['id']]);

            $pool_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO pool_members
                (pool_id, user_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$pool_id, $user['id']]);

            $pdo->commit();

            header('Location: pool_detail.php?id=' . $pool_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Er ging iets mis, probeer het opniew";
        }
    }
}

$pageTitle = 'Nieuwe poule';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Nieuwe poule</h1>
            <p class="form-subtitle">Start je eigen competitie en nodig vrienden uit met een toegangscode.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="create_pool.php" novalidate>
                <div class="form-group">
                    <label class="form-label" for="name">Naam van de poule</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="<?= htmlspecialchars($name) ?>"
                           placeholder="Bijv. Klas 4A - WK 2026" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Beschrijving (optioneel)</label>
                    <textarea id="description" name="description" class="form-textarea"
                              placeholder="Waar gaat deze poule over?"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="flex gap-2">
                    <a href="pools.php" class="btn btn-ghost">Annuleren</a>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Poule aanmaken</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
