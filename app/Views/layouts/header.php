<?php
$basePath = appBasePath();
$publicBasePath = appPublicBasePath();
$role = authRole();
$isDosen = $role === 'dosen';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isEditLetterPage = (string) ($_GET['route'] ?? '') === 'letters-show'
    && (((string) ($_GET['edit'] ?? '')) === '1' || ((string) ($_GET['head_edit'] ?? '')) === '1');
$isFormPage = strpos($requestUri, '/ajukan-surat') !== false
    || strpos($requestUri, 'route=ajukan-surat') !== false
    || $isEditLetterPage;
$styleCssPath = __DIR__ . '/../../../public/assets/css/style.css';
$styleCssVersion = is_file($styleCssPath) ? (string) filemtime($styleCssPath) : '1';
$adminCssPath = __DIR__ . '/../../../public/assets/css/admin.css';
$adminCssVersion = is_file($adminCssPath) ? (string) filemtime($adminCssPath) : '1';
$dashboardCssPath = __DIR__ . '/../../../public/assets/css/dashboard-dosen.css';
$dashboardCssVersion = is_file($dashboardCssPath) ? (string) filemtime($dashboardCssPath) : '1';
$formCssPath = __DIR__ . '/../../../public/assets/css/form-page.css';
$formCssVersion = is_file($formCssPath) ? (string) filemtime($formCssPath) : '1';
$bootstrapCssPath = __DIR__ . '/../../../public/assets/vendor/bootstrap/css/bootstrap.min.css';
$bootstrapCssVersion = is_file($bootstrapCssPath) ? (string) filemtime($bootstrapCssPath) : '1';
$bootstrapIconsCssPath = __DIR__ . '/../../../public/assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css';
$bootstrapIconsCssVersion = is_file($bootstrapIconsCssPath) ? (string) filemtime($bootstrapIconsCssPath) : '1';
$faviconUrl = appPublicUrl('unisap_favicon.ico');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAPA LPPM</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap/css/bootstrap.min.css?v=' . $bootstrapCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap-icons/css/bootstrap-icons.min.css?v=' . $bootstrapIconsCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" referrerpolicy="no-referrer">
    <?php if ($isDosen): ?>
        <link href="<?= htmlspecialchars(appAssetUrl('assets/css/style.css?v=' . $styleCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
        <link href="<?= htmlspecialchars(appAssetUrl('assets/css/dashboard-dosen.css?v=' . $dashboardCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php else: ?>
        <link href="<?= htmlspecialchars(appAssetUrl('assets/css/admin.css?v=' . $adminCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php endif; ?>
    <?php if ($isFormPage): ?>
        <link href="<?= htmlspecialchars(appAssetUrl('assets/css/form-page.css?v=' . $formCssVersion), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($isFormPage ? 'form-page' : '', ENT_QUOTES, 'UTF-8'); ?>">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="main-content">
    <?php require __DIR__ . '/topbar.php'; ?>
