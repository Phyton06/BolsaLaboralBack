<?php
declare(strict_types=1);

/**
 * DB Init — runs schema + seed if tables don't exist.
 * Safe to call on every boot; skips if already initialized.
 */

function initDatabase(): void {
    try {
        $pdo = getPgConnection();

        // Check if tables exist
        $stmt = $pdo->query(
            "SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'usuarios')"
        );
        if ($stmt->fetchColumn()) {
            return; // Already initialized
        }

        echo "Initializing database...\n";

        // Run schema
        $schema = file_get_contents(__DIR__ . '/../config/db.sql');
        $pdo->exec($schema);
        echo "Schema created.\n";

        // Run seed
        $seed = file_get_contents(__DIR__ . '/../db/seed.sql');
        $pdo->exec($seed);
        echo "Seed data loaded.\n";

    } catch (PDOException $e) {
        error_log("DB init error: " . $e->getMessage());
    }
}
