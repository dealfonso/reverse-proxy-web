<?php
    session_start();
    require_once('config.php');
    require_once('util.php');

    $res = autenticar();
    [ $authenticated, $message ] = $res;

    $errores = [];

    if ($authenticated) {
        // Comprobar si el usuario existe en la lista de usuarios; si es así, guardamos el usuario y arreando
        $usuarios = obtener_lista_usuarios();
        $login = $message;
        if (isset($usuarios->$login)) {
            $_SESSION['usuario'] = $login;
            header('Location: ' . __BASE_URL);
            die();
        } else {
            array_push($errores, "el usuario no tiene permiso para usar la aplicación");
        }
    } else {
        array_push($errores, "error al autenticar: $message");
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
        <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">        
        <style>
            a, a:hover, .btn-link, .btn-link:hover {
                color: inherit;
                text-decoration: inherit;
            }
        </style>
    </head>
    <body class="m-5">
        <div class="container">
            <div class="row">
                <div class="titulo text-center">
                    <h1>proxy web</h1>
                    <h2>error al autenticar</h2>
                    <div class="col-md-4 offset-md-4">
                    <?php mostrar_errores($errores??[]); ?>
                    </div>
                    <a href="<?php echo __BASE_URL; ?>">volver</a>
                </div>
            </div>        
        </div>
    </body>
</html>