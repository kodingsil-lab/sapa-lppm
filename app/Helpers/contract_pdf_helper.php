<?php

declare(strict_types=1);

/**
 * Format angka menjadi mata uang rupiah tanpa desimal.
 * Contoh: 95000000 => Rp95.000.000
 */
if (!function_exists('contract_format_rupiah')) {
    function contract_format_rupiah(int $value): string
    {
        if ($value <= 0) {
            return '-';
        }

        return 'Rp' . number_format($value, 0, ',', '.');
    }
}

/**
 * Clamp persentase agar aman pada rentang 0..100.
 */
if (!function_exists('contract_clamp_percentage')) {
    function contract_clamp_percentage(float $value): float
    {
        if ($value < 0) {
            return 0.0;
        }
        if ($value > 100) {
            return 100.0;
        }

        return $value;
    }
}

/**
 * Hitung nominal dana per tahap berdasarkan total dan persentase.
 * Pembulatan menggunakan round agar konsisten ke rupiah.
 *
 * @return array{
 *   stage1_percent: float,
 *   stage2_percent: float,
 *   stage1_amount: int,
 *   stage2_amount: int
 * }
 */
if (!function_exists('contract_calc_stage_amounts')) {
    function contract_calc_stage_amounts(int $total, float $stage1Percent, float $stage2Percent): array
    {
        $total = max(0, $total);
        $p1 = contract_clamp_percentage($stage1Percent);
        $p2 = contract_clamp_percentage($stage2Percent);

        $stage1 = (int) round(($total * $p1) / 100);
        $stage2 = (int) round(($total * $p2) / 100);

        return [
            'stage1_percent' => $p1,
            'stage2_percent' => $p2,
            'stage1_amount' => $stage1,
            'stage2_amount' => $stage2,
        ];
    }
}

/**
 * Format tanggal ke Indonesia.
 * Input disarankan Y-m-d.
 */
if (!function_exists('contract_format_tanggal_indo')) {
    function contract_format_tanggal_indo(string $rawDate): string
    {
        $value = trim($rawDate);
        if ($value === '') {
            return '-';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $day = (int) date('j', $ts);
        $month = (int) date('n', $ts);
        $year = date('Y', $ts);

        return $day . ' ' . ($months[$month] ?? date('F', $ts)) . ' ' . $year;
    }
}

