<?php

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

function autenticar($servidor = null) {
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
