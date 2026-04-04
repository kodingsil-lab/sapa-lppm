<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/App.php';
require_once __DIR__ . '/../Helpers/EnvHelper.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/SmtpMailer.php';

class EmailNotificationService
{
    /** @var array<string,mixed> */
    private array $config;
    private UserModel $userModel;
    private SmtpMailer $mailer;
    private string $appName;
    private string $appUrl;

    public function __construct()
    {
        $this->config = $this->loadEmailConfig();
        $this->userModel = new UserModel();
        $this->mailer = new SmtpMailer($this->config);
        $appConfig = app_config();
        $this->appName = trim((string) ($appConfig['app']['name'] ?? 'SAPA LPPM'));
        $this->appUrl = rtrim((string) ($appConfig['app']['url'] ?? 'http://localhost/sapa-lppm'), '/');
    }

    public function assertConfigurationReady(): void
    {
        $fromEmail = trim((string) ($this->config['fromEmail'] ?? ''));
        if ($fromEmail === '') {
            throw new RuntimeException('Konfigurasi email belum lengkap. Isi MAIL_FROM_ADDRESS di file .env.');
        }

        $protocol = strtolower(trim((string) ($this->config['protocol'] ?? 'mail')));
        if ($protocol === 'smtp') {
            $host = trim((string) ($this->config['SMTPHost'] ?? ''));
            if ($host === '') {
                throw new RuntimeException('Konfigurasi email belum lengkap. Isi MAIL_SMTP_HOST di file .env.');
            }

            $username = trim((string) ($this->config['SMTPUser'] ?? ''));
            $password = (string) ($this->config['SMTPPass'] ?? '');
            if ($username !== '' && $password === '') {
                throw new RuntimeException('Konfigurasi email belum lengkap. Isi MAIL_SMTP_PASS di file .env.');
            }
        }
    }

    public function sendRegistrationNotifications(array $user): void
    {
        $name = trim((string) ($user['name'] ?? 'Dosen'));
        $email = trim((string) ($user['email'] ?? ''));
        $username = trim((string) ($user['username'] ?? '-'));

        if ($email !== '') {
            $this->sendTemplatedEmail(
                $email,
                'Registrasi Akun SAPA LPPM Berhasil',
                [
                    'eyebrow' => 'NOTIFIKASI REGISTRASI',
                    'title' => 'Akun SAPA LPPM Anda Sudah Aktif',
                    'intro' => 'Halo ' . $name . ', akun Anda berhasil dibuat dan sudah dapat digunakan untuk masuk ke sistem.',
                    'highlights' => [
                        ['label' => 'Username', 'value' => $username !== '' ? $username : '-'],
                    ],
                    'bodyHtml' => '<p>Silakan login menggunakan username yang telah Anda buat saat registrasi. Demi keamanan, password tidak dikirim melalui email.</p>',
                    'ctaLabel' => 'Masuk ke SAPA LPPM',
                    'ctaUrl' => $this->appUrl . '/login',
                    'footerNote' => 'Email ini dikirim otomatis setelah registrasi akun dosen berhasil.',
                ]
            );
        }

        $headlines = [
            '<p>Registrasi akun dosen baru telah masuk ke sistem.</p>',
            '<p>Mohon lakukan pengecekan data apabila diperlukan.</p>',
        ];

        $this->sendToHeadRecipients(
            'Notifikasi Registrasi Dosen Baru',
            [
                'eyebrow' => 'ADMINISTRASI AKUN',
                'title' => 'Registrasi Dosen Baru',
                'intro' => 'Sistem mendeteksi ada akun dosen baru yang berhasil diregistrasikan.',
                'highlights' => [
                    ['label' => 'Nama Dosen', 'value' => $name],
                    ['label' => 'Username', 'value' => $username !== '' ? $username : '-'],
                    ['label' => 'Email', 'value' => $email !== '' ? $email : '-'],
                ],
                'bodyHtml' => implode('', $headlines),
                'ctaLabel' => 'Buka Manajemen Pengguna',
                'ctaUrl' => $this->appUrl . '/?route=users',
                'footerNote' => 'Notifikasi ini dikirim ke Kepala LPPM/Admin sebagai pemberitahuan registrasi akun baru.',
            ]
        );
    }

    public function sendPasswordResetLinkNotification(array $user, string $resetUrl): void
    {
        $name = trim((string) ($user['name'] ?? 'Dosen'));
        $email = trim((string) ($user['email'] ?? ''));
        $username = trim((string) ($user['username'] ?? '-'));

        if ($email !== '') {
            $this->sendTemplatedEmail(
                $email,
                'Link Reset Password SAPA LPPM',
                [
                    'eyebrow' => 'RESET PASSWORD',
                    'title' => 'Buat Password Baru Anda',
                    'intro' => 'Halo ' . $name . ', kami menerima permintaan reset password untuk akun SAPA LPPM Anda.',
                    'highlights' => [
                        ['label' => 'Username', 'value' => $username !== '' ? $username : '-'],
                        ['label' => 'Email', 'value' => $email !== '' ? $email : '-'],
                        ['label' => 'Masa Berlaku', 'value' => '30 menit'],
                    ],
                    'bodyHtml' => '<p>Klik tombol di bawah ini untuk membuat password baru. Demi keamanan, link ini hanya dapat digunakan satu kali dan akan kedaluwarsa secara otomatis.</p>',
                    'ctaLabel' => 'Atur Password Baru',
                    'ctaUrl' => $resetUrl,
                    'footerNote' => 'Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini dan akun Anda tidak akan berubah.',
                ]
            );
        }
    }

    public function sendLetterSubmittedNotifications(array $letter, ?array $applicant): void
    {
        $context = $this->buildLetterContext($letter, $applicant);

        if ($context['applicant_email'] !== '') {
            $this->sendTemplatedEmail(
                $context['applicant_email'],
                'Ajuan Surat Berhasil Dikirim',
                [
                    'eyebrow' => 'PERSURATAN',
                    'title' => 'Ajuan Surat Berhasil Dikirim',
                    'intro' => 'Halo ' . $context['applicant_name'] . ', ajuan surat Anda sudah berhasil masuk ke sistem dan sedang menunggu proses verifikasi/approval.',
                    'highlights' => [
                        ['label' => 'Jenis Surat', 'value' => $context['letter_type_name']],
                        ['label' => 'Judul / Perihal', 'value' => $context['subject']],
                        ['label' => 'Tanggal Ajuan', 'value' => $context['letter_date']],
                    ],
                    'bodyHtml' => '<p>Anda dapat memantau perkembangan surat melalui menu <strong>Surat Saya</strong> pada SAPA LPPM.</p>',
                    'ctaLabel' => 'Lihat Surat Saya',
                    'ctaUrl' => $this->appUrl . '/?route=my-letters',
                    'footerNote' => 'Notifikasi ini dikirim otomatis saat ajuan surat berhasil direkam di sistem.',
                ]
            );
        }

        $this->sendToHeadRecipients(
            'Notifikasi Ajuan Surat Baru',
            [
                'eyebrow' => 'PERSURATAN MASUK',
                'title' => 'Ada Ajuan Surat Baru',
                'intro' => 'Sistem menerima ajuan surat baru yang memerlukan perhatian Kepala LPPM/Admin.',
                'highlights' => [
                    ['label' => 'Jenis Surat', 'value' => $context['letter_type_name']],
                    ['label' => 'Pemohon', 'value' => $context['applicant_name']],
                    ['label' => 'Judul / Perihal', 'value' => $context['subject']],
                ],
                'bodyHtml' => '<p>Silakan buka daftar persuratan untuk meninjau, memverifikasi, dan memproses surat tersebut.</p>',
                'ctaLabel' => 'Buka Menu Persuratan',
                'ctaUrl' => $this->appUrl . '/?route=letters',
                'footerNote' => 'Email ini dikirim otomatis saat surat berstatus diajukan.',
            ]
        );
    }

    public function sendLetterApprovedNotifications(array $letter, ?array $applicant): void
    {
        $context = $this->buildLetterContext($letter, $applicant);

        if ($context['applicant_email'] !== '') {
            $this->sendTemplatedEmail(
                $context['applicant_email'],
                'Surat Anda Sudah Disetujui',
                [
                    'eyebrow' => 'STATUS SURAT',
                    'title' => 'Surat Telah Disetujui',
                    'intro' => 'Halo ' . $context['applicant_name'] . ', surat Anda telah disetujui dan siap masuk tahap penerbitan PDF resmi.',
                    'highlights' => [
                        ['label' => 'Jenis Surat', 'value' => $context['letter_type_name']],
                        ['label' => 'Nomor Surat', 'value' => $context['letter_number']],
                        ['label' => 'Perihal', 'value' => $context['subject']],
                    ],
                    'bodyHtml' => '<p>Silakan pantau status surat Anda. Setelah diterbitkan, file PDF resmi akan tersedia di sistem.</p>',
                    'ctaLabel' => 'Pantau Status Surat',
                    'ctaUrl' => $this->appUrl . '/?route=my-letters',
                    'footerNote' => 'Notifikasi ini dikirim saat surat berstatus disetujui.',
                ]
            );
        }

        $this->sendToHeadRecipients(
            'Notifikasi Surat Disetujui',
            [
                'eyebrow' => 'PERSURATAN',
                'title' => 'Surat Berhasil Disetujui',
                'intro' => 'Status surat pada sistem telah berubah menjadi disetujui.',
                'highlights' => [
                    ['label' => 'Jenis Surat', 'value' => $context['letter_type_name']],
                    ['label' => 'Pemohon', 'value' => $context['applicant_name']],
                    ['label' => 'Nomor Surat', 'value' => $context['letter_number']],
                ],
                'bodyHtml' => '<p>Langkah berikutnya adalah menerbitkan PDF resmi agar surat masuk ke arsip sebagai surat terbit.</p>',
                'ctaLabel' => 'Buka Persuratan',
                'ctaUrl' => $this->appUrl . '/?route=letters',
                'footerNote' => 'Email ini dikirim sebagai ringkasan perubahan status surat.',
            ]
        );
    }

    public function sendLetterIssuedNotifications(array $letter, ?array $applicant): void
    {
        $context = $this->buildLetterContext($letter, $applicant);

        if ($context['applicant_email'] !== '') {
            $this->sendTemplatedEmail(
                $context['applicant_email'],
                'Surat Anda Sudah Terbit',
                [
                    'eyebrow' => 'SURAT TERBIT',
                    'title' => 'Surat Resmi Sudah Terbit',
                    'intro' => 'Halo ' . $context['applicant_name'] . ', surat Anda sudah diterbitkan dan arsip PDF resminya tersedia di sistem.',
                    'highlights' => [
                        ['label' => 'Jenis Surat', 'value' => $context['letter_type_name']],
                        ['label' => 'Nomor Surat', 'value' => $context['letter_number']],
                        ['label' => 'Perihal', 'value' => $context['subject']],
                    ],
                    'bodyHtml' => '<p>Silakan masuk ke SAPA LPPM untuk melihat detail dan mengunduh PDF surat resmi yang telah diterbitkan.</p>',
                    'ctaLabel' => 'Buka Surat Saya',
                    'ctaUrl' => $this->appUrl . '/?route=my-letters',
                    'footerNote' => 'Notifikasi ini dikirim saat surat berstatus surat terbit.',
                ]
            );
        }

        $this->sendToHeadRecipients(
            'Notifikasi Surat Terbit',
            [
                'eyebrow' => 'ARSIP SURAT',
                'title' => 'Surat Resmi Sudah Terbit',
                'intro' => 'Sistem berhasil menerbitkan PDF resmi untuk salah satu surat dan memindahkannya ke arsip.',
                'highlights' => [
                    ['label' => 'Jenis Surat', 'value' => $context['letter_type_name']],
                    ['label' => 'Pemohon', 'value' => $context['applicant_name']],
                    ['label' => 'Nomor Surat', 'value' => $context['letter_number']],
                ],
                'bodyHtml' => '<p>Dokumen resmi telah tersedia di arsip SAPA LPPM dan dapat ditinjau kembali sewaktu-waktu.</p>',
                'ctaLabel' => 'Buka Arsip Surat',
                'ctaUrl' => $this->appUrl . '/?route=archives',
                'footerNote' => 'Email ini dikirim otomatis saat proses penerbitan PDF selesai.',
            ]
        );
    }

    private function buildLetterContext(array $letter, ?array $applicant): array
    {
        return [
            'subject' => trim((string) ($letter['subject'] ?? '-')) ?: '-',
            'letter_type_name' => trim((string) ($letter['letter_type_name'] ?? $letter['letter_type_code'] ?? 'Surat')) ?: 'Surat',
            'letter_number' => trim((string) ($letter['letter_number'] ?? '-')) ?: '-',
            'letter_date' => $this->formatDate((string) ($letter['letter_date'] ?? date('Y-m-d'))),
            'applicant_name' => trim((string) ($applicant['name'] ?? $letter['applicant_name'] ?? 'Dosen')) ?: 'Dosen',
            'applicant_email' => trim((string) ($applicant['email'] ?? $letter['applicant_email'] ?? '')),
        ];
    }

    private function sendToHeadRecipients(string $subject, array $viewData): void
    {
        foreach ($this->getHeadRecipients() as $recipient) {
            $this->sendTemplatedEmail($recipient['email'], $subject, $viewData, $recipient['name']);
        }
    }

    private function getHeadRecipients(): array
    {
        $recipients = [];
        $seen = [];

        foreach ($this->userModel->getAllChairmen() as $head) {
            $email = strtolower(trim((string) ($head['email'] ?? '')));
            if ($email === '' || isset($seen[$email])) {
                continue;
            }

            $seen[$email] = true;
            $recipients[] = [
                'email' => $email,
                'name' => trim((string) ($head['name'] ?? 'Kepala LPPM')),
            ];
        }

        $extraRecipients = preg_split('/[,;]+/', (string) ($this->config['recipients'] ?? '')) ?: [];
        foreach ($extraRecipients as $extra) {
            $email = strtolower(trim((string) $extra));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;
            $recipients[] = [
                'email' => $email,
                'name' => 'Penerima Notifikasi',
            ];
        }

        return $recipients;
    }

    private function sendTemplatedEmail(string $toEmail, string $subject, array $viewData, ?string $recipientName = null): void
    {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->assertConfigurationReady();

        $html = $this->renderTemplate('layout', array_merge($viewData, [
            'appName' => $this->appName,
            'recipientName' => $recipientName,
            'subjectLine' => $subject,
        ]));

        $this->mailer->send(
            trim((string) ($this->config['fromEmail'] ?? '')),
            trim((string) ($this->config['fromName'] ?? $this->appName)),
            $toEmail,
            $subject,
            $html,
            $this->buildAltMessage($subject, $viewData)
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function loadEmailConfig(): array
    {
        return [
            'fromEmail' => (string) appEnv('MAIL_FROM_ADDRESS', ''),
            'fromName' => (string) appEnv('MAIL_FROM_NAME', 'SAPA LPPM Universitas San Pedro'),
            'recipients' => (string) appEnv('MAIL_RECIPIENTS', ''),
            'userAgent' => 'CodeIgniter',
            'protocol' => (string) appEnv('MAIL_PROTOCOL', 'smtp'),
            'mailPath' => '/usr/sbin/sendmail',
            'SMTPHost' => (string) appEnv('MAIL_SMTP_HOST', ''),
            'SMTPAuthMethod' => (string) appEnv('MAIL_SMTP_AUTH_METHOD', 'login'),
            'SMTPUser' => (string) appEnv('MAIL_SMTP_USER', ''),
            'SMTPPass' => (string) appEnv('MAIL_SMTP_PASS', ''),
            'SMTPPort' => (int) appEnv('MAIL_SMTP_PORT', 465),
            'SMTPTimeout' => (int) appEnv('MAIL_SMTP_TIMEOUT', 15),
            'SMTPKeepAlive' => false,
            'SMTPCrypto' => (string) $this->getEnvAllowEmpty('MAIL_SMTP_CRYPTO', 'ssl'),
            'wordWrap' => $this->toBool(appEnv('MAIL_WORD_WRAP', 'true')),
            'wrapChars' => 76,
            'mailType' => (string) appEnv('MAIL_TYPE', 'html'),
            'charset' => (string) appEnv('MAIL_CHARSET', 'UTF-8'),
            'validate' => $this->toBool(appEnv('MAIL_VALIDATE', 'true')),
            'priority' => 3,
            'CRLF' => "\r\n",
            'newline' => "\r\n",
            'BCCBatchMode' => false,
            'BCCBatchSize' => 200,
            'DSN' => false,
        ];
    }

    private function getEnvAllowEmpty(string $key, string $default = ''): string
    {
        loadAppEnv(dirname(__DIR__, 2));

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function renderTemplate(string $template, array $data): string
    {
        $templatePath = __DIR__ . '/../Views/emails/' . $template . '.php';
        if (!is_file($templatePath)) {
            throw new RuntimeException('Template email tidak ditemukan: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        return (string) ob_get_clean();
    }

    private function buildAltMessage(string $subject, array $viewData): string
    {
        $lines = [$subject, ''];

        if (!empty($viewData['title'])) {
            $lines[] = (string) $viewData['title'];
        }
        if (!empty($viewData['intro'])) {
            $lines[] = strip_tags((string) $viewData['intro']);
        }

        foreach (($viewData['highlights'] ?? []) as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $value = trim((string) ($item['value'] ?? ''));
            if ($label !== '' && $value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }

        if (!empty($viewData['footerNote'])) {
            $lines[] = '';
            $lines[] = strip_tags((string) $viewData['footerNote']);
        }

        return implode("\n", $lines);
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }
}
