<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ActivityCategoryModel.php';
require_once __DIR__ . '/../Models/OutputTypeModel.php';
require_once __DIR__ . '/../Models/ActivityCategoryOutputModel.php';
require_once __DIR__ . '/../Models/ActivitySchemeModel.php';
require_once __DIR__ . '/../Models/ActivityScopeModel.php';
require_once __DIR__ . '/../Models/FundingSourceModel.php';
require_once __DIR__ . '/../Helpers/ActivityLogHelper.php';

class MasterDataController extends BaseController
{
    private ActivityCategoryModel $categoryModel;
    private OutputTypeModel $outputTypeModel;
    private ActivityCategoryOutputModel $categoryOutputModel;
    private ActivitySchemeModel $schemeModel;
    private ActivityScopeModel $scopeModel;
    private FundingSourceModel $fundingSourceModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new ActivityCategoryModel();
        $this->outputTypeModel = new OutputTypeModel();
        $this->categoryOutputModel = new ActivityCategoryOutputModel();
        $this->schemeModel = new ActivitySchemeModel();
        $this->scopeModel = new ActivityScopeModel();
        $this->fundingSourceModel = new FundingSourceModel();
    }

    public function outputs(): void
    {
        $this->guardAdmin();

        $this->render('master_data/outputs', [
            'pageTitle' => 'Master Jenis Luaran',
            'pageSubtitle' => 'Kelola daftar jenis luaran yang dapat dipilih pada penelitian, pengabdian, dan hilirisasi.',
            'sectionKey' => 'outputs',
            'categories' => $this->categoryModel->getAll(),
            'items' => $this->buildOutputRows(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function createOutput(): void
    {
        $this->guardAdmin();

        $this->render('master_data/output_form', [
            'pageTitle' => 'Tambah Jenis Luaran',
            'pageSubtitle' => 'Tambahkan jenis luaran baru yang dapat dipilih pada penelitian, pengabdian, dan hilirisasi.',
            'sectionKey' => 'outputs',
            'categories' => $this->categoryModel->getAll(),
            'formValues' => [
                'code' => '',
                'name' => '',
                'description' => '',
                'sort_order' => 1,
                'is_active' => 1,
                'allow_required' => 1,
                'allow_additional' => 1,
            ],
            'selectedCategories' => ['penelitian', 'pengabdian', 'hilirisasi'],
            'savePath' => 'master-data/luaran/simpan',
            'backPath' => 'master-data/luaran',
            'formMode' => 'create',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function editOutput(): void
    {
        $this->guardAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $this->outputTypeModel->findById($id) : null;
        if ($item === null) {
            $this->redirectToPath('master-data/luaran', ['error' => 'Jenis luaran tidak ditemukan.']);
        }

        $this->render('master_data/output_form', [
            'pageTitle' => 'Edit Jenis Luaran',
            'pageSubtitle' => 'Perbarui jenis luaran yang sudah tersedia pada sistem.',
            'sectionKey' => 'outputs',
            'categories' => $this->categoryModel->getAll(),
            'formValues' => $item,
            'selectedCategories' => $this->categoryOutputModel->getCategoryCodesForOutput((int) ($item['id'] ?? 0)),
            'savePath' => 'master-data/luaran/simpan',
            'backPath' => 'master-data/luaran',
            'formMode' => 'edit',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function saveOutput(): void
    {
        $this->guardAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/luaran');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $code = trim((string) ($_POST['code'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = max(1, (int) ($_POST['sort_order'] ?? 1));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $allowRequired = isset($_POST['allow_required']) ? 1 : 0;
        $allowAdditional = isset($_POST['allow_additional']) ? 1 : 0;
        $categoryCodes = is_array($_POST['activity_categories'] ?? null) ? (array) $_POST['activity_categories'] : [];

        try {
            if ($allowRequired !== 1 && $allowAdditional !== 1) {
                throw new RuntimeException('Minimal satu jenis pemakaian luaran harus dipilih.');
            }
            if ($categoryCodes === []) {
                throw new RuntimeException('Minimal satu kategori kegiatan harus dipilih.');
            }

            $savedId = $this->outputTypeModel->saveOutputType([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'allow_required' => $allowRequired,
                'allow_additional' => $allowAdditional,
            ], $id > 0 ? $id : null);

            $this->categoryOutputModel->syncCategoriesForOutput($savedId, $categoryCodes);
            logActivity('master-data', ($id > 0 ? 'Memperbarui' : 'Menambahkan') . ' master jenis luaran: ' . $name, $savedId);

            $this->redirectToPath('master-data/luaran', ['success' => 'Master jenis luaran berhasil disimpan.']);
        } catch (Throwable $e) {
            $targetPath = $id > 0 ? 'master-data/luaran/' . $id . '/edit' : 'master-data/luaran/create';
            $this->redirectToPath($targetPath, ['error' => $e->getMessage()]);
        }
    }

    public function deleteOutput(): void
    {
        $this->guardAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/luaran');
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $item = $this->outputTypeModel->findById($id);
            if ($item === null) {
                throw new RuntimeException('Jenis luaran tidak ditemukan.');
            }

            $this->outputTypeModel->deleteOutputType($id);
            logActivity('master-data', 'Menghapus master jenis luaran: ' . (string) ($item['name'] ?? ('ID ' . $id)), $id);

            $this->redirectToPath('master-data/luaran', ['success' => 'Master jenis luaran berhasil dihapus.']);
        } catch (Throwable $e) {
            $this->redirectToPath('master-data/luaran', ['error' => $e->getMessage()]);
        }
    }

    public function schemes(): void
    {
        $this->guardAdmin();

        $this->render('master_data/schemes', [
            'pageTitle' => 'Master Skema',
            'pageSubtitle' => 'Kelola skema kegiatan untuk penelitian, pengabdian, dan hilirisasi.',
            'sectionKey' => 'schemes',
            'categories' => $this->categoryModel->getAll(),
            'items' => $this->schemeModel->getAll(),
            'scopeCounts' => $this->countScopesPerScheme(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function createScheme(): void
    {
        $this->guardAdmin();

        $this->render('master_data/scheme_form', [
            'pageTitle' => 'Tambah Skema',
            'pageSubtitle' => 'Tambahkan skema kegiatan baru untuk kategori yang dipilih.',
            'sectionKey' => 'schemes',
            'categories' => $this->categoryModel->getAll(),
            'formValues' => [
                'activity_category_code' => 'penelitian',
                'code' => '',
                'name' => '',
                'description' => '',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            'savePath' => 'master-data/skema/simpan',
            'backPath' => 'master-data/skema',
            'formMode' => 'create',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function editScheme(): void
    {
        $this->guardAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $this->schemeModel->findById($id) : null;
        if ($item === null) {
            $this->redirectToPath('master-data/skema', ['error' => 'Skema tidak ditemukan.']);
        }

        $this->render('master_data/scheme_form', [
            'pageTitle' => 'Edit Skema',
            'pageSubtitle' => 'Perbarui skema kegiatan yang sudah tersedia.',
            'sectionKey' => 'schemes',
            'categories' => $this->categoryModel->getAll(),
            'formValues' => $item,
            'savePath' => 'master-data/skema/simpan',
            'backPath' => 'master-data/skema',
            'formMode' => 'edit',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function saveScheme(): void
    {
        $this->guardAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/skema');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        try {
            $savedId = $this->schemeModel->saveScheme([
                'activity_category_code' => $_POST['activity_category_code'] ?? '',
                'code' => trim((string) ($_POST['code'] ?? '')),
                'name' => $name,
                'description' => trim((string) ($_POST['description'] ?? '')),
                'sort_order' => max(1, (int) ($_POST['sort_order'] ?? 1)),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], $id > 0 ? $id : null);

            logActivity('master-data', ($id > 0 ? 'Memperbarui' : 'Menambahkan') . ' master skema: ' . $name, $savedId);
            $this->redirectToPath('master-data/skema', ['success' => 'Master skema berhasil disimpan.']);
        } catch (Throwable $e) {
            $targetPath = $id > 0 ? 'master-data/skema/' . $id . '/edit' : 'master-data/skema/create';
            $this->redirectToPath($targetPath, ['error' => $e->getMessage()]);
        }
    }

    public function deleteScheme(): void
    {
        $this->guardAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/skema');
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $item = $this->schemeModel->findById($id);
            if ($item === null) {
                throw new RuntimeException('Skema tidak ditemukan.');
            }
            $this->schemeModel->deleteScheme($id);
            logActivity('master-data', 'Menghapus master skema: ' . (string) ($item['name'] ?? ('ID ' . $id)), $id);

            $this->redirectToPath('master-data/skema', ['success' => 'Master skema berhasil dihapus.']);
        } catch (Throwable $e) {
            $this->redirectToPath('master-data/skema', ['error' => $e->getMessage()]);
        }
    }

    public function scopes(): void
    {
        $this->guardAdmin();

        $this->render('master_data/scopes', [
            'pageTitle' => 'Master Ruang Lingkup',
            'pageSubtitle' => 'Kelola ruang lingkup berdasarkan skema kegiatan yang berlaku.',
            'sectionKey' => 'scopes',
            'items' => $this->scopeModel->getAll(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function createScope(): void
    {
        $this->guardAdmin();

        $this->render('master_data/scope_form', [
            'pageTitle' => 'Tambah Ruang Lingkup',
            'pageSubtitle' => 'Tambahkan ruang lingkup baru yang terkait ke skema kegiatan tertentu.',
            'sectionKey' => 'scopes',
            'schemes' => $this->schemeModel->getAll(null, true),
            'formValues' => [
                'scheme_id' => 0,
                'code' => '',
                'name' => '',
                'description' => '',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            'savePath' => 'master-data/ruang-lingkup/simpan',
            'backPath' => 'master-data/ruang-lingkup',
            'formMode' => 'create',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function editScope(): void
    {
        $this->guardAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $this->scopeModel->findById($id) : null;
        if ($item === null) {
            $this->redirectToPath('master-data/ruang-lingkup', ['error' => 'Ruang lingkup tidak ditemukan.']);
        }

        $this->render('master_data/scope_form', [
            'pageTitle' => 'Edit Ruang Lingkup',
            'pageSubtitle' => 'Perbarui ruang lingkup yang sudah tersedia pada skema kegiatan.',
            'sectionKey' => 'scopes',
            'schemes' => $this->schemeModel->getAll(null, true),
            'formValues' => $item,
            'savePath' => 'master-data/ruang-lingkup/simpan',
            'backPath' => 'master-data/ruang-lingkup',
            'formMode' => 'edit',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function saveScope(): void
    {
        $this->guardAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/ruang-lingkup');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        try {
            $savedId = $this->scopeModel->saveScope([
                'scheme_id' => (int) ($_POST['scheme_id'] ?? 0),
                'code' => trim((string) ($_POST['code'] ?? '')),
                'name' => $name,
                'description' => trim((string) ($_POST['description'] ?? '')),
                'sort_order' => max(1, (int) ($_POST['sort_order'] ?? 1)),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], $id > 0 ? $id : null);

            logActivity('master-data', ($id > 0 ? 'Memperbarui' : 'Menambahkan') . ' master ruang lingkup: ' . $name, $savedId);
            $this->redirectToPath('master-data/ruang-lingkup', ['success' => 'Master ruang lingkup berhasil disimpan.']);
        } catch (Throwable $e) {
            $targetPath = $id > 0 ? 'master-data/ruang-lingkup/' . $id . '/edit' : 'master-data/ruang-lingkup/create';
            $this->redirectToPath($targetPath, ['error' => $e->getMessage()]);
        }
    }

    public function deleteScope(): void
    {
        $this->guardAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/ruang-lingkup');
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $item = $this->scopeModel->findById($id);
            if ($item === null) {
                throw new RuntimeException('Ruang lingkup tidak ditemukan.');
            }
            $this->scopeModel->deleteScope($id);
            logActivity('master-data', 'Menghapus master ruang lingkup: ' . (string) ($item['name'] ?? ('ID ' . $id)), $id);

            $this->redirectToPath('master-data/ruang-lingkup', ['success' => 'Master ruang lingkup berhasil dihapus.']);
        } catch (Throwable $e) {
            $this->redirectToPath('master-data/ruang-lingkup', ['error' => $e->getMessage()]);
        }
    }

    public function fundingSources(): void
    {
        $this->guardAdmin();

        $this->render('master_data/funding_sources', [
            'pageTitle' => 'Master Sumber Dana',
            'pageSubtitle' => 'Kelola sumber dana yang tersedia untuk setiap kategori kegiatan.',
            'sectionKey' => 'funding-sources',
            'categories' => $this->categoryModel->getAll(),
            'items' => $this->fundingSourceModel->getAll(),
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function createFundingSource(): void
    {
        $this->guardAdmin();

        $this->render('master_data/funding_source_form', [
            'pageTitle' => 'Tambah Sumber Dana',
            'pageSubtitle' => 'Tambahkan sumber dana baru untuk kategori kegiatan yang dipilih.',
            'sectionKey' => 'funding-sources',
            'categories' => $this->categoryModel->getAll(),
            'formValues' => [
                'activity_category_code' => 'penelitian',
                'code' => '',
                'name' => '',
                'description' => '',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            'savePath' => 'master-data/sumber-dana/simpan',
            'backPath' => 'master-data/sumber-dana',
            'formMode' => 'create',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function editFundingSource(): void
    {
        $this->guardAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $this->fundingSourceModel->findById($id) : null;
        if ($item === null) {
            $this->redirectToPath('master-data/sumber-dana', ['error' => 'Sumber dana tidak ditemukan.']);
        }

        $this->render('master_data/funding_source_form', [
            'pageTitle' => 'Edit Sumber Dana',
            'pageSubtitle' => 'Perbarui sumber dana yang tersedia pada sistem.',
            'sectionKey' => 'funding-sources',
            'categories' => $this->categoryModel->getAll(),
            'formValues' => $item,
            'savePath' => 'master-data/sumber-dana/simpan',
            'backPath' => 'master-data/sumber-dana',
            'formMode' => 'edit',
            'successMessage' => $_GET['success'] ?? null,
            'errorMessage' => $_GET['error'] ?? null,
        ]);
    }

    public function saveFundingSource(): void
    {
        $this->guardAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/sumber-dana');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        try {
            $savedId = $this->fundingSourceModel->saveFundingSource([
                'activity_category_code' => $_POST['activity_category_code'] ?? '',
                'code' => trim((string) ($_POST['code'] ?? '')),
                'name' => $name,
                'description' => trim((string) ($_POST['description'] ?? '')),
                'sort_order' => max(1, (int) ($_POST['sort_order'] ?? 1)),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ], $id > 0 ? $id : null);

            logActivity('master-data', ($id > 0 ? 'Memperbarui' : 'Menambahkan') . ' master sumber dana: ' . $name, $savedId);
            $this->redirectToPath('master-data/sumber-dana', ['success' => 'Master sumber dana berhasil disimpan.']);
        } catch (Throwable $e) {
            $targetPath = $id > 0 ? 'master-data/sumber-dana/' . $id . '/edit' : 'master-data/sumber-dana/create';
            $this->redirectToPath($targetPath, ['error' => $e->getMessage()]);
        }
    }

    public function deleteFundingSource(): void
    {
        $this->guardAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('master-data/sumber-dana');
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $item = $this->fundingSourceModel->findById($id);
            if ($item === null) {
                throw new RuntimeException('Sumber dana tidak ditemukan.');
            }
            $this->fundingSourceModel->deleteFundingSource($id);
            logActivity('master-data', 'Menghapus master sumber dana: ' . (string) ($item['name'] ?? ('ID ' . $id)), $id);

            $this->redirectToPath('master-data/sumber-dana', ['success' => 'Master sumber dana berhasil dihapus.']);
        } catch (Throwable $e) {
            $this->redirectToPath('master-data/sumber-dana', ['error' => $e->getMessage()]);
        }
    }

    private function guardAdmin(): void
    {
        if (normalizeRoleName((string) authRole()) !== 'admin') {
            $this->redirectToPath($this->adminDashboardPath());
        }
    }

    private function buildOutputRows(): array
    {
        $rows = [];
        foreach ($this->outputTypeModel->getAll() as $item) {
            $item['activity_categories'] = $this->categoryOutputModel->getCategoryCodesForOutput((int) ($item['id'] ?? 0));
            $rows[] = $item;
        }

        return $rows;
    }

    private function countScopesPerScheme(): array
    {
        $counts = [];
        foreach ($this->scopeModel->getAll() as $item) {
            $schemeId = (int) ($item['scheme_id'] ?? 0);
            if ($schemeId > 0) {
                $counts[$schemeId] = (int) ($counts[$schemeId] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
