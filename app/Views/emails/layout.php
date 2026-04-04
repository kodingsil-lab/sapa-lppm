<?php
/** @var string $appName */
/** @var string $subjectLine */
/** @var string|null $recipientName */
/** @var string|null $eyebrow */
/** @var string|null $title */
/** @var string|null $intro */
/** @var array<int,array<string,string>> $highlights */
/** @var string|null $bodyHtml */
/** @var string|null $ctaLabel */
/** @var string|null $ctaUrl */
/** @var string|null $footerNote */

$app = require __DIR__ . '/../../../config.php';
$appUrl = rtrim((string) ($app['app']['url'] ?? ''), '/');
$assetBaseUrl = str_ends_with($appUrl, '/public') ? $appUrl : $appUrl . '/public';
$logoUrl = $assetBaseUrl . '/assets/img/logo-unisap.png';
$logoSrc = $logoUrl;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($subjectLine ?? $appName), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, table, td, div, p, a {
            font-family: Inter, "Helvetica Neue", "Segoe UI", Arial, sans-serif !important;
        }

        .email-shell {
            max-width: 680px;
            margin: 0 auto;
        }

        .email-card {
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(18, 60, 107, 0.08);
            border: 1px solid #dce7f4;
        }

        .email-wordbreak {
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        @media only screen and (max-width: 600px) {
            .email-shell {
                width: 100% !important;
            }

            .email-card {
                border-radius: 16px !important;
            }

            .email-header,
            .email-body {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .email-header {
                padding-top: 16px !important;
                padding-bottom: 14px !important;
            }

            .email-title {
                font-size: 18px !important;
                line-height: 1.35 !important;
            }

            .email-intro,
            .email-body-copy,
            .email-recipient {
                font-size: 13px !important;
                line-height: 1.75 !important;
            }

            .email-highlight-table,
            .email-highlight-table tbody,
            .email-highlight-table tr,
            .email-highlight-table td {
                display: block !important;
                width: 100% !important;
            }

            .email-highlight-table td {
                box-sizing: border-box !important;
                border-bottom: none !important;
                padding: 10px 14px !important;
            }

            .email-highlight-table tr {
                border-bottom: 1px solid #e7eef8 !important;
            }

            .email-highlight-table tr:last-child {
                border-bottom: none !important;
            }

            .email-highlight-label {
                padding-bottom: 2px !important;
                color: #5a718d !important;
            }

            .email-cta {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f3f7fc;font-family:Inter,'Helvetica Neue','Segoe UI',Arial,sans-serif;color:#243b53;-webkit-font-smoothing:antialiased;">
    <div style="margin:0;padding:32px 12px;background:#f3f7fc;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="email-shell" style="max-width:680px;margin:0 auto;">
            <tr>
                <td style="padding:0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="email-card" style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(18,60,107,0.08);border:1px solid #dce7f4;">
                        <tr>
                            <td class="email-header" style="padding:20px 28px 16px;border-top:4px solid #2f6fd6;background:#ffffff;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td style="width:56px;vertical-align:middle;">
                                            <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Logo UNISAP" style="display:block;width:40px;height:40px;object-fit:contain;">
                                        </td>
                                        <td style="vertical-align:middle;">
                                            <div style="font-size:17px;line-height:1.3;font-weight:600;color:#123c6b;">
                                                <?= htmlspecialchars((string) $appName, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div style="margin-top:2px;font-size:12px;line-height:1.5;color:#6e83a0;">
                                                Universitas San Pedro
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="email-body" style="padding:0 28px 28px;">
                                <div style="padding-top:4px;border-top:1px solid #e7eef8;"></div>

                                <?php if (!empty($eyebrow)): ?>
                                    <div style="margin-top:18px;font-size:11px;line-height:1.5;font-weight:500;letter-spacing:0.4px;color:#5f7fa8;">
                                        <?= htmlspecialchars((string) $eyebrow, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>

                                <div class="email-title email-wordbreak" style="margin-top:8px;font-size:22px;line-height:1.4;font-weight:600;color:#173764;">
                                    <?= htmlspecialchars((string) ($title ?? $subjectLine), ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <?php if (!empty($intro)): ?>
                                    <div class="email-intro email-wordbreak" style="margin-top:12px;font-size:14px;line-height:1.8;font-weight:400;color:#556b86;">
                                        <?= htmlspecialchars((string) $intro, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($recipientName)): ?>
                                    <div class="email-recipient email-wordbreak" style="margin-top:16px;font-size:13px;line-height:1.7;color:#6b809d;">
                                        Kepada <span style="font-weight:500;color:#26486b;"><?= htmlspecialchars((string) $recipientName, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($highlights)): ?>
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="email-highlight-table" style="margin-top:22px;border:1px solid #dce7f6;border-radius:12px;background:#f9fbff;">
                                        <?php foreach ($highlights as $index => $item): ?>
                                            <tr>
                                                <td class="email-highlight-label email-wordbreak" style="padding:12px 16px;border-bottom:<?= $index < count($highlights) - 1 ? '1px solid #e7eef8' : 'none' ?>;font-size:13px;line-height:1.7;font-weight:500;color:#5a718d;width:34%;">
                                                    <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="email-wordbreak" style="padding:12px 16px;border-bottom:<?= $index < count($highlights) - 1 ? '1px solid #e7eef8' : 'none' ?>;font-size:13px;line-height:1.7;font-weight:500;color:#173764;">
                                                    <?= htmlspecialchars((string) ($item['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                <?php endif; ?>

                                <?php if (!empty($bodyHtml)): ?>
                                    <div class="email-body-copy email-wordbreak" style="margin-top:22px;font-size:14px;line-height:1.82;font-weight:400;color:#4d647f;">
                                        <?= $bodyHtml ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ctaLabel) && !empty($ctaUrl)): ?>
                                    <div style="margin-top:28px;">
                                        <a href="<?= htmlspecialchars((string) $ctaUrl, ENT_QUOTES, 'UTF-8') ?>" class="email-cta" style="display:inline-block;padding:11px 20px;border-radius:10px;background:#2f6fd6;color:#ffffff;font-size:14px;font-weight:500;text-decoration:none;">
                                            <?= htmlspecialchars((string) $ctaLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($footerNote)): ?>
                                    <div style="margin-top:28px;padding-top:18px;border-top:1px solid #e5eefb;font-size:12px;line-height:1.8;font-weight:400;color:#798ca7;">
                                        <?= htmlspecialchars((string) $footerNote, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
