<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ActivityBaseModel.php';
require_once __DIR__ . '/../Models/ContractSettingModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ActivitySchemeModel.php';
require_once __DIR__ . '/../Models/ActivityScopeModel.php';
require_once __DIR__ . '/../Models/FundingSourceModel.php';
require_once __DIR__ . '/../Models/OutputTypeModel.php';
require_once __DIR__ . '/../Models/ActivityCategoryOutputModel.php';
require_once __DIR__ . '/../Helpers/ActivityLogHelper.php';

abstract class ActivityBaseController extends BaseController
{
    protected string $slug = '';
    protected string $title = '';
    protected string $subtitle = '';
    protected string $longLabel = '';
    private ?UserModel $userModel = null;
    private ?ActivitySchemeModel $schemeModel = null;
    private ?ActivityScopeModel $scopeModel = null;
    private ?FundingSourceModel $fundingSourceModel = null;
    private ?OutputTypeModel $outputTypeModel = null;
    private ?ActivityCategoryOutputModel $categoryOutputModel = null;

    abstract protected function model(): ActivityBaseModel;

    abstract protected function letterRouteMap(): array;

    abstract protected function routes(): array;

    protected function userModel(): UserModel
    {
        if ($this->userModel === null) {
            $this->userModel = new UserModel();
        }

        return $this->userModel;
    }

    protected function schemeModel(): ActivitySchemeModel
    {
        if ($this->schemeModel === null) {
            $this->schemeModel = new ActivitySchemeModel();
        }

        return $this->schemeModel;
    }

    protected function scopeModel(): ActivityScopeModel
    {
        if ($this->scopeModel === null) {
            $this->scopeModel = new ActivityScopeModel();
        }

        return $this->scopeModel;
    }

    protected function fundingSourceModel(): FundingSourceModel
    {
        if ($this->fundingSourceModel === null) {
            $this->fundingSourceModel = new FundingSourceModel();
        }

        return $this->fundingSourceModel;
    }

    protected function outputTypeModel(): OutputTypeModel
    {
        if ($this->outputTypeModel === null) {
            $this->outputTypeModel = new OutputTypeModel();
        }

        return $this->outputTypeModel;
    }

    protected function categoryOutputModel(): ActivityCategoryOutputModel
    {
        if ($this->categoryOutputModel === null) {
            $this->categoryOutputModel = new ActivityCategoryOutputModel();
        }

        return $this->categoryOutputModel;
    }

    public function index(): void
    {
        $this->guardDosen();
        $userId = (int) (authUserId() ?? 0);
        $model = $this->model();

        $filters = [
            'year' => trim((string) ($_GET['year'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        $items = $model->getList($userId, $filters);
        foreach ($items as $index => $row) {
            $itemId = (int) ($row['id'] ?? 0);
            $items[$index]['_is_linked_submission'] = !empty($row['_is_owner']) && $itemId > 0
                ? $model->hasLinkedLetterSubmission($itemId, $userId, $this->slug)
                : false;
        }
        $stats = $model->getStats($userId);

        $this->render('data/' . $this->slug . '/index', [
            'pageTitle' => $this->title,
            'pageSubtitle' => $this->subtitle,
            'activityLongLabel' => $this->longLabel,
            'activityType' => $this->slug,
            'items' => $items,
            'stats' => $stats,
            'filters' => $filters,
            'letterRoutes' => $this->letterRouteMap(),
            'routes' => $this->routes(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function create(): void
    {
        $this->guardDosen();
        $this->renderForm($this->emptyData(), null, [], null, 'create', false, []);
    }

    public function edit(): void
    {
        $this->guardDosen();
        $id = (int) ($_GET['id'] ?? 0);
        $userId = (int) (authUserId() ?? 0);

        if ($id <= 0) {
            $this->redirectListError('ID data tidak valid.');
            return;
        }

        $item = $this->model()->findById($id, $userId);
        if ($item === null) {
            $this->redirectListError('Data tidak ditemukan.');
            return;
        }
        if (empty($item['_is_owner'])) {
            $this->redirectListError('Data hanya dapat diubah oleh ketua kegiatan.');
            return;
        }

        $isCoreLocked = $this->model()->hasLinkedLetterSubmission($id, $userId, $this->slug);
        $fromLetterId = (int) ($_GET['from_letter_id'] ?? 0);
        $revisionContext = $this->buildRevisionContextFromLetter($fromLetterId, $userId);
        $this->renderForm($item, $id, [], null, 'edit', $isCoreLocked, $revisionContext);
    }

    public function store(): void
    {
        $this->guardDosen();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath($this->routePath($this->routes()['index']));
        }

        $id = (int) ($_POST['id'] ?? 0);
        $userId = (int) (authUserId() ?? 0);
        $fromLetterId = (int) ($_POST['from_letter_id'] ?? $_GET['from_letter_id'] ?? 0);
        $revisionContext = $this->buildRevisionContextFromLetter($fromLetterId, $userId);
        $formData = $this->collectFormData($_POST);
        $existing = null;

        $isCoreLocked = false;
        if ($id > 0) {
            $isCoreLocked = $this->model()->hasLinkedLetterSubmission($id, $userId, $this->slug);
        }

        if ($id > 0) {
            $existing = $this->model()->findOwnedById($id, $userId);
            if ($existing === null) {
                $this->redirectListError('Data tidak ditemukan.');
                return;
            }

            // Lindungi field inti bila data sudah dipakai untuk ajuan surat.
            if ($isCoreLocked) {
                foreach (['judul', 'skema', 'ruang_lingkup', 'sumber_dana', 'tahun', 'ketua'] as $field) {
                    $formData[$field] = (string) ($existing[$field] ?? ($formData[$field] ?? ''));
                }
            }
        }

        $errors = $this->validate($formData);
        if (!empty($errors)) {
            $this->renderForm($formData, $id > 0 ? $id : null, $errors, 'Mohon lengkapi data kegiatan.', $id > 0 ? 'edit' : 'create', $isCoreLocked, $revisionContext);
            return;
        }

        try {
            $savedId = $this->model()->save($formData, $userId, $id > 0 ? $id : null);
            $savedItem = $savedId > 0 ? $this->model()->findById($savedId, $userId) : null;
            if ($savedItem !== null) {
                $this->logMemberAuditTrail($existing, $savedItem, $savedId);
            }

            if (!empty($revisionContext['resubmit_contract']) && $savedId > 0) {
                if ($savedItem !== null) {
                    $this->resubmitContractLetterFromActivity($fromLetterId, $userId, $savedItem);
                    $this->redirectToPath('surat-saya', [
                        'success' => 'Data kegiatan diperbarui dan ajuan kontrak berhasil dikirim ulang.',
                    ]);
                }
            }

            $query = [
                'id' => $savedId,
                'success' => 'Data berhasil disimpan.',
            ];
            if ($fromLetterId > 0) {
                $query['from_letter_id'] = $fromLetterId;
            }
            $this->redirectToPath($this->routePath($this->routes()['edit']), $query);
        } catch (Throwable $e) {
            $this->renderForm(
                $formData,
                $id > 0 ? $id : null,
                [],
                $e->getMessage(),
                $id > 0 ? 'edit' : 'create',
                $isCoreLocked,
                $revisionContext
            );
            return;
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int) ($_POST['id'] ?? 0) <= 0) {
            $_POST['id'] = (int) ($_GET['id'] ?? 0);
        }

        $this->store();
    }

    public function show(): void
    {
        $this->guardDosen();
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectListError('ID data tidak valid.');
            return;
        }

        $userId = (int) (authUserId() ?? 0);
        $item = $this->model()->findById($id, $userId);
        if ($item === null) {
            $this->redirectListError('Data tidak ditemukan.');
            return;
        }

        $isCoreLocked = !empty($item['_is_owner'])
            ? $this->model()->hasLinkedLetterSubmission($id, $userId, $this->slug)
            : true;
        $this->renderForm($item, $id, [], null, 'detail', $isCoreLocked, []);
    }

    public function destroy(): void
    {
        $this->guardDosen();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectListError('Metode request tidak valid.');
            return;
        }

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        $userId = (int) (authUserId() ?? 0);
        if ($id <= 0) {
            $this->redirectListError('ID data tidak valid.');
            return;
        }
        if (!$this->model()->isOwnedByUser($id, $userId)) {
            $this->redirectListError('Data hanya dapat dihapus oleh ketua kegiatan.');
            return;
        }

        if ($this->model()->hasLinkedLetterSubmission($id, $userId, $this->slug)) {
            $this->redirectListError('Data sudah dipakai dalam pengajuan surat dan tidak dapat dihapus.');
            return;
        }

        $deleted = $this->model()->deleteById($id, $userId);
        if (!$deleted) {
            $this->redirectListError('Data tidak ditemukan atau tidak dapat dihapus.');
            return;
        }

        $this->redirectToPath($this->routePath($this->routes()['index']), [
            'success' => 'Data berhasil dihapus.',
        ]);
    }

    protected function collectFormData(array $source): array
    {
        $currentUser = authUser() ?? [];
        $autoKetua = trim((string) ($currentUser['name'] ?? ''));
        $currentUserId = (int) ($currentUser['id'] ?? authUserId() ?? 0);
        $anggotaItems = [];
        $anggotaMembers = [];
        $rawAnggota = $source['anggota_items'] ?? ($source['anggota'] ?? '');
        $rawAnggotaMemberIds = $source['anggota_member_ids'] ?? [];
        if (is_array($rawAnggota)) {
            foreach ($rawAnggota as $index => $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $normalizedName = preg_replace('/\s+/', ' ', $item) ?? $item;
                    $memberUserId = is_array($rawAnggotaMemberIds) ? (int) ($rawAnggotaMemberIds[$index] ?? 0) : 0;
                    $anggotaItems[] = $normalizedName;
                    $anggotaMembers[] = [
                        'name' => $normalizedName,
                        'user_id' => $memberUserId,
                    ];
                }
            }
        } else {
            $anggotaText = trim((string) $rawAnggota);
            if ($anggotaText !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $anggotaText) ?: [];
                foreach ($lines as $line) {
                    $line = trim((string) $line);
                    if ($line !== '') {
                        $normalizedName = preg_replace('/\s+/', ' ', $line) ?? $line;
                        $anggotaItems[] = $normalizedName;
                        $anggotaMembers[] = [
                            'name' => $normalizedName,
                            'user_id' => 0,
                        ];
                    }
                }
            }
        }
        $normalizedKetua = strtolower($autoKetua !== '' ? $autoKetua : trim((string) ($source['ketua'] ?? '')));
        $seenMembers = [];
        $filteredMembers = [];
        foreach ($anggotaMembers as $member) {
            $memberName = trim((string) ($member['name'] ?? ''));
            $memberUserId = (int) ($member['user_id'] ?? 0);
            if ($memberName === '') {
                continue;
            }
            if (strtolower($memberName) === $normalizedKetua) {
                continue;
            }
            if ($memberUserId > 0 && $currentUserId > 0 && $memberUserId === $currentUserId) {
                continue;
            }

            $dedupeKey = $memberUserId > 0
                ? 'user:' . $memberUserId
                : 'name:' . strtolower($memberName);
            if (isset($seenMembers[$dedupeKey])) {
                continue;
            }

            $seenMembers[$dedupeKey] = true;
            $filteredMembers[] = [
                'name' => $memberName,
                'user_id' => $memberUserId,
            ];
        }
        $anggotaItems = array_values(array_map(
            static fn (array $member): string => (string) ($member['name'] ?? ''),
            $filteredMembers
        ));
        $anggotaText = implode("\n", $anggotaItems);
        $lamaKegiatanRaw = trim((string) ($source['lama_kegiatan'] ?? ''));
        $allowedDurations = ['1', '2', '3'];
        $lamaKegiatan = in_array($lamaKegiatanRaw, $allowedDurations, true) ? (int) $lamaKegiatanRaw : 1;
        $luaranItems = [];
        $rawLuaran = $source['luaran_target'] ?? [];
        if (is_array($rawLuaran)) {
            foreach ($rawLuaran as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $luaranItems[] = $item;
                }
            }
        }
        $luaranText = implode("\n", array_values(array_unique($luaranItems)));
        $targetLuaranWajibItems = [];
        $rawTargetWajib = $source['target_luaran_wajib'] ?? [];
        if (is_array($rawTargetWajib)) {
            foreach ($rawTargetWajib as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $targetLuaranWajibItems[] = $item;
                }
            }
        } elseif (trim((string) $rawTargetWajib) !== '') {
            $targetLuaranWajibItems[] = trim((string) $rawTargetWajib);
        }
        $targetLuaranWajibItems = array_values(array_unique($targetLuaranWajibItems));

        $targetLuaranTambahanItems = [];
        $rawTargetTambahan = $source['target_luaran_tambahan'] ?? [];
        if (is_array($rawTargetTambahan)) {
            foreach ($rawTargetTambahan as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $targetLuaranTambahanItems[] = $item;
                }
            }
        } elseif (trim((string) $rawTargetTambahan) !== '') {
            $targetLuaranTambahanItems[] = trim((string) $rawTargetTambahan);
        }
        $targetLuaranTambahanItems = array_values(array_unique($targetLuaranTambahanItems));

        $targetLuaranLines = [];
        if (!empty($targetLuaranWajibItems)) {
            $targetLuaranLines[] = 'Luaran Wajib: ' . implode('|', $targetLuaranWajibItems);
        }
        if (!empty($targetLuaranTambahanItems)) {
            $targetLuaranLines[] = 'Luaran Tambahan: ' . implode('|', $targetLuaranTambahanItems);
        }
        $targetLuaranText = implode("\n", $targetLuaranLines);
        $totalDana = preg_replace('/\D+/', '', (string) ($source['total_dana_disetujui'] ?? ''));
        $selectedFunding = trim((string) ($source['sumber_dana'] ?? ''));
        $customFunding = trim((string) ($source['sumber_dana_lainnya'] ?? ''));
        $resolvedFunding = strcasecmp($selectedFunding, 'Lainnya') === 0 ? $customFunding : $selectedFunding;
        $tahunRaw = trim((string) ($source['tahun'] ?? ''));
        $tahunMulai = preg_match('/^\d{4}$/', $tahunRaw) ? (int) $tahunRaw : (int) date('Y');
        $tahunSelesai = $tahunMulai + $lamaKegiatan - 1;

        return [
            'judul' => trim((string) ($source['judul'] ?? '')),
            'skema' => trim((string) ($source['skema'] ?? '')),
            'ruang_lingkup' => trim((string) ($source['ruang_lingkup'] ?? '')),
            'sumber_dana' => $resolvedFunding,
            'tahun' => $tahunRaw,
            'ketua' => $autoKetua !== '' ? $autoKetua : trim((string) ($source['ketua'] ?? '')),
            'anggota' => $anggotaText,
            'anggota_member_ids' => array_values(array_map(
                static fn (array $member): int => (int) ($member['user_id'] ?? 0),
                $filteredMembers
            )),
            'anggota_members' => $filteredMembers,
            'lokasi' => trim((string) ($source['lokasi'] ?? '')),
            'mitra' => trim((string) ($source['mitra'] ?? '')),
            'total_dana_disetujui' => (string) ($totalDana ?? ''),
            'tanggal_mulai' => sprintf('%04d-01-01', $tahunMulai),
            'tanggal_selesai' => sprintf('%04d-12-31', $tahunSelesai),
            'lama_kegiatan' => $lamaKegiatanRaw,
            'status' => trim((string) ($source['status'] ?? 'draft')),
            'deskripsi' => $targetLuaranText !== '' ? $targetLuaranText : ($luaranText !== '' ? $luaranText : trim((string) ($source['deskripsi'] ?? ''))),
            'target_luaran_wajib' => $targetLuaranWajibItems,
            'target_luaran_tambahan' => $targetLuaranTambahanItems,
            'file_proposal' => trim((string) ($source['file_proposal'] ?? '')),
            'file_instrumen' => trim((string) ($source['file_instrumen'] ?? '')),
            'file_pendukung_lain' => trim((string) ($source['file_pendukung_lain'] ?? '')),
        ];
    }

    protected function validate(array $data): array
    {
        $rules = [
            'judul' => 'required|min_length[5]|max_length[255]',
            'skema' => 'required|max_length[160]',
            'ruang_lingkup' => 'permit_empty|max_length[160]',
            'sumber_dana' => 'required|max_length[200]',
            'tahun' => 'required|exact_length[4]|regex_match[/^\d{4}$/]',
            'ketua' => 'required|max_length[160]',
            'anggota' => 'required|max_length[2000]',
            'lokasi' => 'required|max_length[255]',
            'mitra' => 'permit_empty|max_length[255]',
            'total_dana_disetujui' => 'required|numeric|max_length[20]',
            'lama_kegiatan' => 'required|in_list[1,2,3]',
            'status' => 'required|max_length[50]',
            'deskripsi' => 'permit_empty|max_length[5000]',
            'file_proposal' => 'required|regex_match[/^https?:\/\/\S+$/i]|max_length[1200]',
            'file_instrumen' => 'permit_empty|regex_match[/^https?:\/\/\S+$/i]|max_length[1200]',
            'file_pendukung_lain' => 'permit_empty|regex_match[/^https?:\/\/\S+$/i]|max_length[1200]',
        ];
        $messages = [
            'tahun' => [
                'regex_match' => 'Tahun harus 4 digit angka.',
            ],
            'file_proposal' => [
                'regex_match' => 'Lampiran Proposal harus berupa URL http/https yang valid.',
            ],
            'file_instrumen' => [
                'regex_match' => 'Lampiran Instrumen harus berupa URL http/https yang valid.',
            ],
            'file_pendukung_lain' => [
                'regex_match' => 'Lampiran Pendukung harus berupa URL http/https yang valid.',
            ],
        ];

        $validation = $this->validatePayload($data, $rules, $messages);
        $errors = $validation['errors'];

        $ketuaNormalized = strtolower(trim((string) ($data['ketua'] ?? '')));
        $anggotaLines = preg_split('/\r\n|\r|\n/', (string) ($data['anggota'] ?? '')) ?: [];
        $anggotaItems = array_values(array_filter(array_map(
            static fn (string $item): string => trim(preg_replace('/\s+/', ' ', $item) ?? $item),
            $anggotaLines
        ), static fn (string $item): bool => $item !== ''));

        if ($anggotaItems === []) {
            $errors['anggota'] = 'Minimal satu anggota wajib diisi.';
        } else {
            $seen = [];
            foreach ($anggotaItems as $anggotaName) {
                $normalized = strtolower($anggotaName);
                if ($normalized === $ketuaNormalized) {
                    $errors['anggota'] = 'Ketua tidak boleh dimasukkan lagi sebagai anggota.';
                    break;
                }
                if (isset($seen[$normalized])) {
                    $errors['anggota'] = 'Nama anggota tidak boleh duplikat.';
                    break;
                }
                $seen[$normalized] = true;
            }
        }

        $wajibOutputs = is_array($data['target_luaran_wajib'] ?? null) ? $data['target_luaran_wajib'] : [];
        $tambahanOutputs = is_array($data['target_luaran_tambahan'] ?? null) ? $data['target_luaran_tambahan'] : [];
        if (count($wajibOutputs) > 25) {
            $errors['target_luaran_wajib'] = 'Maksimal 25 item luaran wajib.';
        }
        if (count($tambahanOutputs) > 25) {
            $errors['target_luaran_tambahan'] = 'Maksimal 25 item luaran tambahan.';
        }

        return $errors;
    }

    protected function getMasterOptionsForActivity(string $activityType): array
    {
        $this->categoryOutputModel();
        $schemes = $this->schemeModel()->getAll($activityType, true);
        $fundingSources = $this->fundingSourceModel()->getAll($activityType, true);
        $scopesByScheme = $this->scopeModel()->getActiveGroupedBySchemeName($activityType);

        $requiredOutputs = [];
        foreach ($this->outputTypeModel()->getActiveByCategoryCode($activityType, 'required') as $item) {
            $requiredOutputs[(string) ($item['code'] ?? '')] = (string) ($item['name'] ?? '');
        }

        $additionalOutputs = [];
        foreach ($this->outputTypeModel()->getActiveByCategoryCode($activityType, 'additional') as $item) {
            $additionalOutputs[(string) ($item['code'] ?? '')] = (string) ($item['name'] ?? '');
        }

        return [
            'schemes' => $schemes,
            'scheme_names' => array_values(array_map(static fn (array $item): string => (string) ($item['name'] ?? ''), $schemes)),
            'scopes_by_scheme' => $scopesByScheme,
            'funding_sources' => $fundingSources,
            'funding_names' => array_values(array_map(static fn (array $item): string => (string) ($item['name'] ?? ''), $fundingSources)),
            'target_luaran_required' => $requiredOutputs,
            'target_luaran_additional' => $additionalOutputs,
        ];
    }

    protected function isValidSchemeSelection(string $activityType, string $schemeName): bool
    {
        return $this->schemeModel()->findActiveByCategoryAndName($activityType, $schemeName) !== null;
    }

    protected function isValidScopeSelection(string $activityType, string $schemeName, string $scopeName): bool
    {
        return $this->scopeModel()->findActiveByCategoryAndSchemeAndName($activityType, $schemeName, $scopeName) !== null;
    }

    protected function isValidFundingSelection(string $activityType, string $fundingName): bool
    {
        return $this->fundingSourceModel()->findActiveByCategoryAndName($activityType, $fundingName) !== null;
    }

    protected function getAllowedOutputCodes(string $activityType, string $usage = 'all'): array
    {
        return array_values(array_map(
            static fn (array $item): string => (string) ($item['code'] ?? ''),
            $this->outputTypeModel()->getActiveByCategoryCode($activityType, $usage)
        ));
    }

    protected function emptyData(): array
    {
        return [
            'judul' => '',
            'skema' => '',
            'ruang_lingkup' => '',
            'sumber_dana' => '',
            'tahun' => (string) date('Y'),
            'ketua' => trim((string) ((authUser()['name'] ?? ''))),
            'anggota' => '',
            'lokasi' => '',
            'mitra' => '',
            'total_dana_disetujui' => '',
            'tanggal_mulai' => '',
            'tanggal_selesai' => '',
            'lama_kegiatan' => '1',
            'status' => 'draft',
            'deskripsi' => '',
            'target_luaran_wajib' => [],
            'target_luaran_tambahan' => [],
            'file_proposal' => '',
            'file_instrumen' => '',
            'file_pendukung_lain' => '',
        ];
    }

    private function logMemberAuditTrail(?array $beforeItem, array $afterItem, int $activityId): void
    {
        if ($activityId <= 0) {
            return;
        }

        $beforeMembers = $this->normalizeMembersForAudit($beforeItem['_member_entries'] ?? [], (string) ($beforeItem['anggota'] ?? ''));
        $afterMembers = $this->normalizeMembersForAudit($afterItem['_member_entries'] ?? [], (string) ($afterItem['anggota'] ?? ''));

        if ($beforeItem === null) {
            if ($afterMembers !== []) {
                logActivity(
                    'data-' . $this->slug,
                    'Membuat ' . $this->activityAuditLabel() . ' dengan anggota: ' . $this->summarizeAuditMembers($afterMembers),
                    $activityId
                );
            }

            return;
        }

        $added = array_diff_key($afterMembers, $beforeMembers);
        $removed = array_diff_key($beforeMembers, $afterMembers);

        if ($added !== []) {
            logActivity(
                'data-' . $this->slug,
                'Menambahkan anggota pada ' . $this->activityAuditLabel() . ': ' . $this->summarizeAuditMembers($added),
                $activityId
            );
        }

        if ($removed !== []) {
            logActivity(
                'data-' . $this->slug,
                'Menghapus anggota dari ' . $this->activityAuditLabel() . ': ' . $this->summarizeAuditMembers($removed),
                $activityId
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $memberEntries
     * @return array<string, string>
     */
    private function normalizeMembersForAudit(array $memberEntries, string $anggotaText = ''): array
    {
        $normalized = [];

        foreach ($memberEntries as $entry) {
            $name = trim((string) ($entry['member_name'] ?? $entry['name'] ?? ''));
            $userId = (int) ($entry['member_user_id'] ?? $entry['user_id'] ?? 0);
            if ($name === '') {
                continue;
            }

            $label = $userId > 0 ? $name . ' (ID ' . $userId . ')' : $name;
            $key = $userId > 0
                ? 'user:' . $userId
                : 'name:' . strtolower(preg_replace('/\s+/', ' ', $name) ?? $name);

            $normalized[$key] = $label;
        }

        if ($normalized === [] && trim($anggotaText) !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $anggotaText) ?: [];
            foreach ($lines as $line) {
                $name = trim((string) $line);
                if ($name === '') {
                    continue;
                }
                $key = 'name:' . strtolower(preg_replace('/\s+/', ' ', $name) ?? $name);
                $normalized[$key] = $name;
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<string, string> $members
     */
    private function summarizeAuditMembers(array $members): string
    {
        $labels = array_values($members);
        if ($labels === []) {
            return '-';
        }

        $labels = array_slice($labels, 0, 10);
        $summary = implode(', ', $labels);

        return mb_strimwidth($summary, 0, 220, '...');
    }

    private function activityAuditLabel(): string
    {
        return match ($this->slug) {
            'penelitian' => 'kegiatan penelitian',
            'pengabdian' => 'kegiatan pengabdian',
            'hilirisasi' => 'kegiatan hilirisasi',
            default => 'data kegiatan',
        };
    }

    private function renderForm(array $formData, ?int $id, array $errors, ?string $errorMessage = null, string $mode = 'create', bool $isCoreLocked = false, array $revisionContext = []): void
    {
        $formData = $this->hydrateTargetLuaranFromDescription($formData);
        $isEdit = $id !== null && $id > 0;
        $isDetail = $mode === 'detail';
        $isMemberReadOnly = !empty($formData['_is_member_readonly']);
        $modeLabel = $isDetail ? 'Detail ' : ($isEdit ? 'Edit ' : 'Tambah ');

        $this->render('data/' . $this->slug . '/form', [
            'pageTitle' => $modeLabel . $this->title,
            'pageSubtitle' => 'Lengkapi data kegiatan dengan format yang rapi dan konsisten.',
            'activityLongLabel' => $this->longLabel,
            'activityType' => $this->slug,
            'formData' => $formData,
            'itemId' => $id,
            'formMode' => $mode,
            'isEdit' => $isEdit,
            'isDetail' => $isDetail,
            'isMemberReadOnly' => $isMemberReadOnly,
            'validationErrors' => $errors,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $errorMessage,
            'routes' => $this->routes(),
            'isCoreLocked' => $isCoreLocked,
            'revisionNoteFromLetter' => (string) ($revisionContext['note'] ?? ''),
            'fromLetterId' => (int) ($revisionContext['letter_id'] ?? 0),
            'resubmitContractMode' => (bool) ($revisionContext['resubmit_contract'] ?? false),
            'memberSuggestions' => $this->userModel()->getDosenNameSuggestions((int) (authUserId() ?? 0)),
            'masterOptions' => $this->getMasterOptionsForActivity($this->slug),
        ]);
    }

    private function buildRevisionContextFromLetter(int $fromLetterId, int $userId): array
    {
        if ($fromLetterId <= 0 || $userId <= 0) {
            return [];
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.status,
                l.subject,
                COALESCE(stp.keterangan, "") AS task_note,
                COALESCE(rp.notes, "") AS permit_note
            FROM letters l
            LEFT JOIN surat_tugas_penelitian stp ON stp.letter_id = l.id
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            WHERE l.id = :id
              AND l.applicant_id = :user_id
            LIMIT 1'
        );
        $stmt->execute([
            ':id' => $fromLetterId,
            ':user_id' => $userId,
        ]);
        $row = $stmt->fetch();
        if ($row === false) {
            return [];
        }

        $normalizedStatus = strtolower(trim((string) ($row['status'] ?? '')));
        if ($normalizedStatus !== 'perlu_diperbaiki') {
            return [];
        }

        $rawNote = trim((string) ($row['task_note'] ?? ''));
        if ($rawNote === '') {
            $rawNote = trim((string) ($row['permit_note'] ?? ''));
        }

        $cleanNote = preg_replace('/__ACTIVITY_REF__\[[^\]]+\]/i', '', $rawNote);
        $cleanNote = preg_replace('/__CONTRACT_SOURCE__\[[^\]]+\]/i', '', (string) $cleanNote);
        $cleanNote = trim(preg_replace('/\s+/', ' ', (string) $cleanNote));

        if ($cleanNote === '') {
            return [];
        }

        return [
            'letter_id' => $fromLetterId,
            'note' => $cleanNote,
            'resubmit_contract' => str_contains(strtolower((string) ($row['subject'] ?? '')), 'kontrak'),
        ];
    }

    private function resubmitContractLetterFromActivity(int $letterId, int $userId, array $activityRow): void
    {
        if ($letterId <= 0 || $userId <= 0) {
            return;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                l.id,
                l.subject,
                l.status,
                rp.destination_position,
                rp.phone,
                rp.unit,
                rp.faculty,
                rp.applicant_email
            FROM letters l
            LEFT JOIN research_permit_letters rp ON rp.letter_id = l.id
            WHERE l.id = :id
              AND l.applicant_id = :user_id
            LIMIT 1'
        );
        $stmt->execute([
            ':id' => $letterId,
            ':user_id' => $userId,
        ]);
        $letter = $stmt->fetch();
        if ($letter === false) {
            return;
        }

        $subjectLower = strtolower(trim((string) ($letter['subject'] ?? '')));
        $statusLower = strtolower(trim((string) ($letter['status'] ?? '')));
        if (!str_contains($subjectLower, 'kontrak') || $statusLower !== 'perlu_diperbaiki') {
            return;
        }

        $fundingSource = trim((string) ($activityRow['sumber_dana'] ?? '-'));
        $researchScheme = trim((string) (($activityRow['ruang_lingkup'] ?? '') !== '' ? $activityRow['ruang_lingkup'] : ($activityRow['skema'] ?? '-')));
        $activityRef = '__ACTIVITY_REF__[' . $this->slug . ':' . (int) ($activityRow['id'] ?? 0) . ']';
        $contractSourceRef = '__CONTRACT_SOURCE__[' . ContractSettingModel::resolveSourceKeyFromFunding($fundingSource) . ']';
        $attachmentValue = $this->composeActivityAttachmentValue($activityRow);
        $authUser = authUser() ?? [];

        $updatePermit = $pdo->prepare(
            'UPDATE research_permit_letters
             SET research_title = :research_title,
                 research_location = :research_location,
                 start_date = :start_date,
                 end_date = :end_date,
                 researcher_name = :researcher_name,
                 institution = :institution,
                 supervisor = :supervisor,
                 research_scheme = :research_scheme,
                 funding_source = :funding_source,
                 research_year = :research_year,
                 phone = :phone,
                 unit = :unit,
                 faculty = :faculty,
                 purpose = :purpose,
                 destination_position = :destination_position,
                 address = :address,
                 city = :city,
                 attachment_file = :attachment_file,
                 notes = :notes,
                 applicant_email = :applicant_email,
                 members = :members
             WHERE letter_id = :letter_id'
        );
        $updatePermit->execute([
            ':letter_id' => $letterId,
            ':research_title' => (string) ($activityRow['judul'] ?? '-'),
            ':research_location' => (string) ($activityRow['lokasi'] ?? '-'),
            ':start_date' => (string) ($activityRow['tanggal_mulai'] ?? date('Y-m-d')),
            ':end_date' => (string) ($activityRow['tanggal_selesai'] ?? date('Y-m-d')),
            ':researcher_name' => (string) ($activityRow['ketua'] ?? ($authUser['name'] ?? '-')),
            ':institution' => (string) ($activityRow['mitra'] ?? 'LPPM'),
            ':supervisor' => (string) ($activityRow['ketua'] ?? ($authUser['name'] ?? '-')),
            ':research_scheme' => $researchScheme !== '' ? $researchScheme : '-',
            ':funding_source' => $fundingSource !== '' ? $fundingSource : '-',
            ':research_year' => (string) ($activityRow['tahun'] ?? date('Y')),
            ':phone' => (string) (($letter['phone'] ?? '') !== '' ? $letter['phone'] : ($authUser['phone'] ?? '-')),
            ':unit' => (string) (($letter['unit'] ?? '') !== '' ? $letter['unit'] : ($authUser['unit'] ?? '-')),
            ':faculty' => (string) (($letter['faculty'] ?? '') !== '' ? $letter['faculty'] : ($authUser['faculty'] ?? '-')),
            ':purpose' => (string) ($activityRow['deskripsi'] ?? '-'),
            ':destination_position' => (string) (($letter['destination_position'] ?? '') !== '' ? $letter['destination_position'] : 'Kepala LPPM'),
            ':address' => (string) ($activityRow['lokasi'] ?? '-'),
            ':city' => (string) ($activityRow['lokasi'] ?? '-'),
            ':attachment_file' => $attachmentValue,
            ':notes' => 'Ajuan kontrak dari menu Ajukan Surat. ' . $activityRef . ' ' . $contractSourceRef,
            ':applicant_email' => (string) (($letter['applicant_email'] ?? '') !== '' ? $letter['applicant_email'] : ($authUser['email'] ?? '-')),
            ':members' => (string) ($activityRow['anggota'] ?? '-'),
        ]);

        $updateLetter = $pdo->prepare(
            'UPDATE letters
             SET institution = :institution,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id
               AND applicant_id = :user_id'
        );
        $updateLetter->execute([
            ':institution' => (string) ($activityRow['mitra'] ?? 'LPPM'),
            ':status' => 'diajukan',
            ':id' => $letterId,
            ':user_id' => $userId,
        ]);
    }

    private function composeActivityAttachmentValue(array $activityRow): ?string
    {
        $proposal = trim((string) ($activityRow['file_proposal'] ?? ''));
        $instrumen = trim((string) ($activityRow['file_instrumen'] ?? ''));
        $pendukung = trim((string) ($activityRow['file_pendukung_lain'] ?? ''));

        if ($proposal === '' && $instrumen === '' && $pendukung === '') {
            return null;
        }

        return json_encode([
            'file_proposal' => $proposal,
            'file_instrumen' => $instrumen,
            'file_pendukung_lain' => $pendukung,
        ], JSON_UNESCAPED_SLASHES);
    }

    private function redirectListError(string $message): void
    {
        $this->redirectToPath($this->routePath($this->routes()['index']), ['error' => $message]);
    }

    private function guardDosen(): void
    {
        if (authRole() !== 'dosen') {
            $this->redirectToPath($this->adminDashboardPath());
        }
    }

    private function routePath(string $routeKey): string
    {
        return match ($routeKey) {
            'data-penelitian' => 'data/penelitian',
            'data-penelitian-create' => 'data/penelitian/create',
            'data-penelitian-edit' => 'data/penelitian',
            'data-pengabdian' => 'data/pengabdian',
            'data-pengabdian-create' => 'data/pengabdian/create',
            'data-pengabdian-edit' => 'data/pengabdian',
            'data-hilirisasi' => 'data/hilirisasi',
            'data-hilirisasi-create' => 'data/hilirisasi/create',
            'data-hilirisasi-edit' => 'data/hilirisasi',
            default => '',
        };
    }

    private function hydrateTargetLuaranFromDescription(array $formData): array
    {
        $normalizeTargetCodesForForm = static function (array $items): array {
            $normalized = [];
            foreach ($items as $item) {
                $code = trim((string) $item);
                if ($code === '') {
                    continue;
                }
                // Kompatibilitas data lama: "prototipe" ditampilkan sebagai "hilirisasi" pada form terbaru.
                if (strtolower($code) === 'prototipe') {
                    $code = 'hilirisasi';
                }
                $normalized[] = $code;
            }
            return array_values(array_unique($normalized));
        };

        $rawWajib = $formData['target_luaran_wajib'] ?? [];
        $rawTambahan = $formData['target_luaran_tambahan'] ?? [];
        $hasWajib = is_array($rawWajib) ? !empty(array_filter($rawWajib, static fn ($v): bool => trim((string) $v) !== '')) : trim((string) $rawWajib) !== '';
        $hasTambahan = is_array($rawTambahan) ? !empty(array_filter($rawTambahan, static fn ($v): bool => trim((string) $v) !== '')) : trim((string) $rawTambahan) !== '';

        if ($hasWajib || $hasTambahan) {
            if (!is_array($formData['target_luaran_wajib'] ?? null)) {
                $single = trim((string) ($formData['target_luaran_wajib'] ?? ''));
                $formData['target_luaran_wajib'] = $single === '' ? [] : [$single];
            }
            if (!is_array($formData['target_luaran_tambahan'] ?? null)) {
                $single = trim((string) ($formData['target_luaran_tambahan'] ?? ''));
                $formData['target_luaran_tambahan'] = $single === '' ? [] : [$single];
            }
            $formData['target_luaran_wajib'] = $normalizeTargetCodesForForm((array) $formData['target_luaran_wajib']);
            $formData['target_luaran_tambahan'] = $normalizeTargetCodesForForm((array) $formData['target_luaran_tambahan']);
            return $formData;
        }

        $deskripsi = trim((string) ($formData['deskripsi'] ?? ''));
        if ($deskripsi === '') {
            return $formData;
        }

        $lines = preg_split('/\r\n|\r|\n/', $deskripsi) ?: [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (stripos($line, 'Luaran Wajib:') === 0) {
                $rawValue = trim(substr($line, strlen('Luaran Wajib:')));
                $formData['target_luaran_wajib'] = array_values(array_filter(array_map('trim', explode('|', $rawValue)), static fn (string $item): bool => $item !== ''));
            } elseif (stripos($line, 'Luaran Tambahan:') === 0) {
                $rawValue = trim(substr($line, strlen('Luaran Tambahan:')));
                $formData['target_luaran_tambahan'] = array_values(array_filter(array_map('trim', explode('|', $rawValue)), static fn (string $item): bool => $item !== ''));
            }
        }

        if (!isset($formData['target_luaran_wajib']) || !is_array($formData['target_luaran_wajib'])) {
            $formData['target_luaran_wajib'] = [];
        }
        if (!isset($formData['target_luaran_tambahan']) || !is_array($formData['target_luaran_tambahan'])) {
            $formData['target_luaran_tambahan'] = [];
        }
        $formData['target_luaran_wajib'] = $normalizeTargetCodesForForm((array) $formData['target_luaran_wajib']);
        $formData['target_luaran_tambahan'] = $normalizeTargetCodesForForm((array) $formData['target_luaran_tambahan']);

        return $formData;
    }
}
