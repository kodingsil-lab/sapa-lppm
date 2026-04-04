<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

require_once __DIR__ . '/../Helpers/EnvHelper.php';

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * Which SMTP authentication method to use: login, plain
     */
    public string $SMTPAuthMethod = 'login';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 587;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 15;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = true;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        $this->fromEmail = (string) $this->env('MAIL_FROM_ADDRESS', '');
        $this->fromName = (string) $this->env('MAIL_FROM_NAME', 'SAPA LPPM Universitas San Pedro');
        $this->recipients = (string) $this->env('MAIL_RECIPIENTS', '');

        $this->protocol = (string) $this->env('MAIL_PROTOCOL', 'smtp');
        $this->SMTPHost = (string) $this->env('MAIL_SMTP_HOST', '');
        $this->SMTPAuthMethod = (string) $this->env('MAIL_SMTP_AUTH_METHOD', 'login');
        $this->SMTPUser = (string) $this->env('MAIL_SMTP_USER', '');
        $this->SMTPPass = (string) $this->env('MAIL_SMTP_PASS', '');
        $this->SMTPPort = (int) $this->env('MAIL_SMTP_PORT', 465);
        $this->SMTPTimeout = (int) $this->env('MAIL_SMTP_TIMEOUT', 15);
        $this->SMTPCrypto = (string) $this->envAllowEmpty('MAIL_SMTP_CRYPTO', 'ssl');
        $this->mailType = (string) $this->env('MAIL_TYPE', 'html');
        $this->charset = (string) $this->env('MAIL_CHARSET', 'UTF-8');
        $this->validate = $this->toBool($this->env('MAIL_VALIDATE', 'true'));
        $this->wordWrap = $this->toBool($this->env('MAIL_WORD_WRAP', 'true'));
    }

    private function env(string $key, mixed $default = null): mixed
    {
        return \appEnv($key, $default, dirname(__DIR__, 2));
    }

    private function envAllowEmpty(string $key, mixed $default = null): mixed
    {
        \loadAppEnv(dirname(__DIR__, 2));

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
