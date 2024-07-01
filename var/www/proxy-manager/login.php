<?php
    session_start();
    require_once('externos/apr1_md5.php');
    require_once('util.php');

    if (! isset($_SESSION['usuario'])) {
        // Si no hay un usuario logado, vamos a ver si quieren acceder con usuario y clave

        if (isset($_POST['login'])) {
            $usuario = $_POST['usuario']??false;
            $clave = $_POST['clave']??false;

            $usuarios = obtener_lista_usuarios();

            if (isset($usuarios->$usuario)) {
                $valid = APR1_MD5::check($clave, $usuarios->$usuario);
                if ($valid === true) {
                    $_SESSION['usuario'] = $usuario;
                    return;
                } else {
                }
            }
            $errores = [];
            array_push($errores, 'usuario o clave no validos');
        }
    }
    else {
        // Si hay usuario logado, miramos a ver si quiere salir o si ya no tiene permisos
        $usuarios = obtener_lista_usuarios();
        $usuario = $_SESSION['usuario'];
        if ((!isset($usuarios->$usuario)) ||
            (isset($_POST['logout']))) {
            unset($_SESSION['usuario']);
            session_destroy();
        } else {
            return;
        }
    }

    /**
     * Si hemos llegado aqui, hay que hacer login y asegurarse de que no hay usuario logado
     */

    unset($_SESSION['usuario']);
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
                    <h2>Login</h2>
                </div>
            </div>        
            <div class="col-12 col-md-4 offset-md-4 text-center">
                <a class="mt-3 btn btn-outline-secondary" role="button" href="<?php echo rtrim(__BASE_URL, "/"); ?>/auth.php">autenticacion UPV<br/>
                    <img width="72px" class="my-1" src="img/logoupv.png" /> 
                </a>
            </div>
            <div class="col-12 col-md-4 offset-md-4 shadow row p-2 bg-light my-5 p-3">
                <?php
                mostrar_errores();
                ?>
                <form method="post" class="text-center">
                    <input type="text" class="mt-3 form-control" name="usuario" placeholder="username" value="<?php echo $_POST['usuario']??""; ?>" required>
                    <input type="password" class="my-3 form-control" name="clave" placeholder="password" required>
                    <button type="submit" class="btn btn-primary" name="login">acceder con usuario</button><br/>
                </form>
            </div>
        </div>
    </body>
</html>