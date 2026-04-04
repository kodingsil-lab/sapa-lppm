<?php
$app = require __DIR__ . '/../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
$sectionKey = (string) ($sectionKey ?? '');
$masterLinks = [
    'outputs' => ['label' => 'Jenis Luaran', 'path' => '/master-data/luaran'],
    'schemes' => ['label' => 'Skema', 'path' => '/master-data/skema'],
    'scopes' => ['label' => 'Ruang Lingkup', 'path' => '/master-data/ruang-lingkup'],
    'funding-sources' => ['label' => 'Sumber Dana', 'path' => '/master-data/sumber-dana'],
];
?>
<div class="card dashboard-card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
        <?php foreach ($masterLinks as $key => $link): ?>
            <a href="<?= htmlspecialchars($basePath . $link['path'], ENT_QUOTES, 'UTF-8'); ?>" class="btn <?= $sectionKey === $key ? 'btn-primary-main' : 'btn-light-soft'; ?>">
                <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
</div>
