<?php

declare(strict_types=1);

require_once __DIR__ . '/ActivityBaseController.php';
require_once __DIR__ . '/../Models/PengabdianModel.php';

class DataPengabdianController extends ActivityBaseController
{
    private PengabdianModel $dataModel;

    public function __construct()
    {
        parent::__construct();
        $this->slug = 'pengabdian';
        $this->title = 'Data Pengabdian';
        $this->subtitle = 'Daftar kegiatan pengabdian yang dapat digunakan sebagai dasar pengajuan surat.';
        $this->longLabel = 'Pengabdian Kepada Masyarakat';
        $this->dataModel = new PengabdianModel();
    }

    protected function model(): ActivityBaseModel
    {
        return $this->dataModel;
    }

    protected function letterRouteMap(): array
    {
        return [
            'kontrak' => 'surat-pengabdian-kontrak',
            'izin' => 'surat-pengabdian-izin',
            'tugas' => 'surat-pengabdian-tugas',
            'activity_type' => 'pengabdian',
        ];
    }

    protected function routes(): array
    {
        return [
            'index' => 'data-pengabdian',
            'create' => 'data-pengabdian-create',
            'store' => 'data-pengabdian-store',
            'update' => 'data-pengabdian-update',
            'show' => 'data-pengabdian-show',
            'edit' => 'data-pengabdian-edit',
            'delete' => 'data-pengabdian-delete',
        ];
    }

    protected function validate(array $data): array
    {
        $errors = parent::validate($data);

        $skema = trim((string) ($data['skema'] ?? ''));
        $ruangLingkup = trim((string) ($data['ruang_lingkup'] ?? ''));
        $sumberDana = trim((string) ($data['sumber_dana'] ?? ''));

        if (!$this->isValidSchemeSelection('pengabdian', $skema)) {
            $errors['skema'] = 'Skema pengabdian tidak valid.';
        } else {
            if ($ruangLingkup === '') {
                $errors['ruang_lingkup'] = 'Ruang lingkup wajib dipilih.';
            } elseif (!$this->isValidScopeSelection('pengabdian', $skema, $ruangLingkup)) {
                $errors['ruang_lingkup'] = 'Ruang lingkup tidak sesuai dengan skema yang dipilih.';
            }
        }

        if ($sumberDana === '' || !$this->isValidFundingSelection('pengabdian', $sumberDana)) {
            $errors['sumber_dana'] = 'Sumber dana pengabdian tidak valid.';
        }

        $status = strtolower(trim((string) ($data['status'] ?? '')));
        if (!in_array($status, ['aktif', 'selesai'], true)) {
            $errors['status'] = 'Status pelaksanaan harus Aktif atau Selesai.';
        }

        $targetLuaranWajib = $data['target_luaran_wajib'] ?? [];
        if (!is_array($targetLuaranWajib)) {
            $targetLuaranWajib = trim((string) $targetLuaranWajib) === '' ? [] : [trim((string) $targetLuaranWajib)];
        }
        $targetLuaranWajib = array_values(array_filter(array_map('trim', array_map('strval', $targetLuaranWajib)), static fn (string $value): bool => $value !== ''));
        $allowedRequiredOutputs = $this->getAllowedOutputCodes('pengabdian', 'required');

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
        $allowedAdditionalOutputs = $this->getAllowedOutputCodes('pengabdian', 'additional');

        if (!empty($targetLuaranTambahan) && count(array_diff($targetLuaranTambahan, $allowedAdditionalOutputs)) > 0) {
            $errors['target_luaran_tambahan'] = 'Pilihan luaran tambahan tidak valid.';
        }

        return $errors;
    }
}
