<?php
$previewBasePath = appBasePath();
$bootstrapCssPath = __DIR__ . '/../../../public/assets/vendor/bootstrap/css/bootstrap.min.css';
$bootstrapCssVersion = is_file($bootstrapCssPath) ? (string) filemtime($bootstrapCssPath) : '1';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Surat - SAPA LPPM</title>
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap/css/bootstrap.min.css?v=' . $bootstrapCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/css/style.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
</head>
<body class="letter-preview-body">
<div class="container-fluid py-3">
    <div class="d-flex gap-2 justify-content-end mb-3">
        <a href="<?= htmlspecialchars((string) ($backRoute ?? '?route=letters'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Kembali</a>
        <a href="<?= htmlspecialchars($basePath . '/persuratan/' . (int) $letterId . '/download', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Download PDF</a>
    </div>

    <div class="a4-preview-sheet">
        <iframe
            class="a4-preview-frame"
            sandbox="allow-same-origin"
            srcdoc="<?= htmlspecialchars((string) $letterHtml, ENT_QUOTES, 'UTF-8'); ?>"
        ></iframe>
    </div>
</div>
</body>
</html>
