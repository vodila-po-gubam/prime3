<?php

declare(strict_types=1);

$databasePath = __DIR__ . '/../db/database.sqlite';
$databaseDir  = dirname($databasePath);

if (!is_dir($databaseDir)) {
    if (!mkdir($databaseDir, 0777, true) && !is_dir($databaseDir)) {
        throw new RuntimeException('Не удалось создать директорию для базы данных');
    }
}

try {
    $pdo = new PDO(
        'sqlite:' . $databasePath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $exception) {
    throw new RuntimeException('Ошибка подключения к базе данных: ' . $exception->getMessage());
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS submissions (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        form_type  TEXT NOT NULL,
        data       TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        username   TEXT NOT NULL UNIQUE,
        password   TEXT NOT NULL,
        role       TEXT NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'");
} catch (PDOException $exception) {
}

$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$checkStmt->execute(['Admin']);

if ((int) $checkStmt->fetchColumn() === 0) {
    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

    $insertStmt = $pdo->prepare("
        INSERT INTO users (username, password, role)
        VALUES (:username, :password, :role)
    ");

    $insertStmt->execute([
        ':username' => 'Admin',
        ':password' => $passwordHash,
        ':role'     => 'admin',
    ]);
}
