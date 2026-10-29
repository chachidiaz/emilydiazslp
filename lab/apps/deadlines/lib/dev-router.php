<?php
// Local preview only: php -S 127.0.0.1:8000 -t lab lab/apps/deadlines/lib/dev-router.php
// PHP's built-in server ignores .htaccess, so block the same folders Hostinger does.
if (preg_match('#^/(apps/deadlines/(data|lib)|admin/trash)(/|$)#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    http_response_code(403);
    exit('Forbidden');
}
return false;
