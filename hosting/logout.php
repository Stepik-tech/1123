<?php
require __DIR__ . '/includes/config.php';
// «Запомнить меня»: гасим токен автологина на сервере и в браузере
if (!empty($_COOKIE['vds_remember'])) {
    [$rid, $rtok] = array_pad(explode(':', (string)$_COOKIE['vds_remember'], 2), 2, '');
    if ($rid !== '' && $rtok !== '') {
        db()->prepare('DELETE FROM remember_tokens WHERE user_id=? AND token_hash=?')
           ->execute([(int)$rid, hash('sha256', $rtok)]);
    }
    setcookie('vds_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /login.php');
exit;
