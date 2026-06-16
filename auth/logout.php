<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib_auth.php';

if (is_post()) {
    require_csrf();
    auth_logout();
    flash_set('info', 'Vous êtes déconnecté.');
    redirect(app_url('index.php'));
}

http_response_code(405);
echo 'Method not allowed';

