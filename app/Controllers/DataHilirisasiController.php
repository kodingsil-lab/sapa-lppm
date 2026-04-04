<?php

declare(strict_types=1);

require_once __DIR__ . '/ActivityBaseController.php';
require_once __DIR__ . '/../Models/HilirisasiModel.php';

class DataHilirisasiController extends ActivityBaseController
{
    private HilirisasiModel $dataModel;

    public function __construct()
    {
        parent::__construct();
        $this->slug = 'hilirisasi';
        $this->title = 'Data Hilirisasi';
        $this->subtitle = 'Daftar kegiatan hilirisasi yang dapat digunakan sebagai dasar pengajuan surat.';
        $this->longLabel = 'Hilirisasi';
        $this->dataModel = new HilirisasiModel();
    }

    protected function model(): ActivityBaseModel
    {
        return $this->dataModel;
    }

    protected function letterRouteMap(): array
    {
        return [
            'kontrak' => 'surat-hilirisasi-kontrak',
            'izin' => 'surat-hilirisasi-izin',
            'tugas' => 'surat-hilirisasi-tugas',
            'activity_type' => 'hilirisasi',
        ];
    }

    protected function routes(): array
    {
        return [
            'index' => 'data-hilirisasi',
            'create' => 'data-hilirisasi-create',
            'store' => 'data-hilirisasi-store',
            'update' => 'data-hilirisasi-update',
            'show' => 'data-hilirisasi-show',
            'edit' => 'data-hilirisasi-edit',
            'delete' => 'data-hilirisasi-delete',
        ];
    }

    protected function validate(array $data): array
    {
        $errors = parent::validate($data);
        $skema = trim((string) ($data['skema'] ?? ''));
        $ruangLingkup = trim((string) ($data['ruang_lingkup'] ?? ''));
        $sumberDana = trim((string) ($data['sumber_dana'] ?? ''));

        if (!$this->isValidSchemeSelection('hilirisasi', $skema)) {
            $errors['skema'] = 'Skema hilirisasi tidak valid.';
        }

        if ($ruangLingkup === '') {
            $errors['ruang_lingkup'] = 'Ruang lingkup wajib dipilih.';
        } elseif (!$this->isValidScopeSelection('hilirisasi', $skema, $ruangLingkup)) {
            $errors['ruang_lingkup'] = 'Ruang lingkup tidak sesuai dengan skema hilirisasi.';
        }

        if ($sumberDana === '' || !$this->isValidFundingSelection('hilirisasi', $sumberDana)) {
            $errors['sumber_dana'] = 'Sumber dana hilirisasi tidak valid.';
        }

        $allowedStatus = ['aktif', 'selesai'];
        if (!in_array(strtolower((string) ($data['status'] ?? '')), $allowedStatus, true)) {
            $errors['status'] = 'Status pelaksanaan wajib dipilih (Aktif/Selesai).';
        }

        $targetLuaranWajib = $data['target_luaran_wajib'] ?? [];
        if (!is_array($targetLuaranWajib)) {
            $targetLuaranWajib = trim((string) $targetLuaranWajib) === '' ? [] : [trim((string) $targetLuaranWajib)];
        }
        $targetLuaranWajib = array_values(array_filter(array_map('trim', array_map('strval', $targetLuaranWajib)), static fn (string $value): bool => $value !== ''));
        $allowedRequiredOutputs = $this->getAllowedOutputCodes('hilirisasi', 'required');
        if (empty($targetLuaranWajib)) {
            $errors['target_luaran_wajib'] = 'Luaran wajib hilirisasi harus dipilih.';
        } elseif (count(array_diff($targetLuaranWajib, $allowedRequiredOutputs)) > 0) {
            $errors['target_luaran_wajib'] = 'Pilihan luaran wajib hilirisasi tidak valid.';
        }

        return $errors;
    }
}
