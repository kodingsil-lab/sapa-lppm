<?php

declare(strict_types=1);

class SmtpMailer
{
    /** @var array<string,mixed> */
    private array $config;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody, string $textBody): void
    {
        $protocol = strtolower(trim((string) ($this->config['protocol'] ?? 'mail')));

        if ($protocol === 'smtp') {
            $this->sendViaSmtp($fromEmail, $fromName, $toEmail, $subject, $htmlBody, $textBody);
            return;
        }

        $this->sendViaMail($fromEmail, $fromName, $toEmail, $subject, $htmlBody, $textBody);
    }

    private function sendViaMail(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody, string $textBody): void
    {
        $boundary = 'bnd_' . bin2hex(random_bytes(12));
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $this->formatAddress($fromEmail, $fromName),
            'Reply-To: ' . $fromEmail,
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $message = $this->buildMimeMessage($boundary, $htmlBody, $textBody);
        $encodedSubject = $this->encodeHeader($subject);

        if (!mail($toEmail, $encodedSubject, $message, implode("\r\n", $headers))) {
            throw new RuntimeException('Email gagal dikirim ke ' . $toEmail . '. Periksa konfigurasi email server.');
        }
    }

    private function sendViaSmtp(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody, string $textBody): void
    {
        $host = trim((string) ($this->config['SMTPHost'] ?? ''));
        $port = (int) ($this->config['SMTPPort'] ?? 465);
        $timeout = (int) ($this->config['SMTPTimeout'] ?? 15);
        $crypto = strtolower(trim((string) ($this->config['SMTPCrypto'] ?? '')));
        $username = trim((string) ($this->config['SMTPUser'] ?? ''));
        $password = (string) ($this->config['SMTPPass'] ?? '');

        $transportHost = $crypto === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client($transportHost . ':' . $port, $errorCode, $errorMessage, $timeout);
        if (!is_resource($socket)) {
            throw new RuntimeException('Koneksi SMTP gagal: ' . $errorMessage . ' (' . $errorCode . ').');
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expectResponse($socket, [220]);
            $this->writeCommand($socket, 'EHLO ' . $this->resolveHostname());
            $this->expectResponse($socket, [250]);

            if ($crypto === 'tls') {
                $this->writeCommand($socket, 'STARTTLS');
                $this->expectResponse($socket, [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Gagal mengaktifkan enkripsi TLS untuk koneksi SMTP.');
                }
                $this->writeCommand($socket, 'EHLO ' . $this->resolveHostname());
                $this->expectResponse($socket, [250]);
            }

            if ($username !== '') {
                $this->writeCommand($socket, 'AUTH LOGIN');
                $this->expectResponse($socket, [334]);
                $this->writeCommand($socket, base64_encode($username));
                $this->expectResponse($socket, [334]);
                $this->writeCommand($socket, base64_encode($password));
                $this->expectResponse($socket, [235]);
            }

            $this->writeCommand($socket, 'MAIL FROM:<' . $fromEmail . '>');
            $this->expectResponse($socket, [250]);
            $this->writeCommand($socket, 'RCPT TO:<' . $toEmail . '>');
            $this->expectResponse($socket, [250, 251]);
            $this->writeCommand($socket, 'DATA');
            $this->expectResponse($socket, [354]);

            $boundary = 'bnd_' . bin2hex(random_bytes(12));
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $this->formatAddress($fromEmail, $fromName),
                'To: ' . $toEmail,
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $this->buildMimeMessage($boundary, $htmlBody, $textBody);
            $message = preg_replace('/(?m)^\./', '..', $message) ?? $message;

            fwrite($socket, $message . "\r\n.\r\n");
            $this->expectResponse($socket, [250]);

            $this->writeCommand($socket, 'QUIT');
            $this->expectResponse($socket, [221]);
        } finally {
            fclose($socket);
        }
    }

    private function writeCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /**
     * @param int[] $expectedCodes
     */
    private function expectResponse($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('Server SMTP tidak memberikan respons.');
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP error [' . $code . ']: ' . trim($response));
        }

        return $response;
    }

    private function resolveHostname(): string
    {
        $serverName = trim((string) ($_SERVER['SERVER_NAME'] ?? ''));
        if ($serverName !== '') {
            return $serverName;
        }

        $hostname = gethostname();
        if ($hostname !== false && $hostname !== '') {
            return $hostname;
        }

        return 'localhost';
    }

    private function formatAddress(string $email, string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $email;
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function buildMimeMessage(string $boundary, string $htmlBody, string $textBody): string
    {
        return implode("\r\n", [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $textBody,
            '',
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $htmlBody,
            '',
            '--' . $boundary . '--',
            '',
        ]);
    }
}
