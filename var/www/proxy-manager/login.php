<?php
    session_start();
    require_once('util.php');

    if (isset($_SESSION['usuario'])) {
        header('Location: ' . rtrim(__BASE_URL, "/") . "/");
        die();
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
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">        
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
            <div class="col-12 col-md-6 offset-md-3 text-center d-flex">
                <a class="mt-3 btn btn-outline-secondary ms-auto" role="button" href="<?php echo rtrim(__BASE_URL, "/"); ?>/auth-upv.php">
                    <p>login with UPV</p>
                    <img width="72px" class="my-1" src="img/logoupv.png" /> 
                </a>
                <a class="mt-3 btn btn-outline-secondary ms-3 me-auto" role="button" href="<?php echo rtrim(__BASE_URL, "/"); ?>/auth-google.php">
                    <p>login with Google</p>
                    <i style="font-size: 56px" class="pt-5 bi bi-google"></i>
                </a>
            </div>
            <div class="col-12 col-md-4 offset-md-4 shadow row p-2 bg-light my-5 p-3">
                <form action="<?php echo rtrim(__BASE_URL, "/"); ?>/auth.php" method="post" class="text-center">
                    <input type="text" class="mt-3 form-control" name="usuario" placeholder="username" value="<?php echo $_POST['usuario']??""; ?>" required>
                    <input type="password" class="my-3 form-control" name="clave" placeholder="password" required>
                    <button type="submit" class="btn btn-primary" name="login">acceder con usuario</button><br/>
                </form>
            </div>
        </div>
    </body>
</html>
