<?php
if (file_exists('config.local.php')) {
    require_once('config.local.php');
}

if (!defined('__FICHERO_MAPEOS')) define('__FICHERO_MAPEOS', "mapeos.json");
if (!defined('__FICHERO_USUARIOS')) define('__FICHERO_USUARIOS', "usuarios.json");
if (!defined('__SUFIJOS_VALIDOS')) define('__SUFIJOS_VALIDOS', [ "grycap.i3m.upv.es" ]);
if (!defined('__RANGOS_VALIDOS')) define('__RANGOS_VALIDOS', [ "10.0.0.0/8" ]);
if (!defined('__BASE_URL')) define('__BASE_URL', '/');
if (!defined('__FICHERO_DNS_HOST')) define('__FICHERO_DNS_HOST', "configuracion_nginx.%s.conf");
if (!defined('__PUERTOS_SOPORTADOS')) define('__PUERTOS_SOPORTADOS', [ 80, 443 ]);
if (!defined('__GOOGLE_CLIENT_ID')) define('__GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
if (!defined('__GOOGLE_CLIENT_SECRET')) define('__GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
if (!defined('__GOOGLE_REDIRECT_URI')) define('__GOOGLE_REDIRECT_URI', $_SERVER["HTTP_HOST"] . '/auth-google.php');

# We are only dealing with Google OAuth v3
define('__GOOGLE_OAUTH_VERSION', 'v3');

function login_user($user) {
    $_SESSION['usuario'] = $user;
    header('Location: ' . __BASE_URL);
    die();
}