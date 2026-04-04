<?php

declare(strict_types=1);

if (!function_exists('db_pdo')) {
    function db_pdo(): PDO
    {
        $config = require __DIR__ . '/../../config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );

        try {
            return new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $message = 'Koneksi database gagal. Pastikan database `' . $db['database'] . '` sudah dibuat dan diimport.';
            throw new RuntimeException($message, 0, $e);
        }
    }
}
