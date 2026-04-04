<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/LetterModel.php';

class VerifyController
{
    private LetterModel $letterModel;

    public function __construct()
    {
        $this->letterModel = new LetterModel();
    }

    public function show(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $letter = null;
        $isValid = false;
        $message = 'Token verifikasi tidak valid.';

        if ($token !== '') {
            $letter = $this->letterModel->getByVerificationToken($token);

            if ($letter !== null) {
                $status = strtolower((string) ($letter['status'] ?? ''));
                $isValid = in_array($status, ['approved', 'disetujui'], true);
                $message = $isValid ? 'Surat valid dan terverifikasi.' : 'Surat ditemukan, tetapi belum berstatus disetujui.';
            }
        }

        $appConfig = require __DIR__ . '/../../config.php';
        require __DIR__ . '/../Views/verify/show.php';
    }
}
