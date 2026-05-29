# Puntentellingssysteem - Documentatie

## Overzicht
Het puntentellingssysteem berekent punten voor voorspellingen op basis van de werkelijke uitslag van wedstrijden.

## Puntentelling

| Scenario | Punten | Beschrijving |
|----------|--------|-------------|
| Exacte uitslag | 3 | Beide doelpunten exact correct voorspeld |
| Correcte winnaar + correct doelsaldo | 2 | Winnaar correct voorspeld EN doelsaldo exact correct |
| Correcte winnaar/gelijkspel | 1 | Winnaar (thuis/uit) of gelijkspel correct voorspeld, maar doelsaldo fout |
| Incorrect | 0 | Winnaar/gelijkspel fout voorspeld |
| Match zonder uitslag | 0 | Wedstrijd nog niet gespeeld, geen punten mogelijk |

## Voorbeelden

### Voorbeeld 1: Exacte uitslag (3 punten)
- Voorspelling: Nederland 2-0 Argentinië
- Werkelijke uitslag: Nederland 2-0 Argentinië
- Punten: **3**

### Voorbeeld 2: Correcte winnaar + doelsaldo (2 punten)
- Voorspelling: Nederland 2-0 Argentinië
- Werkelijke uitslag: Nederland 3-1 Argentinië
- Analyse: Winnaar correct (Nederland wint), doelsaldo correct (3-1 = verschil 2, 2-0 = verschil 2)
- Punten: **2**

### Voorbeeld 3: Correcte winnaar, fout doelsaldo (1 punt)
- Voorspelling: Nederland 2-1 Argentinië
- Werkelijke uitslag: Nederland 3-0 Argentinië
- Analyse: Winnaar correct (Nederland wint), doelsaldo fout (3-0 = verschil 3, 2-1 = verschil 1)
- Punten: **1**

### Voorbeeld 4: Fout voorspeld (0 punten)
- Voorspelling: Nederland 1-2 Argentinië
- Werkelijke uitslag: Nederland 2-1 Argentinië
- Analyse: Winnaar fout (Argentinië verliest, Nederland wint)
- Punten: **0**

## PHP Implementatie

### Functie: `calculatePoints()`

```php
calculatePoints(int $predicted_home, int $predicted_away, int $actual_home, int $actual_away): int
```

**Parameters:**
- `$predicted_home` (int): Voorspelde doelpunten thuisploeg
- `$predicted_away` (int): Voorspelde doelpunten uitploeg
- `$actual_home` (int): Werkelijke doelpunten thuisploeg
- `$actual_away` (int): Werkelijke doelpunten uitploeg

**Return Value:**
- `int`: Aantal punten (0, 1, 2, of 3)

**Bestandslocatie:** `includes/auth.php`

### Gebruik in code

```php
require_once 'includes/auth.php';

// Bereken punten
$points = calculatePoints(
    predicted_home: 2,
    predicted_away: 1,
    actual_home: 2,
    actual_away: 1
); // Returns: 3

// Gebruik in query
$stmt = $pdo->prepare("
    UPDATE predictions 
    SET points = ? 
    WHERE id = ?
");
$stmt->execute([
    calculatePoints($pred_home, $pred_away, $act_home, $act_away),
    $prediction_id
]);
```

## Database Schema

### Predictions Tabel
```sql
CREATE TABLE predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    predicted_home INT NOT NULL,
    predicted_away INT NOT NULL,
    points TINYINT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prediction (user_id, match_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);
```

**Kolom `points`:**
- Type: `TINYINT` (0-255)
- Default: `NULL`
- Beschrijving: Opgeslagen puntentelling; `NULL` wanneer de werkelijke uitslag nog niet bekend is

## Migratie

Een migratiescript is beschikbaar om de `points` kolom toe te voegen aan bestaande databases:

```bash
php migrations/001_add_points_column.php
```

Dit script:
1. Controleert of de `points` kolom al bestaat
2. Voegt de kolom toe indien deze niet aanwezig is
3. Geeft een bevestigingsbericht
