</div>
<footer class="app-footer">
    <div class="footer-left">SAPA LPPM &copy; 2026 - Sistem Administrasi Persuratan dan Arsip LPPM</div>
    <div class="footer-right">
        Developed By
        <a href="https://wa.me/628113821126" target="_blank" rel="noopener noreferrer" class="footer-dev-link">KSJ</a>
        <span class="footer-heart">❤</span>
    </div>
</footer>
<?php $basePath = appBasePath(); ?>
<meta name="app-csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
<script>
    function getAppCsrfToken() {
        const meta = document.querySelector('meta[name="app-csrf-token"]');
        return meta ? String(meta.getAttribute('content') || '') : '';
    }

    function attachCsrfToken(form) {
        if (!form || String(form.method || '').toLowerCase() !== 'post') {
            return;
        }

        let tokenInput = form.querySelector('input[name="_csrf"]');
        if (!tokenInput) {
            tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_csrf';
            form.appendChild(tokenInput);
        }
        tokenInput.value = getAppCsrfToken();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(attachCsrfToken);
    });

    document.addEventListener('submit', function (event) {
        attachCsrfToken(event.target);
    });

    if (window.fetch) {
        const originalFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            const options = init || {};
            const method = String(options.method || 'GET').toUpperCase();
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
                const headers = new Headers(options.headers || {});
                const csrfToken = getAppCsrfToken();
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', csrfToken);
                }
                options.headers = headers;
            }
            return originalFetch(input, options);
        };
    }

    if (window.XMLHttpRequest) {
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
            this._csrfMethod = String(method || 'GET').toUpperCase();
            return originalOpen.call(this, method, url, async, user, password);
        };

        XMLHttpRequest.prototype.send = function (body) {
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(String(this._csrfMethod || 'GET'))) {
                try {
                    this.setRequestHeader('X-CSRF-Token', getAppCsrfToken());
                } catch (e) {
                    // noop
                }
            }
            return originalSend.call(this, body);
        };
    }
</script>
<?php
$appJsPath = __DIR__ . '/../../../public/assets/js/app.js';
$appJsVersion = is_file($appJsPath) ? (string) filemtime($appJsPath) : '1';
$dashboardJsPath = __DIR__ . '/../../../public/assets/js/dashboard.js';
$dashboardJsVersion = is_file($dashboardJsPath) ? (string) filemtime($dashboardJsPath) : '1';
$bootstrapJsPath = __DIR__ . '/../../../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js';
$bootstrapJsVersion = is_file($bootstrapJsPath) ? (string) filemtime($bootstrapJsPath) : '1';
?>
<script src="<?= htmlspecialchars(appAssetUrl('assets/vendor/bootstrap/js/bootstrap.bundle.min.js?v=' . $bootstrapJsVersion), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?= htmlspecialchars(appAssetUrl('assets/js/app.js?v=' . $appJsVersion), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?= htmlspecialchars(appAssetUrl('assets/js/dashboard.js?v=' . $dashboardJsVersion), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function () {
            appSidebar.classList.toggle('collapsed');
        });
    }

    document.querySelectorAll('.submenu-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const parent = this.closest('.has-submenu');
            if (!parent) {
                return;
            }
            const submenu = parent.querySelector('.sidebar-submenu');
            const arrow = parent.querySelector('.submenu-arrow');
            if (submenu) {
                submenu.classList.toggle('show');
            }
            if (arrow) {
                arrow.classList.toggle('rotate');
            }
        });
    });

    if (window.bootstrap && window.bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (!window.bootstrap.Tooltip.getInstance(el)) {
                new window.bootstrap.Tooltip(el);
            }
        });
    }
</script>
</body>
</html>
