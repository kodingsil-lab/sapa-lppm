<?php
$app = require __DIR__ . '/../../../../config.php';
$basePath = rtrim((string) (parse_url((string) ($app['app']['url'] ?? ''), PHP_URL_PATH) ?? ''), '/');
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    :root {
        --auth-primary: #123c6b;
        --auth-primary-soft: #eef5ff;
        --auth-accent: #4c86e8;
        --auth-border: #d9e5f3;
        --auth-text: #15314f;
        --auth-muted: #60758d;
        --auth-bg: #edf4fb;
        --auth-card: #ffffff;
        --auth-shadow: 0 28px 80px rgba(18, 60, 107, 0.14);
    }

    * {
        box-sizing: border-box;
    }

    body.auth-page {
        margin: 0;
        min-height: 100vh;
        font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        color: var(--auth-text);
        background:
            radial-gradient(circle at top left, rgba(118, 169, 255, 0.18), transparent 28%),
            radial-gradient(circle at bottom right, rgba(18, 60, 107, 0.14), transparent 24%),
            linear-gradient(135deg, #f6faff 0%, #eef4fb 50%, #f9fbfe 100%);
    }

    .auth-shell {
        width: min(1160px, calc(100% - 32px));
        margin: 28px auto;
        min-height: calc(100vh - 56px);
        display: grid;
        grid-template-columns: minmax(320px, 0.95fr) minmax(360px, 1.05fr);
        border-radius: 28px;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(10px);
        box-shadow: var(--auth-shadow);
        border: 1px solid rgba(255, 255, 255, 0.7);
    }

    .auth-hero {
        position: relative;
        padding: 42px 36px;
        color: #fff;
        background:
            linear-gradient(160deg, rgba(14, 46, 82, 0.94), rgba(29, 86, 147, 0.92)),
            url("<?= htmlspecialchars(appAssetUrl('assets/img/dashboard-admin.png'), ENT_QUOTES, 'UTF-8'); ?>") center/cover no-repeat;
    }

    .auth-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), transparent 28%, rgba(6, 21, 39, 0.14));
        pointer-events: none;
    }

    .auth-hero-inner,
    .auth-panel {
        position: relative;
        z-index: 1;
    }

    .auth-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.9rem;
        margin-bottom: 2.1rem;
    }

    .auth-brand-mark {
        width: 62px;
        height: 62px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(6px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 10px;
    }

    .auth-brand-mark img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .auth-brand-title {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .auth-brand-subtitle {
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.82);
        margin-top: 0.2rem;
    }

    .auth-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.42rem 0.82rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.14);
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    .auth-hero h1 {
        font-size: clamp(1.9rem, 2.4vw, 2.8rem);
        line-height: 1.15;
        font-weight: 800;
        margin: 0 0 0.95rem;
        max-width: 14ch;
    }

    .auth-hero p {
        font-size: 1rem;
        line-height: 1.75;
        color: rgba(255, 255, 255, 0.84);
        max-width: 52ch;
        margin: 0;
    }

    .auth-feature-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0 0;
        display: grid;
        gap: 0.9rem;
    }

    .auth-feature-item {
        display: flex;
        align-items: flex-start;
        gap: 0.8rem;
        padding: 0.9rem 1rem;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .auth-feature-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
    }

    .auth-feature-title {
        font-weight: 700;
        margin-bottom: 0.22rem;
    }

    .auth-feature-copy {
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.93rem;
        line-height: 1.6;
    }

    .auth-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 38px 32px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.72), #ffffff);
    }

    .auth-card {
        width: min(100%, 468px);
    }

    .auth-card-head {
        margin-bottom: 1.4rem;
    }

    .auth-card-title {
        font-size: 2rem;
        line-height: 1.15;
        font-weight: 800;
        color: var(--auth-text);
        margin-bottom: 0.55rem;
    }

    .auth-card-copy {
        color: var(--auth-muted);
        line-height: 1.7;
        margin-bottom: 0;
    }

    .auth-alert {
        border: 1px solid var(--auth-border);
        border-radius: 16px;
        padding: 0.95rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .auth-alert-error {
        background: #fff3f3;
        color: #9d3131;
        border-color: #f1c9c9;
    }

    .auth-alert-info {
        background: #f6faff;
        color: #2c5f95;
        border-color: #d7e6f7;
    }

    .auth-form {
        display: grid;
        gap: 1rem;
    }

    .auth-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .auth-field label {
        display: block;
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--auth-text);
        margin-bottom: 0.42rem;
    }

    .auth-field input,
    .auth-field textarea,
    .auth-field select {
        width: 100%;
        border: 1px solid var(--auth-border);
        background: #fff;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        font-size: 0.98rem;
        color: var(--auth-text);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .auth-field input[readonly] {
        background: #f7fbff;
        color: #5d7189;
    }

    .auth-field input:focus,
    .auth-field textarea:focus,
    .auth-field select:focus {
        outline: none;
        border-color: #84acee;
        box-shadow: 0 0 0 4px rgba(76, 134, 232, 0.12);
    }

    .auth-inline {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .auth-link {
        color: #2f6fd6;
        text-decoration: none;
        font-weight: 700;
    }

    .auth-link:hover {
        color: #194f9d;
        text-decoration: underline;
    }

    .auth-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        width: 100%;
        border: 0;
        border-radius: 16px;
        padding: 0.95rem 1.1rem;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .auth-btn:hover {
        transform: translateY(-1px);
    }

    .auth-btn-primary {
        color: #fff;
        background: linear-gradient(135deg, #2f6fd6, #4f88e7);
        box-shadow: 0 14px 26px rgba(47, 111, 214, 0.22);
    }

    .auth-btn-secondary {
        color: #214a78;
        background: #eef5ff;
        border: 1px solid #d8e6f8;
    }

    .auth-footer-note {
        margin-top: 1.2rem;
        color: var(--auth-muted);
        font-size: 0.92rem;
        line-height: 1.65;
    }

    .auth-bottom-credit {
        margin-top: 14px;
        text-align: center;
        color: #60758d;
        font-size: 0.84rem;
        font-weight: 600;
        line-height: 1.5;
    }

    .auth-bottom-credit a {
        color: #123c6b;
        text-decoration: none;
        font-weight: 800;
    }

    .auth-bottom-credit a:hover {
        text-decoration: underline;
    }

    .auth-bottom-heart {
        color: #dc2626;
        margin-left: 2px;
    }

    .auth-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-top: 1rem;
    }

    .auth-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.8rem;
        border-radius: 999px;
        background: #f4f8fd;
        border: 1px solid #dde8f5;
        color: #44607c;
        font-size: 0.88rem;
        font-weight: 600;
    }

    .auth-demo {
        margin-top: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 16px;
        background: #f7fbff;
        border: 1px solid #dce8f6;
        color: #4d6177;
        line-height: 1.7;
        font-size: 0.92rem;
    }

    @media (max-width: 991.98px) {
        .auth-shell {
            grid-template-columns: 1fr;
            margin: 16px auto;
            width: min(100% - 20px, 760px);
        }

        .auth-hero,
        .auth-panel {
            padding: 28px 22px;
        }
    }

    @media (max-width: 575.98px) {
        .auth-form-grid {
            grid-template-columns: 1fr;
        }

        .auth-card-title {
            font-size: 1.7rem;
        }

        .auth-shell {
            border-radius: 22px;
        }
    }
</style>
