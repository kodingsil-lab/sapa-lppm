<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/NomorSuratSettingModel.php';
require_once __DIR__ . '/../Models/NomorSuratModel.php';
require_once __DIR__ . '/../Models/ContractSettingModel.php';

class SettingController extends BaseController
{
    private NomorSuratSettingModel $settingModel;
    private NomorSuratModel $nomorSuratModel;
    private ContractSettingModel $contractSettingModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new NomorSuratSettingModel();
        $this->nomorSuratModel = new NomorSuratModel();
        $this->contractSettingModel = new ContractSettingModel();
    }

    public function letterNumber(): void
    {
        if (!$this->canManageSettings()) {
            $this->redirectToPath($this->adminDashboardPath());
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveLetterNumberSettings();

            return;
        }

        $year = (int) ($_GET['tahun'] ?? date('Y'));
        $activeTab = strtoupper(trim((string) ($_GET['tab'] ?? 'K')));
        $queryValidation = $this->validatePayload(
            [
                'tahun' => (string) $year,
                'tab' => $activeTab,
            ],
            [
                'tahun' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
                'tab' => 'required|in_list[K,I,T]',
            ]
        );
        if (!$queryValidation['valid']) {
            $year = (int) date('Y');
            $activeTab = 'K';
        }
        $settings = $this->settingModel->getAll();
        $overview = $this->nomorSuratModel->getOverviewByYear($year);
        $recentByJenis = [
            'K' => $this->nomorSuratModel->getRecentByJenis('K', $year, 20),
            'I' => $this->nomorSuratModel->getRecentByJenis('I', $year, 20),
            'T' => $this->nomorSuratModel->getRecentByJenis('T', $year, 20),
        ];

        $this->render('settings/letter_number', [
            'pageTitle' => 'Pengaturan Nomor Surat',
            'settings' => $settings,
            'overview' => $overview,
            'recentByJenis' => $recentByJenis,
            'selectedYear' => $year,
            'activeTab' => $activeTab,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    private function saveLetterNumberSettings(): void
    {
        $rows = $_POST['settings'] ?? [];
        $activeTab = strtoupper(trim((string) ($_POST['active_tab'] ?? $_GET['tab'] ?? 'K')));
        $tahun = (int) ($_GET['tahun'] ?? $_POST['tahun'] ?? date('Y'));
        $paramValidation = $this->validatePayload(
            [
                'tab' => $activeTab,
                'tahun' => (string) $tahun,
            ],
            [
                'tab' => 'required|in_list[K,I,T]',
                'tahun' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            ]
        );
        if (!$paramValidation['valid']) {
            $this->redirectToPath('pengaturan/nomor-surat', [
                'error' => $this->firstValidationError($paramValidation['errors'], 'Parameter tidak valid.'),
            ]);
        }

        if (!is_array($rows) || empty($rows)) {
            $this->redirectToPath('pengaturan/nomor-surat', [
                'tahun' => $tahun,
                'tab' => $activeTab,
                'error' => 'Data pengaturan tidak ditemukan.',
            ]);
        }

        $requiredTokens = ['{nomor_urut}', '{jenis_surat}', '{skema}', '{bulan_romawi}', '{tahun}'];

        try {
            foreach ($rows as $raw) {
                if (!is_array($raw)) {
                    continue;
                }

                $jenis = strtoupper(trim((string) ($raw['jenis_surat'] ?? '')));
                $nama = trim((string) ($raw['nama_jenis'] ?? ''));
                $format = trim((string) ($raw['format_template'] ?? ''));
                $isActive = in_array((string) ($raw['is_active'] ?? '1'), ['1', 'true', 'on'], true);

                $rowValidation = $this->validatePayload(
                    [
                        'jenis_surat' => $jenis,
                        'nama_jenis' => $nama,
                        'format_template' => $format,
                    ],
                    [
                        'jenis_surat' => 'required|in_list[K,I,T]',
                        'nama_jenis' => 'required|max_length[120]',
                        'format_template' => 'required|min_length[15]|max_length[255]',
                    ]
                );
                if (!$rowValidation['valid']) {
                    throw new InvalidArgumentException($this->firstValidationError($rowValidation['errors'], 'Data pengaturan nomor surat tidak valid.'));
                }
                foreach ($requiredTokens as $token) {
                    if (!str_contains($format, $token)) {
                        throw new InvalidArgumentException('Format untuk jenis ' . $jenis . ' wajib memuat token ' . $token . '.');
                    }
                }

                $this->settingModel->upsert($jenis, $nama, $format, $isActive);
            }

            $this->redirectToPath('pengaturan/nomor-surat', [
                'tahun' => $tahun,
                'tab' => $activeTab,
                'success' => 'Pengaturan nomor surat berhasil disimpan.',
            ]);
        } catch (Throwable $e) {
            $this->redirectToPath('pengaturan/nomor-surat', [
                'tahun' => $tahun,
                'tab' => $activeTab,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function contract(): void
    {
        if (!$this->canManageSettings()) {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $scope = ContractSettingModel::normalizeScope((string) ($_GET['scope'] ?? ContractSettingModel::SCOPE_PENELITIAN));
        $selectedYear = (int) ($_GET['tahun_anggaran'] ?? date('Y'));
        $activeTab = ContractSettingModel::SOURCE_DIKTI;
        $viewMode = strtolower(trim((string) ($_GET['mode'] ?? 'edit')));
        $queryValidation = $this->validatePayload(
            [
                'scope' => $scope,
                'tahun_anggaran' => (string) $selectedYear,
                'mode' => $viewMode,
            ],
            [
                'scope' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'tahun_anggaran' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
                'mode' => 'required|in_list[edit,detail]',
            ]
        );
        if (!$queryValidation['valid']) {
            $selectedYear = (int) date('Y');
            $viewMode = 'edit';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveContractSettings();
            return;
        }

        $scopeTitleMap = [
            ContractSettingModel::SCOPE_PENELITIAN => 'Seting Kontrak Penelitian',
            ContractSettingModel::SCOPE_PENGABDIAN => 'Seting Kontrak Pengabdian',
            ContractSettingModel::SCOPE_HILIRISASI => 'Seting Kontrak Hilirisasi',
        ];

        $this->render('settings/contract', [
            'pageTitle' => (string) ($scopeTitleMap[$scope] ?? $scopeTitleMap[ContractSettingModel::SCOPE_PENELITIAN]),
            'settingsBySource' => $this->contractSettingModel->getAllBySourceForYear($selectedYear, $scope),
            'selectedYear' => $selectedYear,
            'activeTab' => $activeTab,
            'scope' => $scope,
            'viewMode' => $viewMode,
            'yearSummaryRowsBySource' => [
                ContractSettingModel::SOURCE_DIKTI => $this->contractSettingModel->getYearListSummaryBySource(ContractSettingModel::SOURCE_DIKTI, $scope),
            ],
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function contractDetail(): void
    {
        if (!$this->canManageSettings()) {
            $this->redirectToPath($this->adminDashboardPath());
        }

        $scope = ContractSettingModel::normalizeScope((string) ($_GET['scope'] ?? ContractSettingModel::SCOPE_PENELITIAN));
        $selectedYear = (int) ($_GET['tahun_anggaran'] ?? 0);
        $queryValidation = $this->validatePayload(
            [
                'scope' => $scope,
                'tahun_anggaran' => (string) $selectedYear,
            ],
            [
                'scope' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                'tahun_anggaran' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            ]
        );
        if (!$queryValidation['valid']) {
            $this->redirectToPath('pengaturan/kontrak', [
                'scope' => $scope,
                'error' => $this->firstValidationError($queryValidation['errors'], 'Tahun anggaran tidak valid.'),
            ]);
        }

        $sourceKey = ContractSettingModel::SOURCE_DIKTI;
        $viewMode = strtolower(trim((string) ($_GET['mode'] ?? 'detail')));
        if (!in_array($viewMode, ['detail', 'edit'], true)) {
            $viewMode = 'detail';
        }

        $settings = $this->contractSettingModel->getBySourceAndYear($sourceKey, $selectedYear, $scope);
        $hasStoredData = trim((string) ($settings['nomor_kontrak_dikti'] ?? '')) !== ''
            || trim((string) ($settings['nomor_kontrak_lldikti_xv'] ?? '')) !== ''
            || trim((string) ($settings['tanggal_mulai_global'] ?? '')) !== ''
            || trim((string) ($settings['tanggal_selesai_global'] ?? '')) !== ''
            || trim((string) ($settings['updated_at'] ?? '')) !== '';

        if (!$hasStoredData) {
            $this->redirectToPath('pengaturan/kontrak', [
                'scope' => $scope,
                'tab' => $sourceKey,
                'error' => 'Detail seting kontrak tidak ditemukan.',
            ]);
        }

        $this->render('settings/contract_detail', [
            'pageTitle' => match ($scope) {
                ContractSettingModel::SCOPE_PENGABDIAN => 'Detail Seting Kontrak Pengabdian',
                ContractSettingModel::SCOPE_HILIRISASI => 'Detail Seting Kontrak Hilirisasi',
                default => 'Detail Seting Kontrak Penelitian',
            },
            'selectedYear' => $selectedYear,
            'sourceKey' => $sourceKey,
            'sourceLabel' => 'Hibah Dikti',
            'scope' => $scope,
            'setting' => $settings,
            'viewMode' => $viewMode,
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    private function saveContractSettings(): void
    {
        try {
            $scope = ContractSettingModel::normalizeScope((string) ($_POST['scope'] ?? ContractSettingModel::SCOPE_PENELITIAN));
            $selectedYear = (int) ($_POST['tahun_anggaran'] ?? date('Y'));
            $paramValidation = $this->validatePayload(
                [
                    'scope' => $scope,
                    'tahun_anggaran' => (string) $selectedYear,
                ],
                [
                    'scope' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                    'tahun_anggaran' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
                ]
            );
            if (!$paramValidation['valid']) {
                throw new InvalidArgumentException($this->firstValidationError($paramValidation['errors'], 'Tahun anggaran tidak valid.'));
            }
            $activeTab = ContractSettingModel::SOURCE_DIKTI;
            $redirectTarget = strtolower(trim((string) ($_POST['redirect_target'] ?? 'form')));
            if (!in_array($redirectTarget, ['form', 'detail'], true)) {
                $redirectTarget = 'form';
            }

            $rawSettings = $_POST['settings'] ?? [];
            if (!is_array($rawSettings) || empty($rawSettings)) {
                throw new InvalidArgumentException('Data seting kontrak tidak ditemukan.');
            }

            $sourceLabel = 'Hibah Dikti';
            $raw = is_array($rawSettings[$activeTab] ?? null) ? $rawSettings[$activeTab] : [];
            $payload = [
                'nomor_kontrak_dikti' => trim((string) ($raw['nomor_kontrak_dikti'] ?? '')),
                'nomor_kontrak_lldikti_xv' => trim((string) ($raw['nomor_kontrak_lldikti_xv'] ?? '')),
                'hari_penandatanganan' => trim((string) ($raw['hari_penandatanganan'] ?? '')),
                'tanggal_penandatanganan' => trim((string) ($raw['tanggal_penandatanganan'] ?? '')),
                'tanggal_mulai_global' => trim((string) ($raw['tanggal_mulai_global'] ?? '')),
                'tanggal_selesai_global' => trim((string) ($raw['tanggal_selesai_global'] ?? '')),
                'batas_tanggal_tahap_1' => trim((string) ($raw['batas_tanggal_tahap_1'] ?? '')),
                'batas_tanggal_tahap_2' => trim((string) ($raw['batas_tanggal_tahap_2'] ?? '')),
                'batas_upload_tahap_2' => trim((string) ($raw['batas_upload_tahap_2'] ?? '')),
                'batas_laporan_akhir' => trim((string) ($raw['batas_laporan_akhir'] ?? '')),
                'persentase_tahap_1' => trim((string) ($raw['persentase_tahap_1'] ?? '')),
                'persentase_tahap_2' => trim((string) ($raw['persentase_tahap_2'] ?? '')),
            ];

            $requiredLabels = [
                'hari_penandatanganan' => 'Hari Penandatanganan Kontrak',
                'tanggal_penandatanganan' => 'Tanggal Penandatanganan Kontrak',
                'persentase_tahap_1' => 'Persentase Pencairan Tahap 1',
                'persentase_tahap_2' => 'Persentase Pencairan Tahap 2',
            ];
            if (in_array($scope, [
                ContractSettingModel::SCOPE_HILIRISASI,
                ContractSettingModel::SCOPE_PENELITIAN,
                ContractSettingModel::SCOPE_PENGABDIAN,
            ], true)) {
                $requiredLabels += [
                    'tanggal_mulai_global' => 'Tanggal Mulai Kegiatan',
                    'tanggal_selesai_global' => 'Tanggal Selesai Kegiatan',
                ];
            }
            $requiredLabels = [
                'nomor_kontrak_dikti' => 'Nomor Kontrak Dikti',
                'nomor_kontrak_lldikti_xv' => 'Nomor Kontrak LLDIKTI XV',
            ] + $requiredLabels;

            foreach ($requiredLabels as $fieldKey => $fieldLabel) {
                if ($payload[$fieldKey] === '') {
                    throw new InvalidArgumentException($fieldLabel . ' wajib diisi untuk sumber dana ' . $sourceLabel . '.');
                }
            }
            $payloadValidation = $this->validatePayload(
                [
                    'nomor_kontrak_dikti' => $payload['nomor_kontrak_dikti'],
                    'nomor_kontrak_lldikti_xv' => $payload['nomor_kontrak_lldikti_xv'],
                    'hari_penandatanganan' => $payload['hari_penandatanganan'],
                    'tanggal_penandatanganan' => $payload['tanggal_penandatanganan'],
                    'tanggal_mulai_global' => $payload['tanggal_mulai_global'],
                    'tanggal_selesai_global' => $payload['tanggal_selesai_global'],
                    'persentase_tahap_1' => $payload['persentase_tahap_1'],
                    'persentase_tahap_2' => $payload['persentase_tahap_2'],
                ],
                [
                    'nomor_kontrak_dikti' => 'required|max_length[200]',
                    'nomor_kontrak_lldikti_xv' => 'required|max_length[200]',
                    'hari_penandatanganan' => 'required|max_length[40]',
                    'tanggal_penandatanganan' => 'required|valid_date[Y-m-d]',
                    'tanggal_mulai_global' => 'required|valid_date[Y-m-d]',
                    'tanggal_selesai_global' => 'required|valid_date[Y-m-d]',
                    'persentase_tahap_1' => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
                    'persentase_tahap_2' => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
                ]
            );
            if (!$payloadValidation['valid']) {
                throw new InvalidArgumentException($this->firstValidationError($payloadValidation['errors'], 'Data seting kontrak tidak valid.'));
            }

            $p1 = (float) $payload['persentase_tahap_1'];
            $p2 = (float) $payload['persentase_tahap_2'];
            if ($p1 <= 0 || $p2 <= 0) {
                throw new InvalidArgumentException('Persentase tahap harus lebih dari 0 untuk sumber dana ' . $sourceLabel . '.');
            }
            if (abs(($p1 + $p2) - 100.0) > 0.01) {
                throw new InvalidArgumentException('Total persentase tahap 1 + tahap 2 harus 100% untuk sumber dana ' . $sourceLabel . '.');
            }
            if (
                in_array($scope, [
                    ContractSettingModel::SCOPE_HILIRISASI,
                    ContractSettingModel::SCOPE_PENELITIAN,
                    ContractSettingModel::SCOPE_PENGABDIAN,
                ], true)
                && $payload['tanggal_mulai_global'] !== ''
                && $payload['tanggal_selesai_global'] !== ''
                && $payload['tanggal_mulai_global'] > $payload['tanggal_selesai_global']
            ) {
                throw new InvalidArgumentException('Tanggal Mulai Kegiatan tidak boleh melebihi Tanggal Selesai Kegiatan.');
            }

            $this->contractSettingModel->saveBySourceForYear($selectedYear, $scope, $activeTab, $sourceLabel, $payload);

            if ($redirectTarget === 'detail') {
                $this->redirectToPath('pengaturan/kontrak', [
                    'scope' => $scope,
                    'tahun_anggaran' => $selectedYear,
                    'tab' => $activeTab,
                    'mode' => 'detail',
                    'success' => 'Seting kontrak tahun anggaran ' . $selectedYear . ' berhasil disimpan.',
                ]);
            }

            $this->redirectToPath('pengaturan/kontrak', [
                'scope' => $scope,
                'tahun_anggaran' => $selectedYear,
                'tab' => $activeTab,
                'success' => 'Seting kontrak tahun anggaran ' . $selectedYear . ' berhasil disimpan.',
            ]);
        } catch (Throwable $e) {
            $scope = ContractSettingModel::normalizeScope((string) ($_POST['scope'] ?? ContractSettingModel::SCOPE_PENELITIAN));
            $fallbackYear = (int) ($_POST['tahun_anggaran'] ?? date('Y'));
            if ($fallbackYear < 2000 || $fallbackYear > 2100) {
                $fallbackYear = (int) date('Y');
            }
            $activeTab = ContractSettingModel::SOURCE_DIKTI;
            $redirectTarget = strtolower(trim((string) ($_POST['redirect_target'] ?? 'form')));
            if (!in_array($redirectTarget, ['form', 'detail'], true)) {
                $redirectTarget = 'form';
            }

            if ($redirectTarget === 'detail') {
                $this->redirectToPath('pengaturan/kontrak', [
                    'scope' => $scope,
                    'tahun_anggaran' => $fallbackYear,
                    'tab' => $activeTab,
                    'mode' => 'edit',
                    'error' => $e->getMessage(),
                ]);
            }
            $this->redirectToPath('pengaturan/kontrak', [
                'scope' => $scope,
                'tahun_anggaran' => $fallbackYear,
                'tab' => $activeTab,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteContractYear(): void
    {
        if (!$this->canManageSettings()) {
            $this->redirectToPath($this->adminDashboardPath());
        }

        try {
            $scope = ContractSettingModel::normalizeScope((string) ($_POST['scope'] ?? ContractSettingModel::SCOPE_PENELITIAN));
            $year = (int) ($_POST['tahun_anggaran'] ?? 0);
            $sourceKey = strtolower(trim((string) ($_POST['source_key'] ?? '')));
            $deleteValidation = $this->validatePayload(
                [
                    'scope' => $scope,
                    'tahun_anggaran' => (string) $year,
                    'source_key' => $sourceKey,
                ],
                [
                    'scope' => 'required|in_list[penelitian,pengabdian,hilirisasi]',
                    'tahun_anggaran' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
                    'source_key' => 'required|in_list[dikti]',
                ]
            );
            if (!$deleteValidation['valid']) {
                throw new InvalidArgumentException($this->firstValidationError($deleteValidation['errors'], 'Parameter hapus tidak valid.'));
            }

            $this->contractSettingModel->deleteBySourceAndYear($sourceKey, $year, $scope);
            $this->redirectToPath('pengaturan/kontrak', [
                'scope' => $scope,
                'tab' => $sourceKey,
                'success' => 'Seting kontrak tahun anggaran ' . $year . ' berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            $scope = ContractSettingModel::normalizeScope((string) ($_POST['scope'] ?? ContractSettingModel::SCOPE_PENELITIAN));
            $this->redirectToPath('pengaturan/kontrak', [
                'scope' => $scope,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function canManageSettings(): bool
    {
        return isAdminPanelRole((string) authRole());
    }
}
