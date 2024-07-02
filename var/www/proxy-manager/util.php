<?php
require_once('config.php');
require_once('externos/apr1_md5.php');

$_ERRORES = [];

function pre_var_dump(...$var) {
    echo("<pre>");
    foreach ($var as $v) {
        var_dump($v);
    }
    echo("</pre>");
}

function p_error(...$messages) {
    global $_ERRORES;
    foreach ($messages as $m) {
        array_push($_ERRORES, "<strong>Internal Error:</strong> $m");
    }
}

function mostrar_errores($errores = [], $class = "alert-danger") {
    if (isset($errores)) {
        if (count($errores) > 0) {
            foreach ($errores as $error) {
            echo <<<EOT
            <div class="alert $class text-center alert-dismissible fade show" role="alert">
            $error
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
    EOT;
            }
        }
    }
}

function obtener_lista_usuarios() {
    try {
        $f_usuarios = @file_get_contents(__FICHERO_USUARIOS);
        if ($f_usuarios === false) {
            $usuarios = [];
        } else
            $usuarios = json_decode($f_usuarios);
    } catch (\Exception $e) {
        $usuarios = [];
    }
    return $usuarios;
}

function autenticar_local($usuario, $clave) {
    $usuarios = obtener_lista_usuarios();

    if (isset($usuarios->$usuario)) {
        $valid = APR1_MD5::check($clave, $usuarios->$usuario);
        if ($valid === true) {
            return [ true, $usuario ];
        } else {
        }
    }
    return [ false, 'usuario o clave no validos' ];
}

function autenticar_UPV($servidor = null) {
    require_once('externos/SSO_UPV/openid.php');

    if ($servidor === null) {
        $servidor = $_SERVER['HTTP_HOST'];
    }
    $openid = new LightOpenID($servidor);
    $openid->verify_peer= true;
    $carpetaCertificados= 'externos/SSO_UPV/';
    $openid->cainfo= $carpetaCertificados . 'ca-bundle.pem';	// certificados ra�z aceptados
    $openid->capath= $carpetaCertificados;						// desactivar certificados ra�z del sistema
    $openid->cnmatch= 'www.rediris.es';							// con wrappers no se usa el atributo SAN de los certificados
    
    if(!$openid->mode) {
        $openid->identity= 'https://yo.rediris.es/soy/@upv.es';
        $openid->optional = array('contact/email');
        header('Location: ' . $openid->authUrl());
        exit();
    } elseif($openid->mode == 'cancel') {
        return [ false, 'Authentication cancelled by user' ];
    } else {
        if (@$openid->validate()) {
            // validaci�n correcta
            $idOpenID= $openid->identity;
            if (preg_match('#^https://yo\.rediris\.es/soy/([-_\w]+)@upv\.es/?$#', $idOpenID, $usrOpenID)) {
                $atOpenID= @$openid->getAttributes();
                return [ true, $usrOpenID[1] ];
            }
            else
                return [ false, 'Not a UPV user' ];
        }
        else
            return [ false, 'Invalid authentication' ];
    }		
}

function autenticar_Google() {
    // If the captured code param exists and is valid
    if (isset($_GET['code']) && !empty($_GET['code'])) {

        // Execute cURL request to retrieve the access token
        $params = [
            'code' => $_GET['code'],
            'client_id' => __GOOGLE_CLIENT_ID,
            'client_secret' => __GOOGLE_CLIENT_SECRET,
            'redirect_uri' => __GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        // Make sure access token is valid
        if (isset($response['access_token']) && !empty($response['access_token'])) {
            // Execute cURL request to retrieve the user info associated with the Google account
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/' . __GOOGLE_OAUTH_VERSION . '/userinfo');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
            $response = curl_exec($ch);
            curl_close($ch);
            $profile = json_decode($response, true);
            // Make sure the profile data exists
            if (isset($profile['email'])) {
                return [ true, $profile['email'] ];
            } else {
                return [ false, 'Could not retrieve profile information! Please try again later!' ];
            }
        } else {
            return [ false, 'Invalid access token! Please try again later!' ];
        }
    } else {
        // Define params and redirect to Google Authentication page
        $params = [
            'response_type' => 'code',
            'client_id' => __GOOGLE_CLIENT_ID,
            'redirect_uri' => __GOOGLE_REDIRECT_URI,
            // Solo hemos pedido el email, pero podríamos pedir más cosas (ver https://developers.google.com/identity/protocols/oauth2/scopes)
            'scope' => 'https://www.googleapis.com/auth/userinfo.email',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params));
        die();
    }    
}