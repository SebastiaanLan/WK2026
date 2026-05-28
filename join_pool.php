<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$errors  = [];
$code    = '';
$pool = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['access_code'] ?? ''));

    if ($code === '' && strlen($code) == 8) {
        $errors[] = "Vul een toeganagscode in.";
    }

    $access_code = strtoupper($code);

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id FROM pools
            WHERE access_code = ?
        ");
        $stmt->execute([$code]);
        $pool_id = (int)$stmt->fetch();

        if (!$pool_id) {
            $errors[] = 'Deze toegangscode is onbekend';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                SELECT id FROM pool_members
                WHERE pool_id = ?
                AND user_id = ?
            ");
            $stmt->execute([$pool_id, $user['id']]);

            if ($stmt->fetch()) {
                header('Location: pool_detail.php?id=' . $pool['id']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO pool_members
                (pool_id, user_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$pool_id, $user['id']]);

            header('Location: pool_detail.php?id=' . $pool['id']);
            exit;
        }
    }
}

$pageTitle = 'Poule joinen';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Join een poule</h1>
            <p class="form-subtitle">Heb je een toegangscode gekregen? Vul hem hier in om deel te nemen.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="join_pool.php" novalidate>
                <div class="form-group">
                    <label class="form-label" for="access_code">Toegangscode</label>
                    <input type="text" id="access_code" name="access_code" class="form-input"
                           value="<?= htmlspecialchars($code) ?>"
                           placeholder="Bijv. A1B2C3D4"
                           style="font-family: var(--font-mono); letter-spacing: 0.15em; text-transform: uppercase;"
                           maxlength="20" required>
                    <p class="form-help">De 8-cijferige code die je van de poule-beheerder hebt gekregen.</p>
                </div>

                <div class="flex gap-2">
                    <a href="pools.php" class="btn btn-ghost">Annuleren</a>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Join poule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
