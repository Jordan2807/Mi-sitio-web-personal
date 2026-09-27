<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($usuario === "" || $password === "") {

        $error = "Debe completar todos los campos.";

    } else {

        $sql = "SELECT * FROM administradores WHERE usuario = ? LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {

            $admin = $resultado->fetch_assoc();

            if (password_verify($password, $admin["password"])) {

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_usuario"] = $admin["usuario"];

                header("Location: index.php");
                exit;

            } else {

                $error = "Usuario o contraseña incorrectos.";
            }

        } else {

            $error = "Usuario o contraseña incorrectos.";
        }

        $stmt->close();
    }
}

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

    <h2>Iniciar sesión</h2>

    <?php if ($error !== ""): ?>

        <p>
            <?php echo limpiar($error); ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <div>
            <label for="usuario">Usuario:</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                required
            >
        </div>

        <br>

        <div>
            <label for="password">Contraseña:</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <br>

        <button type="submit">
            Iniciar sesión
        </button>

    </form>

    <br>

    <a href="../index.php">
        Volver al sitio
    </a>

</body>

</html>