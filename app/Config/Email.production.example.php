<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = 'your-mail@domain.com';
    public string $fromName   = 'SAPA LPPM Universitas San Pedro';
    public string $recipients = '';
    public string $userAgent = 'CodeIgniter';
    public string $protocol = 'smtp';
    public string $mailPath = '/usr/sbin/sendmail';
    public string $SMTPHost = 'smtp.your-provider.com';
    public string $SMTPAuthMethod = 'login';
    public string $SMTPUser = 'your-mail@domain.com';
    public string $SMTPPass = 'your_smtp_password';
    public int $SMTPPort = 465;
    public int $SMTPTimeout = 15;
    public bool $SMTPKeepAlive = false;
    public string $SMTPCrypto = 'ssl';
    public bool $wordWrap = true;
    public int $wrapChars = 76;
    public string $mailType = 'html';
    public string $charset = 'UTF-8';
    public bool $validate = true;
    public int $priority = 3;
    public string $CRLF = "\r\n";
    public string $newline = "\r\n";
    public bool $BCCBatchMode = false;
    public int $BCCBatchSize = 200;
    public bool $DSN = false;
}
