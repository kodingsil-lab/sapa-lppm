<?php

declare(strict_types=1);

if (!function_exists('letter_detail_badge')) {
    function letter_detail_badge(string $sectionKey): string
    {
        $key = strtolower(trim($sectionKey));

        $map = [
            'pemohon' => 'Pemohon',
            'applicant' => 'Pemohon',
            'data_kegiatan' => 'Data Kegiatan',
            'activity' => 'Data Kegiatan',
            'pelaksanaan' => 'Pelaksanaan',
            'execution' => 'Pelaksanaan',
            'penugasan' => 'Penugasan',
            'assignment' => 'Penugasan',
            'administrasi' => 'Administrasi',
            'admin' => 'Administrasi',
        ];

        return $map[$key] ?? 'Informasi';
    }
}

