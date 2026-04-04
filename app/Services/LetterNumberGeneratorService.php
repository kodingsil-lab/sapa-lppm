<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/NomorSuratModel.php';
require_once __DIR__ . '/../Models/NomorSuratSettingModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class LetterNumberGeneratorService
{
    private NomorSuratModel $nomorSuratModel;
    private NomorSuratSettingModel $settingModel;

    public function __construct()
    {
        $this->nomorSuratModel = new NomorSuratModel();
        $this->settingModel = new NomorSuratSettingModel();
    }

    public function generate(string $jenisSuratCode, string $skemaInput, ?DateTimeImmutable $issuedAt = null): string
    {
        $jenisSurat = $this->normalizeJenisSuratCode($jenisSuratCode);
        $skema = $this->normalizeSkema($skemaInput);
        $issuedDate = $issuedAt ?? new DateTimeImmutable('now');
        $year = (int) $issuedDate->format('Y');
        $month = (int) $issuedDate->format('n');

        $pdo = db_pdo();
        $pdo->beginTransaction();

        try {
            $lastNumber = $this->nomorSuratModel->getLastNumberForYearWithLock($year);
            $nextNumber = $lastNumber + 1;

            $this->nomorSuratModel->insertNumber($jenisSurat, $skema, $nextNumber, $year);

            $setting = $this->settingModel->findByJenis($jenisSurat);
            if ($setting !== null && (int) ($setting['is_active'] ?? 1) !== 1) {
                throw new RuntimeException('Format nomor surat untuk jenis ' . $jenisSurat . ' sedang nonaktif.');
            }
            $template = (string) (($setting['format_template'] ?? '') !== '' ? $setting['format_template'] : $this->settingModel->getDefaultFormatTemplate());

            $letterNumber = strtr($template, [
                '{nomor_urut}' => sprintf('%03d', $nextNumber),
                '{jenis_surat}' => $jenisSurat,
                '{skema}' => $skema,
                '{bulan_romawi}' => $this->monthToRoman($month),
                '{tahun}' => (string) $year,
            ]);

            $pdo->commit();

            return $letterNumber;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function monthToRoman(int $month): string
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
            throw new InvalidArgumentException('Bulan tidak valid untuk konversi Romawi.');
        }

        return $map[$month];
    }

    private function normalizeJenisSuratCode(string $jenisSuratCode): string
    {
        $value = strtoupper(trim($jenisSuratCode));
        if (!in_array($value, ['K', 'I', 'T'], true)) {
            throw new InvalidArgumentException('Jenis surat tidak valid. Gunakan K, I, atau T.');
        }

        return $value;
    }

    private function normalizeSkema(string $skemaInput): string
    {
        $value = trim($skemaInput);
        if ($value === '') {
            throw new InvalidArgumentException('Skema wajib diisi untuk generate nomor surat.');
        }

        $knownMap = [
            'Penelitian Dosen Pemula Afirmasi (PDP)' => 'PDP',
            'Penelitian Dosen Pemula Afirmasi (PDP-Afirmasi)' => 'PDP',
            'Penelitian Dosen Pemula (PDP)' => 'PDP',
            'Penelitian Fundamental' => 'PF',
            'Penelitian Fundamental (PF)' => 'PF',
            'Penelitian Kerja Sama antar Perguruan Tinggi (PKPT)' => 'PKPT',
            'Penelitian Terapan Luaran Prototipe' => 'PTLP',
            'Penelitian Terapan Luaran Prototipe (PTLP)' => 'PTLP',
            'Penelitian Terapan Luaran Model' => 'PTLM',
            'Penelitian Terapan Luaran Model (PTLM)' => 'PTLM',
            'Pemberdayaan Masyarakat Pemula' => 'PMP',
            'Pemberdayaan Masyarakat Pemula (PMP)' => 'PMP',
            'Pemberdayaan Kemitraan Masyarakat' => 'PKM',
            'Pemberdayaan Kemitraan Masyarakat (PKM)' => 'PKM',
            'Pemberdayaan Masyarakat oleh Mahasiswa' => 'PMM',
            'Pemberdayaan Masyarakat oleh Mahasiswa (PMM)' => 'PMM',
            'Pemberdayaan Mitra Usaha Produk Unggulan Daerah' => 'PMUPUD',
            'Pemberdayaan Mitra Usaha Produk Unggulan Daerah (PMUPUD)' => 'PMUPUD',
            'Pemberdayaan Wilayah' => 'PW',
            'Pemberdayaan Wilayah (PW)' => 'PW',
            'Pemberdayaan Desa Binaan' => 'PDB',
            'Pemberdayaan Desa Binaan (PDB)' => 'PDB',
            'Hilirisasi Pengujian Model dan Prototipe' => 'HPMP',
            'Hilirisasi Pengujian Model dan Prototipe (HPMP)' => 'HPMP',
            'Hilirisasi Riset Prioritas' => 'HRP',
            'Hilirisasi Riset Prioritas (HRP)' => 'HRP',
        ];

        if (isset($knownMap[$value])) {
            return $knownMap[$value];
        }

        if (preg_match('/\(([A-Za-z0-9\-]+)\)/', $value, $matches)) {
            $value = (string) ($matches[1] ?? $value);
        }

        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));
        if ($normalized === '') {
            throw new InvalidArgumentException('Skema tidak valid.');
        }

        return substr($normalized, 0, 20);
    }
}
