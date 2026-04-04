-- Example seed users for admin panel roles (replace password hash before production)
INSERT INTO users (name, nidn, email, username, password, role, unit, phone)
VALUES
    ('Kepala LPPM', '0000000001', 'kepala@sapa-lppm.local', 'kepala_lppm', 'CHANGE_ME_HASH', 'kepala_lppm', 'LPPM', '0000000001'),
    ('Admin LPPM', '0000000002', 'admin@sapa-lppm.local', 'admin', 'CHANGE_ME_HASH', 'admin', 'LPPM', '0000000002');
