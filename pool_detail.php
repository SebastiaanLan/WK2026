<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

$pool_id = (int)($_GET['id'] ?? 0);
if ($pool_id <= 0) {
    header('Location: pools.php');
    exit;
}

// Haal de poule op + check of gebruiker lid is
$pool = null;
$members = [];

try {
    // Poule + check of user lid is
    $stmt = $pdo->prepare("
        SELECT p.*, u.name AS creator_name
        FROM pools p
        INNER JOIN users u ON u.id = p.created_by
        INNER JOIN pool_members pm ON pm.pool_id = p.id AND pm.user_id = ?
        WHERE p.id = ?
    ");
    $stmt->execute([$user['id'], $pool_id]);
    $pool = $stmt->fetch();

    if (!$pool) {
        header('Location: pools.php');
        exit;
    }

    // Leden ophalen
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, pm.joined_at
        FROM pool_members pm
        INNER JOIN users u ON u.id = pm.user_id
        WHERE pm.pool_id = ?
        ORDER BY pm.joined_at ASC
    ");
    $stmt->execute([$pool_id]);
    $members = $stmt->fetchAll();

    // Voorspellingen ophalen gestructureerd
    $stmt = $pdo->prepare("
        SELECT m.id AS match_id, m.home_team, m.away_team, m.match_date,
               u.id AS user_id, u.name AS user_name,
               p.predicted_home, p.predicted_away
        FROM matches m
        CROSS JOIN pool_members pm
        CROSS JOIN users u
        LEFT JOIN predictions p ON p.match_id = m.id AND p.user_id = u.id
        WHERE pm.pool_id = ? AND u.id = pm.user_id
        ORDER BY m.match_date ASC, u.name ASC
    ");
    $stmt->execute([$pool_id]);
    $predictions_raw = $stmt->fetchAll();

    // Structureren naar $datap[match_id][user_id]
    $datap = [];
    $matches_list = [];

    foreach ($predictions_raw as $row) {
        $match_id = $row['match_id'];
        $user_id = $row['user_id'];

        if (!isset($datap[$match_id])) {
            $datap[$match_id] = [];
            $matches_list[$match_id] = [
                'home_team' => $row['home_team'],
                'away_team' => $row['away_team'],
                'match_date' => $row['match_date']
            ];
        }

        $prediction = '-';
        if ($row['predicted_home'] !== null && $row['predicted_away'] !== null) {
            $prediction = $row['predicted_home'] . '-' . $row['predicted_away'];
        }

        $datap[$match_id][$user_id] = $prediction;
    }

    // Helper functie voor landafkorting
    $getCountryCode = function($country) {
        $codes = [
            'Nederland' => 'NED', 'Argentinië' => 'ARG', 'Brazilië' => 'BRA',
            'Duitsland' => 'DUI', 'Frankrijk' => 'FRA', 'Spanje' => 'ESP',
            'Engeland' => 'ENG', 'Portugal' => 'POR', 'België' => 'BEL',
            'Kroatië' => 'KRO', 'Italië' => 'ITA', 'Uruguay' => 'URU',
            'Japan' => 'JPN', 'Marokko' => 'MAR', 'Verenigde Staten' => 'USA',
            'Mexico' => 'MEX'
        ];
        return $codes[$country] ?? substr($country, 0, 3);
    };
} catch (PDOException $e) {
    die('Fout bij ophalen van poule: ' . htmlspecialchars($e->getMessage()));
}

$pageTitle = $pool['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 24px;">
        <a href="pools.php" class="nav-link" style="padding-left: 0;">← Terug naar poules</a>
    </div>

    <div class="pool-hero">
        <div class="feature-number">POULE</div>
        <h1 class="pool-hero-title"><?= htmlspecialchars($pool['name']) ?></h1>
        <?php if ($pool['description']): ?>
            <p style="color: var(--text-dim); font-size: 16px; max-width: 640px;">
                <?= nl2br(htmlspecialchars($pool['description'])) ?>
            </p>
        <?php endif; ?>
        <div class="pool-hero-code">
            <span class="pool-hero-code-label">Toegangscode:</span>
            <strong><?= htmlspecialchars($pool['access_code']) ?></strong>
        </div>
    </div>

    <div class="dash-grid">
        <!-- Deelnemers -->
        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Deelnemers</h2>
                    <p class="card-subtitle"><?= count($members) ?> LEDEN</p>
                </div>
            </div>

            <div class="member-list">
                <?php foreach ($members as $member): ?>
                    <div class="member">
                        <div class="member-avatar">
                            <?= strtoupper(substr(htmlspecialchars($member['name']), 0, 1)) ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                            <div class="member-email"><?= htmlspecialchars($member['email']) ?></div>
                        </div>
                        <?php if ((int)$member['id'] === (int)$pool['created_by']): ?>
                            <span class="member-badge">Beheerder</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Voorspellingen overzicht -->
    <section class="card" style="margin-top: 32px;">
        <div class="card-header">
            <div>
                <h2 class="card-title">Voorspellingen per wedstrijd</h2>
                <p class="card-subtitle">OVERZICHT VAN ALLE LEDEN</p>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <?php if (empty($datap)): ?>
                <p style="padding: 16px; color: var(--text-dim); text-align: center;">
                    Nog geen voorspellingen beschikbaar
                </p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-card-hi); border-bottom: 2px solid var(--field);">
                            <th style="text-align: left; padding: 14px 12px; color: var(--field); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Wedstrijd</th>
                            <?php foreach ($members as $member): ?>
                                <th style="text-align: center; padding: 14px 12px; color: var(--field); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">
                                    <?= htmlspecialchars($member['name']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datap as $match_id => $user_predictions): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;">
                                <td style="padding: 14px 12px; font-weight: 700; white-space: nowrap; color: var(--field);">
                                    <?= $getCountryCode($matches_list[$match_id]['home_team']) ?>
                                    <span style="color: var(--text-dim); margin: 0 6px; font-weight: 400;">vs</span>
                                    <?= $getCountryCode($matches_list[$match_id]['away_team']) ?>
                                </td>
                                <?php foreach ($members as $member): ?>
                                    <td style="text-align: center; padding: 14px 12px; font-family: var(--font-mono); font-weight: 500; color: var(--accent);">
                                        <?php 
                                            $prediction = $user_predictions[$member['id']] ?? '-';
                                            echo htmlspecialchars($prediction);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <div class="dash-grid" style="margin-top: 32px;">
        <!-- Sidebar -->
        <aside class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Acties</h2>
                    <p class="card-subtitle">BEHEER</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="predictions.php" class="btn btn-primary btn-block">⚽ Voorspellen</a>
                <div style="padding: 16px; background: var(--bg-deep); border: 1px dashed var(--border-hi); border-radius: var(--radius-sm);">
                    <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-mute); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">
                        Deel deze code met vrienden
                    </div>
                    <div style="font-family: var(--font-display); font-size: 24px; color: var(--field); letter-spacing: 0.1em;">
                        <?= htmlspecialchars($pool['access_code']) ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
