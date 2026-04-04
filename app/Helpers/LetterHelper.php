<?php

declare(strict_types=1);

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

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

if (!function_exists('monthToRoman')) {
    function monthToRoman(int $month): string
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        if (!isset($map[$month])) {
            throw new InvalidArgumentException('Bulan tidak valid.');
        }

        return $map[$month];
    }
}

if (!function_exists('generateLetterNumber')) {
    function generateLetterNumber(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new InvalidArgumentException('Format tanggal tidak valid.');
        }

        $month = (int) date('n', $timestamp);
        $year = (int) date('Y', $timestamp);
        $romanMonth = monthToRoman($month);

        $pdo = db_pdo();

        $stmt = $pdo->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(letter_number, '/', 1) AS UNSIGNED)) AS last_number
             FROM letters
             WHERE MONTH(letter_date) = :month
               AND YEAR(letter_date) = :year"
        );
        $stmt->execute([
            ':month' => $month,
            ':year' => $year,
        ]);

        $lastNumber = (int) ($stmt->fetchColumn() ?: 0);
        $nextNumber = $lastNumber + 1;

        return sprintf('%03d/LPPM/UNISAP/%s/%d', $nextNumber, $romanMonth, $year);
    }
}

if (!function_exists('generateLetterNumberByKind')) {
    function generateLetterNumberByKind(string $date, string $kind): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new InvalidArgumentException('Format tanggal tidak valid.');
        }

        $kindNormalized = strtolower(trim($kind));
        $subjectKeyword = match ($kindNormalized) {
            'kontrak' => 'kontrak',
            'tugas' => 'tugas',
            default => 'izin',
        };

        $month = (int) date('n', $timestamp);
        $year = (int) date('Y', $timestamp);
        $romanMonth = monthToRoman($month);

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(letter_number, '/', 1) AS UNSIGNED)) AS last_number
             FROM letters
             WHERE letter_number IS NOT NULL
               AND letter_number <> ''
               AND MONTH(letter_date) = :month
               AND YEAR(letter_date) = :year
               AND LOWER(subject) LIKE :subject_keyword"
        );
        $stmt->execute([
            ':month' => $month,
            ':year' => $year,
            ':subject_keyword' => '%' . $subjectKeyword . '%',
        ]);

        $lastNumber = (int) ($stmt->fetchColumn() ?: 0);
        $nextNumber = $lastNumber + 1;

        return sprintf('%03d/LPPM/UNISAP/%s/%d', $nextNumber, $romanMonth, $year);
    }
}

if (!function_exists('generateVerificationToken')) {
    function generateVerificationToken(int $length = 32): string
    {
        if ($length < 16) {
            $length = 16;
        }

        $bytes = random_bytes((int) ceil($length / 2));

        return substr(bin2hex($bytes), 0, $length);
    }
}

if (!function_exists('buildVerificationUrl')) {
    function buildVerificationUrl(string $token): string
    {
        $config = require __DIR__ . '/../../config.php';
        $baseUrl = rtrim((string) ($config['app']['url'] ?? ''), '/');

        return $baseUrl . '/verify/' . rawurlencode($token);
    }
}

if (!function_exists('generateQrCode')) {
    function generateQrCode(string $token): array
    {
        if ($token === '') {
            throw new InvalidArgumentException('Token verifikasi tidak boleh kosong.');
        }

        $verificationUrl = buildVerificationUrl($token);
        $storageDir = __DIR__ . '/../../storage/uploads/qrcodes';

        if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Folder QR Code tidak dapat dibuat.');
        }

        $filename = 'qr-' . $token . '.png';
        $fullPath = $storageDir . '/' . $filename;
        $relativePath = 'storage/uploads/qrcodes/' . $filename;

        $builder = new Builder(
            writer: new PngWriter(),
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 12
        );
        $result = $builder->build();

        $result->saveToFile($fullPath);

        return [
            'token' => $token,
            'verification_url' => $verificationUrl,
            'full_path' => $fullPath,
            'relative_path' => $relativePath,
            'data_uri' => $result->getDataUri(),
        ];
    }
}
