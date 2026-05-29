<?php
// ============================================
// Authenticatie helper functies
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Controleer of de gebruiker is ingelogd.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Stuur de gebruiker door naar de login pagina als deze niet is ingelogd.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Haal de huidige ingelogde gebruiker op (id, name, email).
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}

/**
 * Bereken punten op basis van de voorspelling en werkelijke uitslag.
 *
 * Puntensysteem:
 * - 1 punt: correcte winnaar of gelijkspel
 * - 2 punten: correcte winnaar + correct doelsaldo
 * - 3 punten: exacte uitslag geraden
 *
 * @param int $predicted_home Voorspelde thuisscorescore
 * @param int $predicted_away Voorspelde uitscores
 * @param int $actual_home Werkelijke thuisscores
 * @param int $actual_away Werkelijke uitscores
 * @return int Aantal punten (0-3)
 */
function calculatePoints(int $predicted_home, int $predicted_away, int $actual_home, int $actual_away): int {
    // Geen punten mogelijk als de werkelijke uitslag niet bekend is
    if ($actual_home === null || $actual_away === null) {
        return 0;
    }

    // Exacte uitslag geraden = 3 punten
    if ($predicted_home === $actual_home && $predicted_away === $actual_away) {
        return 3;
    }

    // Bepaal winnaar/verliezer/gelijkspel voor voorspelling en werkelijke uitslag
    $predicted_diff = $predicted_home - $predicted_away;
    $actual_diff = $actual_home - $actual_away;

    // Determine result: positive = home win, 0 = draw, negative = away win
    $predicted_result = ($predicted_diff > 0) ? 1 : (($predicted_diff < 0) ? -1 : 0);
    $actual_result = ($actual_diff > 0) ? 1 : (($actual_diff < 0) ? -1 : 0);

    // Correcte winnaar/gelijkspel
    if ($predicted_result === $actual_result) {
        // Correcte winnaar + correct doelsaldo = 2 punten
        if ($predicted_diff === $actual_diff) {
            return 2;
        }
        // Alleen correcte winnaar/gelijkspel = 1 punt
        return 1;
    }

    // Geen punten
    return 0;
}
