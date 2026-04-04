<?php

declare(strict_types=1);

require_once __DIR__ . '/ActivityBaseController.php';
require_once __DIR__ . '/../Models/PenelitianModel.php';

class DataPenelitianController extends ActivityBaseController
{
    private PenelitianModel $dataModel;

    public function __construct()
    {
        parent::__construct();
        $this->slug = 'penelitian';
        $this->title = 'Data Penelitian';
        $this->subtitle = 'Daftar kegiatan penelitian yang dapat digunakan sebagai dasar pengajuan surat.';
        $this->longLabel = 'Penelitian';
        $this->dataModel = new PenelitianModel();
    }

    protected function model(): ActivityBaseModel
    {
        return $this->dataModel;
    }

    protected function letterRouteMap(): array
    {
        return [
            'kontrak' => 'surat-penelitian-kontrak',
            'izin' => 'surat-penelitian-izin',
            'tugas' => 'surat-penelitian-tugas',
            'activity_type' => 'penelitian',
        ];
    }

    protected function routes(): array
    {
        return [
            'index' => 'data-penelitian',
            'create' => 'data-penelitian-create',
            'store' => 'data-penelitian-store',
            'update' => 'data-penelitian-update',
            'show' => 'data-penelitian-show',
            'edit' => 'data-penelitian-edit',
            'delete' => 'data-penelitian-delete',
        ];
    }

    protected function validate(array $data): array
    {
        $errors = parent::validate($data);

        $skema = (string) ($data['skema'] ?? '');
        $ruangLingkup = (string) ($data['ruang_lingkup'] ?? '');
        $sumberDana = trim((string) ($data['sumber_dana'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? '')));

        if (!$this->isValidSchemeSelection('penelitian', $skema)) {
            $errors['skema'] = 'Skema penelitian tidak valid.';
        } else {
            if ($ruangLingkup === '') {
                $errors['ruang_lingkup'] = 'Ruang lingkup wajib dipilih.';
            } elseif (!$this->isValidScopeSelection('penelitian', $skema, $ruangLingkup)) {
                $errors['ruang_lingkup'] = 'Ruang lingkup tidak sesuai dengan skema yang dipilih.';
            }
        }

        if ($sumberDana === '' || !$this->isValidFundingSelection('penelitian', $sumberDana)) {
            $errors['sumber_dana'] = 'Sumber dana penelitian tidak valid.';
        }

        if (!in_array($status, ['aktif', 'selesai'], true)) {
            $errors['status'] = 'Status pelaksanaan harus Aktif atau Selesai.';
        }

        $targetLuaranWajib = $data['target_luaran_wajib'] ?? [];
        if (!is_array($targetLuaranWajib)) {
            $targetLuaranWajib = trim((string) $targetLuaranWajib) === '' ? [] : [trim((string) $targetLuaranWajib)];
        }
        $targetLuaranWajib = array_values(array_filter(array_map('trim', array_map('strval', $targetLuaranWajib)), static fn (string $value): bool => $value !== ''));
        $allowedRequiredOutputs = $this->getAllowedOutputCodes('penelitian', 'required');

        if (empty($targetLuaranWajib)) {
            $errors['target_luaran_wajib'] = 'Luaran wajib harus dipilih.';
        } elseif (count(array_diff($targetLuaranWajib, $allowedRequiredOutputs)) > 0) {
            $errors['target_luaran_wajib'] = 'Pilihan luaran wajib tidak valid.';
        }

        $targetLuaranTambahan = $data['target_luaran_tambahan'] ?? [];
        if (!is_array($targetLuaranTambahan)) {
            $targetLuaranTambahan = trim((string) $targetLuaranTambahan) === '' ? [] : [trim((string) $targetLuaranTambahan)];
        }
        $targetLuaranTambahan = array_values(array_filter(array_map('trim', array_map('strval', $targetLuaranTambahan)), static fn (string $value): bool => $value !== ''));
        $allowedAdditionalOutputs = $this->getAllowedOutputCodes('penelitian', 'additional');

        if (!empty($targetLuaranTambahan) && count(array_diff($targetLuaranTambahan, $allowedAdditionalOutputs)) > 0) {
            $errors['target_luaran_tambahan'] = 'Pilihan luaran tambahan tidak valid.';
        }

        return $errors;
    }
}
