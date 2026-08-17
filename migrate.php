<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Načtení SQL migrace
    $sql = file_get_contents('db_update.sql');
    
    // Rozdělení na jednotlivé příkazy (podle středníku)
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Provedeno: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Pokud sloupec už existuje, ignorujeme chybu, jinak vyhodíme
                if (str_contains($e->getMessage(), 'Duplicate column name') || str_contains($e->getMessage(), 'already exists')) {
                    echo "Sloupec 'role' již existuje, ignoruji.\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    echo "Migrace dokončena úspěšně.\n";
} catch (Exception $e) {
    echo "Chyba při migraci: " . $e->getMessage() . "\n";
}
