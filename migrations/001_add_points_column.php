<?php
/**
 * Migration: Add points column to predictions table
 * This migration adds support for tracking calculated points in predictions.
 */

require_once __DIR__ . '/../includes/db.php';

try {
    // Check if points column already exists
    $result = $pdo->query("SHOW COLUMNS FROM predictions LIKE 'points'");
    if ($result->rowCount() === 0) {
        // Add points column if it doesn't exist
        $pdo->exec("ALTER TABLE predictions ADD COLUMN points TINYINT DEFAULT NULL");
        echo "✓ Successfully added 'points' column to predictions table\n";
    } else {
        echo "✓ Column 'points' already exists in predictions table\n";
    }
} catch (PDOException $e) {
    die("✗ Migration failed: " . htmlspecialchars($e->getMessage()) . "\n");
}
