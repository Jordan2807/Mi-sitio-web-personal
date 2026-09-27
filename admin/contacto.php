<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

$mensaje = "";
$error = "";


/*
 * GUARDAR CAMBIOS
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $redes_sociales = trim($_POST["redes_sociales"] ?? "");


    if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $error = "El correo electrónico no tiene un formato válido.";

    } else {

        $sql = "UPDATE perfil SET
                    correo = ?,
                    telefono = ?,
                    redes_sociales = ?
                WHERE id = 1";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sss",
            $correo,
            $telefono,
            $redes_sociales
        );


        if ($stmt->execute()) {

            $mensaje = "La información de contacto se actualizó correctamente.";

        } else {

            $error = "No se pudieron guardar los cambios.";
        }

        $stmt->close();
    }
}


/*
 * OBTENER INFORMACIÓN ACTUAL
 */
$sql = "SELECT correo, telefono, redes_sociales
        FROM perfil
        WHERE id = 1
        LIMIT 1";

$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    die("No existe información del perfil.");
}

$perfil = $resultado->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Administrar contacto</title>

</head>

<body>

    <?php require_once __DIR__ . "/navbar.php"; ?>
    <h1>Administrar contacto</h1>


    <?php if ($mensaje !== ""): ?>

        <p>
            <strong>
                <?php echo limpiar($mensaje); ?>
            </strong>
        </p>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <p>
            <strong>
                <?php echo limpiar($error); ?>
            </strong>
        </p>

    <?php endif; ?>


    <form method="POST">

        <h2>Información de contacto</h2>


        <p>

            <label for="correo">
                Correo electrónico:
            </label>

            <br>

            <input
                type="email"
                id="correo"
                name="correo"
                value="<?php echo limpiar($perfil["correo"]); ?>"
            >

        </p>


        <p>

            <label for="telefono">
                Teléfono:
            </label>

            <br>

            <input
                type="text"
                id="telefono"
                name="telefono"
                value="<?php echo limpiar($perfil["telefono"]); ?>"
            >

        </p>


        <p>

            <label for="redes_sociales">
                Redes sociales:
            </label>

            <br>

            <textarea
                id="redes_sociales"
                name="redes_sociales"
                rows="5"
                cols="60"
            ><?php echo limpiar($perfil["redes_sociales"]); ?></textarea>

        </p>


        <p>
            Puedes colocar una red social por línea.
        </p>


        <button type="submit">
            Guardar cambios
        </button>

    </form>


    <hr>


    <p>
        <a href="index.php">
            Volver al panel
        </a>
    </p>


    <p>
        <a href="../contacto.php">
            Ver página de contacto
        </a>
    </p>

</body>

</html>