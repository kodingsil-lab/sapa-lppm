<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/LetterModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ContractSettingModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';
require_once __DIR__ . '/../Helpers/AuthHelper.php';
require_once __DIR__ . '/../Helpers/terbilang_helper.php';
require_once __DIR__ . '/../Helpers/contract_pdf_helper.php';
require_once __DIR__ . '/../Services/EmailNotificationService.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class LetterPdfController extends BaseController
{
    private LetterModel $letterModel;
    private UserModel $userModel;
    private ContractSettingModel $contractSettingModel;
    private EmailNotificationService $emailNotificationService;

    public function __construct()
    {
        parent::__construct();
        $this->letterModel = new LetterModel();
        $this->userModel = new UserModel();
        $this->contractSettingModel = new ContractSettingModel();
        $this->emailNotificationService = new EmailNotificationService();
    }

    public function generate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToPath('persuratan', ['error' => 'Metode terbitkan surat tidak valid.']);
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
        if ($id <= 0) {
            $this->redirectToPath('persuratan', ['error' => 'ID surat tidak valid.']);
        }

        try {
            $result = $this->generateLetterPdf($id);
            $this->letterModel->markAsIssued($id);
            $this->sendIssuedNotificationSafely($id);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . (string) filesize($result['full_path']));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($result['full_path']);
            exit;
        } catch (Throwable $e) {
            $this->redirectToPath('persuratan', ['error' => $e->getMessage()]);
        }
    }

    public function preview(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID surat tidak valid.';
            return;
        }

        try {
            $letter = $this->letterModel->getByIdWithDetails($id);
            if ($letter === null) {
                http_response_code(404);
                echo 'Data surat tidak ditemukan.';
                return;
            }
            if (!$this->canAccessLetter($letter)) {
                http_response_code(403);
                echo 'Akses ditolak.';
                return;
            }
            if (authRole() === 'dosen' && !$this->isApprovedStatus((string) ($letter['status'] ?? ''))) {
                $this->redirectToPath('surat-saya', [
                    'error' => 'Preview hanya tersedia setelah surat disetujui Kepala LPPM/Admin.',
                ]);
            }

            $pdfBinary = $this->buildLetterPdfBinary($letter);
            $previewFilename = $this->buildPdfFilename($letter, '-preview');

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $previewFilename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-Letter-Render-Timestamp: ' . (string) time());
            echo $pdfBinary;
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Gagal menampilkan preview: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    public function download(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo 'ID surat tidak valid.';
            return;
        }

        try {
            $letter = $this->letterModel->getByIdWithDetails($id);
            if ($letter === null) {
                http_response_code(404);
                echo 'Data surat tidak ditemukan.';
                return;
            }
            if (!$this->canAccessLetter($letter)) {
                http_response_code(403);
                echo 'Akses ditolak.';
                return;
            }
            if (authRole() === 'dosen' && !$this->isIssuedStatus((string) ($letter['status'] ?? ''))) {
                $this->redirectToPath('surat-saya', [
                    'error' => 'Download PDF hanya tersedia setelah surat berstatus Surat Terbit.',
                ]);
            }

            // Selalu regenerate agar perubahan template/layout langsung terlihat.
            $generated = $this->generateLetterPdf($id);
            $fullPath = $generated['full_path'];

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $generated['filename'] . '"');
            header('Content-Length: ' . (string) filesize($fullPath));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($fullPath);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Gagal download PDF: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    public function generateLetterPdf(int $letterId): array
    {
        $letter = $this->letterModel->getByIdWithDetails($letterId);
        if ($letter === null) {
            throw new RuntimeException('Data surat tidak ditemukan.');
        }
        $pdfBinary = $this->buildLetterPdfBinary($letter);

        $storageDir = __DIR__ . '/../../storage/uploads/letters';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Folder penyimpanan PDF tidak dapat dibuat.');
        }

        $filename = $this->buildPdfFilename($letter);
        $fullPath = $storageDir . '/' . $filename;
        $relativePath = 'storage/uploads/letters/' . $filename;

        file_put_contents($fullPath, $pdfBinary);
        $this->letterModel->updateFilePdf($letterId, $relativePath);

        return [
            'full_path' => $fullPath,
            'relative_path' => $relativePath,
            'filename' => $filename,
        ];
    }

    private function buildPdfFilename(array $letter, string $suffix = ''): string
    {
        $letterId = (int) ($letter['id'] ?? 0);
        $baseName = trim((string) ($letter['letter_number'] ?? ''));
        if ($baseName === '') {
            $baseName = 'surat-' . $letterId;
        }

        $baseName = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $baseName);
        $baseName = preg_replace('/\s+/', ' ', $baseName) ?? $baseName;
        $baseName = trim($baseName, ". \t\n\r\0\x0B-");
        if ($baseName === '') {
            $baseName = 'surat-' . $letterId;
        }

        return $baseName . $suffix . '.pdf';
    }

    private function buildLetterPdfBinary(array $letter): string
    {
        $html = $this->renderLetterHtml($letter);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function renderLetterHtml(array $letter): string
    {
        $letter = $this->hydrateContractSettingsForPdf($letter);

        $templateFile = $this->resolveTemplateFile($letter);
        $templatePath = __DIR__ . '/../Views/letters/templates/' . $templateFile;
        if (!is_file($templatePath)) {
            throw new RuntimeException('Template PDF tidak ditemukan: ' . $templateFile);
        }

        $logoPath = __DIR__ . '/../../public/assets/img/logo-unisap.png';
        $logoBase64 = '';
        if (is_file($logoPath)) {
            $binary = file_get_contents($logoPath);
            if ($binary !== false) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($binary);
            }
        }

        $formatTanggalIndo = static function (?string $rawDate): string {
            $value = trim((string) $rawDate);
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

            $day = (int) date('d', $ts);
            $month = (int) date('n', $ts);
            $year = date('Y', $ts);

            return $day . ' ' . ($months[$month] ?? date('F', $ts)) . ' ' . $year;
        };

        $formattedCreatedDate = $formatTanggalIndo((string) ($letter['letter_date'] ?? ''));
        $tanggalMulai = !empty($letter['research_start_date']) ? date('d/m/Y', strtotime((string) $letter['research_start_date'])) : '-';
        $tanggalSelesai = !empty($letter['research_end_date']) ? date('d/m/Y', strtotime((string) $letter['research_end_date'])) : '-';
        $attachmentCount = $this->countLetterAttachments($letter);
        $jumlahLampiran = $attachmentCount > 0 ? $attachmentCount . ' Berkas' : '-';
        $namaInstansi = (string) ($letter['research_institution'] ?? $letter['institution'] ?? '-');
        $alamatInstansi = trim((string) ($letter['research_address'] ?? ''));
        $kotaInstansi = trim((string) ($letter['research_city'] ?? ''));
        $alamatTujuan = trim($alamatInstansi . ($alamatInstansi !== '' && $kotaInstansi !== '' ? ', ' : '') . $kotaInstansi);

        $chairman = $this->userModel->getDefaultChairman();
        $chairmanName = (string) ($chairman['name'] ?? 'Kepala LPPM');
        $chairmanIdentifier = (string) ($chairman['nuptk'] ?? $chairman['nidn'] ?? $chairman['phone'] ?? '-');
        $signatureDataUri = null;
        $signaturePath = (string) ($chairman['signature_path'] ?? '');
        if (isSafeProjectRelativePathUnder($signaturePath, 'storage/uploads/signatures')) {
            $signatureFullPath = __DIR__ . '/../../' . normalizeProjectRelativePath($signaturePath);
            if (is_file($signatureFullPath)) {
                $signatureBinary = file_get_contents($signatureFullPath);
                if ($signatureBinary !== false) {
                    $signatureDataUri = 'data:image/png;base64,' . base64_encode($signatureBinary);
                }
            }
        }

        $kotaSurat = 'Kupang';

        // Data khusus surat tugas penelitian/pengabdian
        $taskTitle = (string) ($letter['task_title'] ?? $letter['research_title'] ?? '-');
        $taskScheme = (string) ($letter['task_scheme'] ?? $letter['research_scheme'] ?? '-');
        $taskFundingSource = (string) ($letter['task_funding_source'] ?? $letter['funding_source'] ?? '-');
        $taskYear = (string) ($letter['task_year'] ?? $letter['research_year'] ?? '-');
        $taskLocation = (string) ($letter['task_research_location'] ?? $letter['task_location'] ?? $letter['research_location'] ?? '-');
        $taskLeaderName = (string) ($letter['task_leader_name'] ?? $letter['researcher_name'] ?? $letter['applicant_name'] ?? '-');
        $taskLeaderNuptk = (string) ($letter['applicant_nuptk'] ?? $letter['applicant_nidn'] ?? '-');
        $taskMembersRaw = (string) ($letter['task_members'] ?? $letter['research_members'] ?? '');
        $taskStartDate = (string) ($letter['task_start_date'] ?? $letter['research_start_date'] ?? '');
        $taskEndDate = (string) ($letter['task_end_date'] ?? $letter['research_end_date'] ?? '');
        $taskTanggalMulaiDisplay = $taskStartDate !== '' ? $formatTanggalIndo($taskStartDate) : '-';
        $taskTanggalSelesaiDisplay = $taskEndDate !== '' ? $formatTanggalIndo($taskEndDate) : '-';
        $contractTanggalMulaiDisplay = contract_format_tanggal_indo((string) ($letter['setting_contract_start_date'] ?? $letter['research_start_date'] ?? $letter['task_start_date'] ?? ''));
        $contractTanggalSelesaiDisplay = contract_format_tanggal_indo((string) ($letter['setting_contract_end_date'] ?? $letter['research_end_date'] ?? $letter['task_end_date'] ?? ''));

        $nilaiKontrakRaw = (string) ($letter['contract_total_fund'] ?? $letter['research_total_fund'] ?? '');
        $nilaiKontrakDigits = preg_replace('/\D+/', '', $nilaiKontrakRaw);
        $nilaiKontrakNumber = $nilaiKontrakDigits !== '' ? (int) $nilaiKontrakDigits : 0;
        $nilaiKontrakRupiah = $nilaiKontrakNumber > 0 ? contract_format_rupiah($nilaiKontrakNumber) : '-';
        $nilaiKontrakTerbilang = $nilaiKontrakNumber > 0 ? terbilang_rupiah($nilaiKontrakNumber) : '-';
        $nilaiKontrakTerbilangUcfirst = $nilaiKontrakNumber > 0 ? ucwords(terbilang_rupiah($nilaiKontrakNumber)) : '-';
        $nilaiKontrakTerbilangUpper = $nilaiKontrakNumber > 0 ? terbilang_upper($nilaiKontrakNumber) . ' RUPIAH' : '-';
        $contractStage1Percent = (float) ($letter['contract_percent_stage_1'] ?? 80);
        $contractStage2Percent = (float) ($letter['contract_percent_stage_2'] ?? 20);
        $contractStageCalc = contract_calc_stage_amounts($nilaiKontrakNumber, $contractStage1Percent, $contractStage2Percent);
        $danaTahap1Rupiah = contract_format_rupiah((int) ($contractStageCalc['stage1_amount'] ?? 0));
        $danaTahap2Rupiah = contract_format_rupiah((int) ($contractStageCalc['stage2_amount'] ?? 0));
        $danaTahap1TerbilangUcfirst = ((int) ($contractStageCalc['stage1_amount'] ?? 0)) > 0
            ? ucwords(terbilang_rupiah((int) ($contractStageCalc['stage1_amount'] ?? 0)))
            : '-';
        $danaTahap2TerbilangUcfirst = ((int) ($contractStageCalc['stage2_amount'] ?? 0)) > 0
            ? ucwords(terbilang_rupiah((int) ($contractStageCalc['stage2_amount'] ?? 0)))
            : '-';
        $batasUploadTahap2Display = contract_format_tanggal_indo((string) ($letter['contract_deadline_upload_stage_2'] ?? ''));
        $batasTanggalTahap1Display = contract_format_tanggal_indo((string) ($letter['contract_deadline_stage_1'] ?? ''));
        $batasTanggalTahap2Display = contract_format_tanggal_indo((string) ($letter['contract_deadline_stage_2'] ?? ''));
        $batasLaporanAkhirDisplay = contract_format_tanggal_indo((string) ($letter['contract_deadline_report_final'] ?? ''));
        $batasWaktuUploadSetelahDanaDisplay = trim((string) ($letter['contract_deadline_after_fund'] ?? ''));
        if ($batasWaktuUploadSetelahDanaDisplay === '') {
            $batasWaktuUploadSetelahDanaDisplay = '-';
        }

        ob_start();
        require $templatePath;

        return (string) ob_get_clean();
    }

    private function hydrateContractSettingsForPdf(array $letter): array
    {
        $typeCode = strtoupper(trim((string) ($letter['letter_type_code'] ?? '')));
        if ($typeCode !== 'KONTRAK') {
            return $letter;
        }

        $sourceKey = '';
        $notes = trim((string) ($letter['research_notes'] ?? ''));
        if ($notes !== '' && preg_match('/__CONTRACT_SOURCE__\[([a-z_]+)\]/i', $notes, $matches)) {
            $sourceKey = strtolower(trim((string) ($matches[1] ?? '')));
        }

        if ($sourceKey !== ContractSettingModel::SOURCE_DIKTI) {
            $fundingSource = (string) ($letter['funding_source'] ?? $letter['task_funding_source'] ?? '');
            $sourceKey = ContractSettingModel::resolveSourceKeyFromFunding($fundingSource);
        }

        $scope = $this->resolveContractScope($letter);

        $year = (int) ($letter['research_year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y', strtotime((string) ($letter['letter_date'] ?? 'now')));
        }

        $setting = $this->contractSettingModel->getBySourceAndYear($sourceKey, $year, $scope);
        $signDay = trim((string) ($setting['hari_penandatanganan'] ?? ''));
        $signDate = trim((string) ($setting['tanggal_penandatanganan'] ?? ''));

        // Fallback aman: jika seting tahun terkait belum terisi, ambil tahun lain
        // pada sumber dana yang sama agar PDF tidak menampilkan placeholder '-'.
        if ($signDay === '' || $signDate === '') {
            $historyRows = $this->contractSettingModel->getYearListSummaryBySource($sourceKey, $scope);
            foreach ($historyRows as $row) {
                $candidateDay = trim((string) ($row['hari_penandatanganan'] ?? ''));
                $candidateDate = trim((string) ($row['tanggal_penandatanganan'] ?? ''));
                if ($candidateDay !== '' && $candidateDate !== '') {
                    $signDay = $candidateDay;
                    $signDate = $candidateDate;
                    break;
                }
            }
        }

        // Fallback terakhir: gunakan seting Hibah Dikti pada tahun yang sama.
        if ($signDay !== '') {
            $letter['setting_contract_sign_day'] = $signDay;
            $letter['hari_penandatanganan'] = $signDay;
            $letter['contract_sign_day'] = $signDay;
        }
        if ($signDate !== '') {
            $letter['setting_contract_sign_date'] = $signDate;
            $letter['tanggal_penandatanganan'] = $signDate;
            $letter['contract_sign_date'] = $signDate;
        }
        $nomorKontrakDikti = trim((string) ($setting['nomor_kontrak_dikti'] ?? ''));
        if ($nomorKontrakDikti === '') {
            $nomorKontrakDikti = trim((string) ($letter['letter_number'] ?? ''));
        }
        if ($nomorKontrakDikti !== '') {
            $letter['nomor_kontrak_dikti'] = $nomorKontrakDikti;
            $letter['contract_number_dikti'] = $nomorKontrakDikti;
        }
        $letter['contract_percent_stage_1'] = (float) ($setting['persentase_tahap_1'] ?? 80);
        $letter['contract_percent_stage_2'] = (float) ($setting['persentase_tahap_2'] ?? 20);
        $letter['contract_deadline_upload_stage_2'] = (string) ($setting['batas_upload_tahap_2'] ?? '');
        $letter['contract_deadline_stage_1'] = (string) ($setting['batas_tanggal_tahap_1'] ?? '');
        $letter['contract_deadline_stage_2'] = (string) ($setting['batas_tanggal_tahap_2'] ?? '');
        $letter['contract_deadline_report_final'] = (string) ($setting['batas_laporan_akhir'] ?? '');
        $letter['contract_deadline_after_fund'] = (string) ($setting['batas_waktu_upload_setelah_dana'] ?? '');

        $settingStartDate = trim((string) ($setting['tanggal_mulai_global'] ?? ''));
        $settingEndDate = trim((string) ($setting['tanggal_selesai_global'] ?? ''));
        if (in_array($scope, [
            ContractSettingModel::SCOPE_HILIRISASI,
            ContractSettingModel::SCOPE_PENELITIAN,
            ContractSettingModel::SCOPE_PENGABDIAN,
        ], true)) {
            if ($settingStartDate !== '') {
                $letter['setting_contract_start_date'] = $settingStartDate;
                $letter['research_start_date'] = $settingStartDate;
            }
            if ($settingEndDate !== '') {
                $letter['setting_contract_end_date'] = $settingEndDate;
                $letter['research_end_date'] = $settingEndDate;
            }
        }

        [$activityType, $activityId] = $this->resolveContractActivityRefFromNotes(
            (string) ($letter['research_notes'] ?? ''),
            (string) ($letter['subject'] ?? ''),
            (string) ($letter['letter_type_slug'] ?? '')
        );
        if ($activityId <= 0) {
            $activityId = $this->resolveContractActivityIdFromLetterContext($activityType, $letter);
        }
        if ($activityId > 0) {
            $fundValue = $this->findActivityTotalApprovedFund(
                $activityType,
                $activityId,
                (int) ($letter['applicant_id'] ?? 0)
            );
            if ($fundValue !== '') {
                $letter['contract_total_fund'] = $fundValue;
            }

            $activityDates = $this->findActivityExecutionDates(
                $activityType,
                $activityId,
                (int) ($letter['applicant_id'] ?? 0)
            );
            if (($activityDates['start_date'] ?? '') !== '') {
                $letter['research_start_date'] = (string) $activityDates['start_date'];
            }
            if (($activityDates['end_date'] ?? '') !== '') {
                $letter['research_end_date'] = (string) $activityDates['end_date'];
            }
        }

        return $letter;
    }

    private function resolveContractActivityRefFromNotes(string $notes, string $subject = '', string $letterTypeSlug = ''): array
    {
        if ($notes !== '' && preg_match('/__ACTIVITY_REF__\[(penelitian|pengabdian|hilirisasi):(\d+)\]/i', $notes, $matches)) {
            $type = strtolower((string) ($matches[1] ?? 'penelitian'));

            return [
                $type,
                (int) ($matches[2] ?? 0),
            ];
        }

        $scope = $this->resolveScopeFromLetterTypeSlug($letterTypeSlug);
        if ($scope !== '') {
            return [$scope, 0];
        }

        $subject = strtolower(trim($subject));
        if (str_contains($subject, 'pengabdian')) {
            return ['pengabdian', 0];
        }
        if (str_contains($subject, 'hilirisasi')) {
            return ['hilirisasi', 0];
        }

        return ['penelitian', 0];
    }

    private function resolveContractActivityIdFromLetterContext(string $activityType, array $letter): int
    {
        $ownerId = (int) ($letter['applicant_id'] ?? 0);
        if ($ownerId <= 0) {
            return 0;
        }

        $activityType = strtolower(trim($activityType));
        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            $activityType = 'penelitian';
        }

        if ($activityType === 'pengabdian') {
            $table = 'data_pengabdian';
        } elseif ($activityType === 'hilirisasi') {
            $table = 'data_hilirisasi';
        } else {
            $table = 'data_penelitian';
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT id, judul, tahun, mitra, lokasi
             FROM ' . $table . '
             WHERE created_by = :created_by'
        );
        $stmt->execute([':created_by' => $ownerId]);
        $rows = $stmt->fetchAll() ?: [];
        if ($rows === []) {
            return 0;
        }

        $targetTitle = $this->normalizeMatchText((string) ($letter['research_title'] ?? ''));
        $targetYear = trim((string) ($letter['research_year'] ?? ''));
        $targetInstitution = $this->normalizeMatchText((string) ($letter['research_institution'] ?? ''));
        $targetAddress = $this->normalizeMatchText((string) ($letter['research_address'] ?? ''));

        $bestId = 0;
        $bestScore = -1;
        foreach ($rows as $row) {
            $score = 0;

            $rowTitle = $this->normalizeMatchText((string) ($row['judul'] ?? ''));
            $rowYear = trim((string) ($row['tahun'] ?? ''));
            $rowMitra = $this->normalizeMatchText((string) ($row['mitra'] ?? ''));
            $rowLokasi = $this->normalizeMatchText((string) ($row['lokasi'] ?? ''));

            if ($targetTitle !== '' && $rowTitle !== '' && $targetTitle === $rowTitle) {
                $score += 4;
            }
            if ($targetYear !== '' && $rowYear !== '' && $targetYear === $rowYear) {
                $score += 2;
            }
            if ($targetInstitution !== '' && $rowMitra !== '' && $targetInstitution === $rowMitra) {
                $score += 3;
            }
            if ($targetAddress !== '' && $rowLokasi !== '' && $targetAddress === $rowLokasi) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = (int) ($row['id'] ?? 0);
            }
        }

        return $bestScore > 0 ? $bestId : 0;
    }

    private function normalizeMatchText(string $value): string
    {
        $text = strtolower(trim($value));
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^a-z0-9 ]/', '', (string) $text);

        return trim((string) $text);
    }

    private function resolveContractScope(array $letter): string
    {
        $scope = $this->resolveScopeFromLetterTypeSlug((string) ($letter['letter_type_slug'] ?? ''));
        if ($scope !== '') {
            return $scope;
        }

        [$activityType] = $this->resolveContractActivityRefFromNotes(
            (string) ($letter['research_notes'] ?? ''),
            (string) ($letter['subject'] ?? '')
        );

        return ContractSettingModel::resolveScopeFromActivityType($activityType);
    }

    private function resolveScopeFromLetterTypeSlug(string $letterTypeSlug): string
    {
        $slug = strtolower(trim($letterTypeSlug));
        if ($slug === '') {
            return '';
        }

        if (str_contains($slug, '_pengabdian')) {
            return ContractSettingModel::SCOPE_PENGABDIAN;
        }

        if (str_contains($slug, '_penelitian')) {
            return ContractSettingModel::SCOPE_PENELITIAN;
        }
        if (str_contains($slug, '_hilirisasi')) {
            return ContractSettingModel::SCOPE_HILIRISASI;
        }

        return '';
    }

    private function findActivityTotalApprovedFund(string $activityType, int $activityId, int $applicantId): string
    {
        if ($activityId <= 0 || $applicantId <= 0) {
            return '';
        }

        $activityType = strtolower(trim($activityType));
        if ($activityType === 'pengabdian') {
            $table = 'data_pengabdian';
        } elseif ($activityType === 'hilirisasi') {
            $table = 'data_hilirisasi';
        } else {
            $table = 'data_penelitian';
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT total_dana_disetujui
             FROM ' . $table . '
             WHERE id = :id AND created_by = :created_by
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $activityId,
            ':created_by' => $applicantId,
        ]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            return '';
        }

        return trim((string) $value);
    }

    private function findActivityExecutionDates(string $activityType, int $activityId, int $applicantId): array
    {
        if ($activityId <= 0 || $applicantId <= 0) {
            return [
                'start_date' => '',
                'end_date' => '',
            ];
        }

        $activityType = strtolower(trim($activityType));
        if ($activityType === 'pengabdian') {
            $table = 'data_pengabdian';
        } elseif ($activityType === 'hilirisasi') {
            $table = 'data_hilirisasi';
        } else {
            $table = 'data_penelitian';
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT tanggal_mulai, tanggal_selesai
             FROM ' . $table . '
             WHERE id = :id AND created_by = :created_by
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $activityId,
            ':created_by' => $applicantId,
        ]);
        $row = $stmt->fetch() ?: [];

        return [
            'start_date' => trim((string) ($row['tanggal_mulai'] ?? '')),
            'end_date' => trim((string) ($row['tanggal_selesai'] ?? '')),
        ];
    }

    private function resolveTemplateFile(array $letter): string
    {
        $code = strtoupper(trim((string) ($letter['letter_type_code'] ?? '')));
        $scope = $this->resolveLetterScope($letter);
        if ($code === 'TUGAS') {
            return match ($scope) {
                ContractSettingModel::SCOPE_PENGABDIAN => 'assignment_service.php',
                ContractSettingModel::SCOPE_HILIRISASI => 'assignment_hilirisasi.php',
                default => 'assignment_research.php',
            };
        }
        if ($code === 'KONTRAK') {
            if ($scope === ContractSettingModel::SCOPE_PENGABDIAN) {
                return 'contract_service.php';
            }
            if ($scope === ContractSettingModel::SCOPE_HILIRISASI) {
                return 'contract_hilirisasi.php';
            }

            return 'contract_research.php';
        }

        return match ($scope) {
            ContractSettingModel::SCOPE_PENGABDIAN => 'permit_service.php',
            ContractSettingModel::SCOPE_HILIRISASI => 'permit_hilirisasi.php',
            default => 'permit_research.php',
        };
    }

    private function resolveLetterScope(array $letter): string
    {
        $scope = $this->resolveScopeFromLetterTypeSlug((string) ($letter['letter_type_slug'] ?? ''));
        if ($scope !== '') {
            return $scope;
        }

        $subject = strtolower(trim((string) ($letter['subject'] ?? '')));
        if (str_contains($subject, 'pengabdian')) {
            return ContractSettingModel::SCOPE_PENGABDIAN;
        }
        if (str_contains($subject, 'hilirisasi')) {
            return ContractSettingModel::SCOPE_HILIRISASI;
        }

        return ContractSettingModel::SCOPE_PENELITIAN;
    }

    private function canAccessLetter(array $letter): bool
    {
        if (isAdminPanelRole(authRole())) {
            return true;
        }

        if (authRole() === 'dosen') {
            $userId = (int) (authUserId() ?? 0);
            return $userId > 0 && $this->letterModel->canViewByUser((int) ($letter['id'] ?? 0), $userId);
        }

        return false;
    }

    private function isApprovedStatus(string $status): bool
    {
        $normalized = strtolower(str_replace(' ', '_', trim($status)));

        return in_array($normalized, ['approved', 'disetujui', 'surat_terbit', 'terbit', 'selesai'], true);
    }

    private function isIssuedStatus(string $status): bool
    {
        $normalized = strtolower(str_replace(' ', '_', trim($status)));

        return in_array($normalized, ['surat_terbit', 'terbit', 'selesai'], true);
    }

    private function sendIssuedNotificationSafely(int $letterId): void
    {
        if ($letterId <= 0) {
            return;
        }

        try {
            $letter = $this->letterModel->getByIdForDetail($letterId);
            if ($letter === null) {
                return;
            }

            $applicant = $this->userModel->findById((int) ($letter['applicant_id'] ?? 0));
            $this->emailNotificationService->sendLetterIssuedNotifications($letter, $applicant);
        } catch (Throwable $e) {
            error_log('[SAPA LPPM] Email notifikasi surat terbit gagal: ' . $e->getMessage());
        }
    }

    private function countLetterAttachments(array $letter): int
    {
        $paths = $this->extractPermitAttachmentPaths((string) ($letter['attachment_file'] ?? ''));

        $taskProposal = trim((string) ($letter['task_file_proposal'] ?? ''));
        $taskSk = trim((string) ($letter['task_file_sk'] ?? ''));
        if ($taskProposal !== '') {
            $paths[] = $taskProposal;
        }
        if ($taskSk !== '') {
            $paths[] = $taskSk;
        }

        $normalized = [];
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path !== '') {
                $normalized[$path] = true;
            }
        }

        return count($normalized);
    }

    private function extractPermitAttachmentPaths(string $rawValue): array
    {
        $rawValue = trim($rawValue);
        if ($rawValue === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (is_array($decoded)) {
            $paths = [];
            foreach (['file_proposal', 'file_instrumen', 'file_pendukung_lain'] as $key) {
                $value = trim((string) ($decoded[$key] ?? ''));
                if ($value !== '') {
                    $paths[] = $value;
                }
            }

            return $paths;
        }

        return [$rawValue];
    }
}
