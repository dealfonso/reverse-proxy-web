<?php
require_once("config.php");
require_once("login.php");

if (! isset($_SESSION['usuario']))
    die();

require_once("externos/ip_in_range.php");
require_once("mapeos.php");

$mapeos = Mappings::FromFile(__FICHERO_MAPEOS);
$error_abrir_fichero = false;

$errores = [];
$mensajes = [];

if ($mapeos === false) {
    array_push($errores, "error al abrir el fichero de mapeos; es posible que las acciones que realice rompan algo");
    $error_abrir_fichero = true;
    $mapeos = new Mappings();
}

$_VALORES = $_POST;

function validar_datos($dns_name, $suffix, $ip_address = null) {
    global $errores;
    $datos_validos = true;

    if (($dns_name === false) || ($suffix === false) || ($ip_address === false)) {
        array_push($errores, "no se han indicado los valores requeridos (nombre de DNS y sufijo)");
        $datos_validos = false;
    } 
    if (($suffix !== "") && (!in_array($suffix, __SUFIJOS_VALIDOS))) {
        array_push($errores, "los sufijos validos son " . implode(", ", __SUFIJOS_VALIDOS));
        $datos_validos = false;
    } 

    if ($ip_address !== null) {
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            array_push($errores, "la dirección ip proporcionada no es válida");
            $datos_validos = false;
        } else {
            $rango_valido = false;
            foreach (__RANGOS_VALIDOS as $rango) {
                if (ip_in_range($ip_address, $rango)) {
                    $rango_valido = true;
                    break;
                }    
            }
            if ($rango_valido !== true) {
                array_push($errores, "los rangos de IP validos son " . implode(", ", __RANGOS_VALIDOS));
                $datos_validos = false;
            }
        }    
    }
    return $datos_validos;
}

$dns_name = $_VALORES['dns_name']??false;
if ($dns_name === "") $dns_name = false;
$suffix = $_VALORES['suffix']??false;
$ip_address = $_VALORES['ip_address']??false;    

if (isset($_VALORES['crear'])) {
    $datos_validos = validar_datos($dns_name, $suffix, $ip_address);

    $http_port = $_VALORES['http_port']??null;
    if ($http_port === "") $http_port = null;
    $https_port = $_VALORES['https_port']??null;
    if ($https_port === "") $https_port = null;

    if ($datos_validos === true) {
        if ($suffix !== "")
            $dns_name = "$dns_name.$suffix";

        if ($mapeos->get($dns_name) !== false) {
            array_push($errores, "el DNS $dns_name ya existia y no se ha hecho nada");
        } else {
            $mapeos->add($dns_name, $ip_address, $_SESSION['usuario'], [ '80' => $http_port, '443' => $https_port]);

            if ($mapeos->to_json(__FICHERO_MAPEOS) === false) {
                $mapeos->remove($dns_name, $ip_address, $_SESSION['usuario']);
                array_push($errores, "no se ha podido actualizar la base de datos de redirecciones; por favor, recarga la página porque la información mostrada puede ser errónea");
            } else {
                if ($mapeos->gen_portmaps(__FICHERO_DNS_HOST, __PUERTOS_SOPORTADOS) === false) {
                    array_push($errores, "No se ha podido actualizar nginx. Por favor contacte con el administador.");
                }
                array_push($mensajes, "se ha creado la redirección de $dns_name a $ip_address");
                $_VALORES["dns_name"] = "";
                $_VALORES["suffix"] = "";
                $_VALORES["ip_address"] = "";
                unset($_VALORES["http_port"]);
                unset($_VALORES["https_port"]);
            }
        }        
    }
}

if (isset($_VALORES['actualizar'])) {
    $datos_validos = validar_datos($dns_name, $suffix, $ip_address);

    $http_port = $_VALORES['http_port']??null;
    if ($http_port === "") $http_port = null;
    $https_port = $_VALORES['https_port']??null;
    if ($https_port === "") $https_port = null;

    if ($datos_validos === true) {
        if ($suffix !== "")
            $dns_name = "$dns_name.$suffix";

        $redireccion = $mapeos->get($dns_name);
        if ($redireccion === false) {
            array_push($errores, "el DNS $dns_name no existía, así que no se ha hecho nada");
        } else {

            $propietario = $redireccion["owner"]??$_SESSION['usuario'];
            if ($propietario === "") $propietario = $_SESSION['usuario'];

            if ($propietario === $_SESSION['usuario']) {
                $mapeos->add($dns_name, $ip_address, $_SESSION['usuario'], [ '80' => $http_port, '443' => $https_port]);
                if (! $mapeos->to_json(__FICHERO_MAPEOS)) {
                    array_push($errores, "no se ha posido actualizar la base de datos de redirecciones; por favor, recarga la página porque la información mostrada puede ser errónea");
                } else {
                    if ($mapeos->gen_portmaps(__FICHERO_DNS_HOST, __PUERTOS_SOPORTADOS) === false) {
                        array_push($errores, "No se ha podido actualizar nginx. Por favor contacte con el administador.");
                    }
                    array_push($mensajes, "se ha actualizado la redirección de $dns_name para que apunte a $ip_address");
                    $_VALORES["dns_name"] = $dns_name;
                    $_VALORES["suffix"] = "";
                    $_VALORES["ip"] = $ip_address;
                    $_VALORES["http_port"] = $http_port===null?"":$http_port;
                    $_VALORES["https_port"] = $https_port===null?"":$https_port;
                }
            } else {
                array_push($errores, "el usuario no es el propietario de la redirección");
            }
        }        
    }
}

if (isset($_VALORES['eliminar'])) {
    if ($suffix !== "")
        $dns_name = "$dns_name.$suffix";

    $redireccion = $mapeos->get($dns_name, $ip_address);

    if ($redireccion === false) {
        array_push($errores, "el DNS $dns_name no existe o no apunta a la ip $ip_address");
        $datos_validos = false;
    } else {
        $propietario = $redireccion["owner"]??$_SESSION['usuario'];
        if ($propietario === "") $propietario = $_SESSION['usuario'];

        if ($propietario === $_SESSION['usuario']) {

            $mapeos->remove($dns_name, $ip_address);

            if ($mapeos->to_json(__FICHERO_MAPEOS) === true) {
                if ($mapeos->gen_portmaps(__FICHERO_DNS_HOST, __PUERTOS_SOPORTADOS) === false) {
                    array_push($errores, "No se ha podido actualizar nginx. Por favor contacte con el administador.");
                }
                $_VALORES["dns_name"] = $dns_name;
                $_VALORES["suffix"] = "";
                $_VALORES["ip"] = $ip_address;
                $_VALORES["http_port"] = $redireccion["portmap"][80]===null?"":$redireccion["portmap"][80];
                $_VALORES["https_port"] = $redireccion["portmap"][443]===null?"":$redireccion["portmap"][443];
                array_push($mensajes, "se ha eliminado la redirección de $dns_name a $ip_address");
            } else {
                array_push($errores, "no se ha posido actualizar la base de datos de redirecciones; por favor, recarga la página porque la información mostrada puede ser errónea");
            }
        } else {
            array_push($errores, "el usuario no es el propietario de la redirección");
        }
    }
}

/**
 * Actualizamos los valores para que se muestre lo adecuado en el formulario
 */

$_VALORES["dns_name"] = $_VALORES["dns_name"]??false; 
foreach (__SUFIJOS_VALIDOS as $sufijo) {
    $pos = strrpos($_VALORES["dns_name"], "." . $sufijo);
    if ($pos !== false) {
        $_VALORES["dns_name"] = substr($_VALORES["dns_name"], 0, $pos);
        $_VALORES["suffix"] = $sufijo;
        break;
    }
}
?>
<html>
    <head>
        <meta charset="utf-8">
        <title>proxy web</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-/bQdsTh/da6pkI1MST/rWKFNjaCP5gBSY4sEBT38Q/9RBh9AH40zEOg7Hlq2THRZ" crossorigin="anonymous"></script></head>
        <script src="https://cdn.jsdelivr.net/gh/dealfonso/power-buttons@2/dist/powerbuttons.js"></script>        
        <link href="https://fonts.googleapis.com/css2?family=Material+Icons&family=Material+Icons+Outlined" rel="stylesheet">        
        <style>

            @keyframes in_and_out {
                0% {
                    background-color: inherit;
                }
                50% {
                    background-color: #fee;  
                }
                100% {
                    background-color: inherit;
                }
            }
            .entrada {
                border-radius: 10px;
                border: 1px solid #eee;
                animation-duration: 1s;
                animation-iteration-count: 3;
                animation-timing-function: linear;
            }
            .dns-entry {
                border-radius: 10px;
            }
            a, a:hover, .btn-link, .btn-link:hover {
                color: inherit;
                text-decoration: inherit;
            }
            i[class*="material-icons"], span[class*="material-icons"] {
                vertical-align: bottom;
            }
            .selected {
                animation-name: in_and_out;
            }
            .no-owner {
                background-color: #caa;
                opacity: .5;
            }
            .small {
                font-size: .75em;
            }
            .small i {
                font-size: 18px;
                vertical-align: middle !important;
            }
        </style>
        <script type="text/javascript">
            function checkdns() {
                let suffix = $('select#suffix option:selected').val();
                let ip_address = $('input#ip_address').val();
                let dns_name = $('input#dns_name').val();
                if (suffix != "") {
                    dns_name = dns_name + "." + suffix;
                }

                let dns_exists = false;
                $('div.entrada').each(function() {
                    $(this).removeClass('selected');
                    let $e = $(this);
                    if (dns_name == $e.find('input[name="dns_name"]').val()) {
                        dns_exists = true;
                        $('html, body').animate({
                            scrollTop: $(this).offset().top
                        }, 500);
                        $(this).addClass('selected');
                    }
                })
                return dns_exists;
            }
            function setdns(dns_name) {
                $('select#suffix').val("");

                let actual_t = dns_name.length;
                let seleccionado = null;

                $('select#suffix option').each(function() {
                    let sufijo = "." + $(this).val();
                    if (sufijo === ".") return;
                    let pos = dns_name.lastIndexOf(sufijo);
                    if ( pos !== -1) {
                        if (pos < actual_t) {
                            actual_t = pos;
                            seleccionado = $(this);
                        }
                    }
                })
                if (actual_t < dns_name.length) {
                    seleccionado.prop("selected", true);
                    dns_name = dns_name.slice(0, actual_t);
                }
                $('input#dns_name').val(dns_name);
                return dns_name;
            }
            function editdata(e) {
                let $e = $(e);
                let $parents = $e.parents('div.entrada');
                let dns_name = $parents.find('input[name="dns_name"]').val();
                let ip_address = $parents.find('input[name="ip_address"]').val();
                let http_port = $parents.find('input[name="http_port"]').val();
                let https_port = $parents.find('input[name="https_port"]').val();
                dns_name = setdns(dns_name);
                $('input#ip_address').val(ip_address);
                $('input#http_port').val(http_port);
                $('input#https_port').val(https_port);
            }

            $(function() {
                $('button[name="crear"]').click(function(e) {
                    if (checkdns())
                        e.preventDefault();
                })
                $('button[name="actualizar"]').click(function(e) {
                    if (!checkdns()) {
                        e.preventDefault();
                        alert("el dns no existe");
                    }
                })
            })
        </script>
    </head>
    <body class="m-5">
        <div class="container">
            <div class="row">
                <div class="titulo text-center">
                    <div class="float-end">
                        <form method="post">
                            <button type="submit" class="btn" name="logout">
                                <?php echo $_SESSION["usuario"]; ?>
                                <i class="material-icons-outlined">
                                    logout
                                </i>                    
                            </button>
                        </form>
                    </div>
                    <a href="<?php echo($_SERVER['PHP_SELF']);?>">
                        <h1>proxy web</h1>
                        <p>lista de DNS redireccionados a direcciones IP internas<br/>
                        <span class="small">(*) la redirección solo funciona para el protocolo HTTP y HTTPS</span></p>
                    </a>
                </div>
            </div>
            <div class="dns-entry shadow row p-2 bg-light my-5 p-3">
                <form id="creacion" method="post">
                    <div class="row">
                        <div class="col-md my-auto">
                            <input type="text" class="form-control" id="dns_name" name="dns_name" placeholder="your-dns" value="<?php echo $_VALORES["dns_name"]??null; ?>">
                        </div>
                        <div class="col-md my-auto">
                            <select class="form-select" id="suffix" name="suffix">
                                <?php
                                $seleccionado = $_VALORES["suffix"]??null;
                                foreach (__SUFIJOS_VALIDOS as $sufijo) {
                                    echo "<option value=\"$sufijo\" " . ($sufijo===$seleccionado?"selected":"") . ">$sufijo</option>";
                                }
                                ?>
                                <option value="">-- ninguno --</option>
                            </select>                    
                        </div>
                        <div class="col-md my-auto">
                            <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="ip address (ej. 10.0.0.80)"  value="<?php echo $_VALORES["ip_address"]??null; ?>">
                        </div>
                        <div class="col-md my-auto">
                            <div class="input-group">
                                <span class="material-icons-outlined input-group-text">http</span>
                                <input type="number" min="1" max="65535" class="form-control" id="http_port" name="http_port" placeholder="80 (vacio = desactivado)" value="<?php echo $_VALORES["http_port"]??80; ?>">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text material-icons-outlined">https</span>
                                <input type="number" min="1" max="65535" class="form-control" id="https_port" name="https_port" placeholder="443 (vacio = desactivado)" value="<?php echo $_VALORES["https_port"]??443; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row ">
                        <div class="col-sm text-sm-end text-center mt-3">
                            <button type="submit" name="crear" class="mx-2 btn btn-primary">
                                <i class="material-icons-outlined">
                                    add
                                </i>crear
                            </button>
                        </div>
                        <div class="col-sm text-sm-start text-center mt-3">
                            <button type="submit" name="actualizar" class="mx-2 btn btn-secondary">
                                <i class="material-icons-outlined">
                                    edit
                                </i>actualizar
                            </button>
                        </div>        
                    </div>
                </form>
            </div>
<?php
    mostrar_errores($errores??[]);
    mostrar_errores($_ERRORES, "alert-warning");
    mostrar_errores($mensajes, "alert-success");
?>
            <div class="row row-cols-1 row-cols-md-3 gx-5 gy-3">
<?php
    if ($mapeos !== false) {
        echo $mapeos->to_html($_SESSION['usuario']);
    }
?>
            </div>
        </div>
    </body>
</html>