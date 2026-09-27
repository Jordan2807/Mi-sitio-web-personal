<?php

require_once __DIR__ . "/proteger.php";
$usuario = $_SESSION["admin_usuario"];

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administración</title>
</head>

<body>

    <h1>Panel de administración</h1>

    <p>
        Bienvenido,
        <strong><?php echo htmlspecialchars($usuario); ?></strong>
    </p>

    <hr>

    <h2>Administrar sitio</h2>

    <ul>

        <li>
            <a href="perfil.php">
                Administrar perfil
            </a>
        </li>

        <li>
            <a href="atestados.php">
                Administrar atestados académicos
            </a>
        </li>

        <li>
            <a href="galeria.php">
                Administrar galería
            </a>
        </li>

        <li>
            <a href="contacto.php">
                Administrar contacto
            </a>
        </li>

    </ul>

    <hr>

    <p>
        <a href="cerrar-sesion.php">
            Cerrar sesión
        </a>
    </p>

    <p>
        <a href="../index.php">
            Ver sitio web
        </a>
    </p>

</body>

</html>