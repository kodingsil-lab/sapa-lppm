<?php

declare(strict_types=1);

/**
 * Loader helper gaya CI agar bisa dipanggil helper('terbilang').
 */
if (!function_exists('helper')) {
    function helper(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            return;
        }

        $file = __DIR__ . '/' . $name . '_helper.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
}

/**
 * Normalisasi angka dari input campuran (mis. 1.250.000,50 atau 1250000.50).
 * Mengembalikan string angka standar dengan pemisah desimal titik.
 */
if (!function_exists('terbilang_normalize_number')) {
    function terbilang_normalize_number($nilai): string
    {
        if (is_int($nilai) || is_float($nilai)) {
            return (string) $nilai;
        }

        $raw = trim((string) $nilai);
        if ($raw === '') {
            return '0';
        }

        $raw = preg_replace('/\s+/', '', $raw);
        $raw = preg_replace('/[^0-9,\.\-]/', '', (string) $raw);
        if ($raw === '' || $raw === '-' || $raw === null) {
            return '0';
        }

        $negative = str_starts_with($raw, '-');
        $unsigned = ltrim($raw, '-');

        $lastComma = strrpos($unsigned, ',');
        $lastDot = strrpos($unsigned, '.');
        $decimalSep = '';

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSep = $lastComma > $lastDot ? ',' : '.';
        } elseif ($lastComma !== false) {
            $fractionLen = strlen($unsigned) - $lastComma - 1;
            $decimalSep = $fractionLen > 0 && $fractionLen <= 2 ? ',' : '';
        } elseif ($lastDot !== false) {
            $fractionLen = strlen($unsigned) - $lastDot - 1;
            $decimalSep = $fractionLen > 0 && $fractionLen <= 2 ? '.' : '';
        }

        if ($decimalSep === '') {
            $digits = preg_replace('/\D+/', '', $unsigned);
            $result = $digits === '' ? '0' : $digits;
            return $negative && $result !== '0' ? '-' . $result : $result;
        }

        $parts = explode($decimalSep, $unsigned, 2);
        $intPart = preg_replace('/\D+/', '', $parts[0] ?? '');
        $fracPart = preg_replace('/\D+/', '', $parts[1] ?? '');
        $intPart = $intPart === '' ? '0' : $intPart;
        $result = $intPart . ($fracPart !== '' ? '.' . $fracPart : '');

        return $negative && $result !== '0' ? '-' . $result : $result;
    }
}

/**
 * Fungsi rekursif inti penyebut angka bahasa Indonesia.
 * Mendukung hingga triliun.
 */
if (!function_exists('penyebut')) {
    function penyebut($nilai): string
    {
        $nilai = (int) floor(abs((float) $nilai));
        $huruf = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
        ];

        if ($nilai < 12) {
            return ' ' . $huruf[$nilai];
        }
        if ($nilai < 20) {
            return penyebut($nilai - 10) . ' belas';
        }
        if ($nilai < 100) {
            return penyebut((int) floor($nilai / 10)) . ' puluh' . penyebut($nilai % 10);
        }
        if ($nilai < 200) {
            return ' seratus' . penyebut($nilai - 100);
        }
        if ($nilai < 1000) {
            return penyebut((int) floor($nilai / 100)) . ' ratus' . penyebut($nilai % 100);
        }
        if ($nilai < 2000) {
            return ' seribu' . penyebut($nilai - 1000);
        }
        if ($nilai < 1000000) {
            return penyebut((int) floor($nilai / 1000)) . ' ribu' . penyebut($nilai % 1000);
        }
        if ($nilai < 1000000000) {
            return penyebut((int) floor($nilai / 1000000)) . ' juta' . penyebut($nilai % 1000000);
        }
        if ($nilai < 1000000000000) {
            return penyebut((int) floor($nilai / 1000000000)) . ' miliar' . penyebut($nilai % 1000000000);
        }
        if ($nilai < 1000000000000000) {
            return penyebut((int) floor($nilai / 1000000000000)) . ' triliun' . penyebut($nilai % 1000000000000);
        }

        // Fallback jika melebihi batas dukungan.
        return ' ' . (string) $nilai;
    }
}

/**
 * Konversi angka ke terbilang (huruf kecil), termasuk desimal dan negatif.
 * Contoh: -12.5 => minus dua belas koma lima
 */
if (!function_exists('terbilang')) {
    function terbilang($nilai): string
    {
        $normalized = terbilang_normalize_number($nilai);
        $normalized = trim($normalized);
        if ($normalized === '') {
            return 'nol';
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');

        $parts = explode('.', $unsigned, 2);
        $intPart = (int) ($parts[0] ?? 0);
        $fractionPart = isset($parts[1]) ? trim((string) $parts[1]) : '';

        $result = trim(penyebut($intPart));
        if ($result === '') {
            $result = 'nol';
        }

        if ($fractionPart !== '') {
            $digitMap = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
            $fractionWords = [];
            foreach (str_split($fractionPart) as $digitChar) {
                $digit = (int) $digitChar;
                $fractionWords[] = $digitMap[$digit] ?? 'nol';
            }
            $result .= ' koma ' . implode(' ', $fractionWords);
        }

        $result = strtolower(trim((string) preg_replace('/\s+/', ' ', $result)));
        if ($negative && $result !== 'nol') {
            $result = 'minus ' . $result;
        }

        return $result;
    }
}

/**
 * Terbilang untuk rupiah.
 * Contoh: 100000 => seratus ribu rupiah
 */
if (!function_exists('terbilang_rupiah')) {
    function terbilang_rupiah($nilai): string
    {
        $words = terbilang($nilai);
        if ($words === '') {
            $words = 'nol';
        }

        if (!str_ends_with($words, 'rupiah')) {
            $words .= ' rupiah';
        }

        return trim((string) preg_replace('/\s+/', ' ', strtolower($words)));
    }
}

/**
 * Versi huruf awal kapital.
 */
if (!function_exists('terbilang_ucfirst')) {
    function terbilang_ucfirst($nilai): string
    {
        $text = terbilang($nilai);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($text, 0, 1), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');
        }

        return ucfirst($text);
    }
}

/**
 * Versi huruf besar semua.
 */
if (!function_exists('terbilang_upper')) {
    function terbilang_upper($nilai): string
    {
        $text = terbilang($nilai);
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($text, 'UTF-8');
        }

        return strtoupper($text);
    }
}

/**
 * Alias untuk kebutuhan umum angka Indonesia.
 */
if (!function_exists('terbilang_angka_indo')) {
    function terbilang_angka_indo($nilai): string
    {
        return terbilang($nilai);
    }
}

