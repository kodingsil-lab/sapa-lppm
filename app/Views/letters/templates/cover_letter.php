<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4; margin: 2cm 2.54cm; }
        body { font-family: "Bookman Old Style", serif; font-size: 12pt; color: #000; }
        .line { border-bottom: 2px solid #000; margin-bottom: 12px; }
        .sig { width: 280px; margin-left: auto; margin-top: 20px; }
        .sig img { max-width: 205px; max-height: 88px; margin: 6px 0; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="line"></div>
    <p>Template fallback surat.</p>

    <div class="sig">
        <div><?= htmlspecialchars((string) $kotaSurat, ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars((string) $formattedCreatedDate, ENT_QUOTES, 'UTF-8'); ?></div>
        <div>Kepala LPPM,</div>
        <?php if (!empty($signatureDataUri)): ?>
            <img src="<?= htmlspecialchars((string) $signatureDataUri, ENT_QUOTES, 'UTF-8'); ?>" alt="Tanda Tangan">
        <?php else: ?>
            <br><br><br>
        <?php endif; ?>
        <div class="bold"><?= htmlspecialchars((string) $chairmanName, ENT_QUOTES, 'UTF-8'); ?></div>
        <div>NUPTK <?= htmlspecialchars((string) $chairmanIdentifier, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
</body>
</html>
