<?php

declare(strict_types=1);

if (!function_exists('db_pdo')) {
    function db_pdo(): PDO
    {
        $config = require __DIR__ . '/../../config.php';
        $db = $config['db'];
        $appTimezone = (string) ($config['app']['timezone'] ?? 'Asia/Makassar');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );

        try {
            $pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            try {
                $tz = new DateTimeZone($appTimezone);
                $offset = (new DateTimeImmutable('now', $tz))->format('P');
                $pdo->exec("SET time_zone = '$offset'");
            } catch (Throwable $e) {
                // Ignore timezone setup failure so DB connection remains usable.
            }

            return $pdo;
        } catch (PDOException $e) {
            $message = 'Koneksi database gagal. Pastikan database `' . $db['database'] . '` sudah dibuat dan diimport.';
            throw new RuntimeException($message, 0, $e);
        }
    }
}
