<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

$mensaje = "";
$error = "";

/*
|--------------------------------------------------------------------------
| Función para subir imágenes
|--------------------------------------------------------------------------
*/

function subirImagenAtestado($archivo)
{
    $carpeta = __DIR__ . "/../uploads/atestados/";

    if (!isset($archivo) || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($archivo["size"] > 5 * 1024 * 1024) {
        return false;
    }

    $informacion = getimagesize($archivo["tmp_name"]);

    if ($informacion === false) {
        return false;
    }

    $extension = strtolower(pathinfo($archivo["name"], PATHINFO_EXTENSION));

    $extensiones_permitidas = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($extension, $extensiones_permitidas, true)) {
        return false;
    }

    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    $nombre = uniqid("atestado_", true) . "." . $extension;

    $ruta_destino = $carpeta . $nombre;

    if (!move_uploaded_file($archivo["tmp_name"], $ruta_destino)) {
        return false;
    }

    return "uploads/atestados/" . $nombre;
}


/*
|--------------------------------------------------------------------------
| ELIMINAR ATESTADO
|--------------------------------------------------------------------------
*/

if (isset($_GET["eliminar"])) {

    $id = (int) $_GET["eliminar"];

    $sql = "SELECT imagen FROM atestados WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $atestado = $resultado->fetch_assoc();

        $stmt->close();

        $sql = "DELETE FROM atestados WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            if (!empty($atestado["imagen"])) {

                $archivo = __DIR__ . "/../" . $atestado["imagen"];

                if (file_exists($archivo)) {
                    unlink($archivo);
                }
            }

            $mensaje = "El atestado se eliminó correctamente.";

        } else {

            $error = "No se pudo eliminar el atestado.";
        }

        $stmt->close();

    } else {

        $stmt->close();
        $error = "El atestado no existe.";
    }
}


/*
|--------------------------------------------------------------------------
| AGREGAR / EDITAR
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    $titulo = trim($_POST["titulo"] ?? "");
    $institucion = trim($_POST["institucion"] ?? "");
    $anio = trim($_POST["anio"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($titulo === "" || $institucion === "") {

        $error = "El título y la institución son obligatorios.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | AGREGAR
        |--------------------------------------------------------------------------
        */

        if ($accion === "agregar") {

            $imagen = subirImagenAtestado($_FILES["imagen"] ?? null);

            if ($imagen === false) {

                $error = "La imagen no es válida o supera el tamaño permitido.";

            } else {

                $sql = "INSERT INTO atestados
                        (titulo, institucion, anio, descripcion, imagen)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "ssiss",
                    $titulo,
                    $institucion,
                    $anio,
                    $descripcion,
                    $imagen
                );

                if ($stmt->execute()) {

                    $mensaje = "El atestado se agregó correctamente.";

                } else {

                    if ($imagen !== null) {

                        $archivo = __DIR__ . "/../" . $imagen;

                        if (file_exists($archivo)) {
                            unlink($archivo);
                        }
                    }

                    $error = "No se pudo guardar el atestado.";
                }

                $stmt->close();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EDITAR
        |--------------------------------------------------------------------------
        */

        elseif ($accion === "editar") {

            $id = (int) ($_POST["id"] ?? 0);

            /*
            |--------------------------------------------------------------------------
            | Verificar atestado actual
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT imagen FROM atestados WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows !== 1) {

                $stmt->close();

                $error = "El atestado no existe.";

            } else {

                $atestado_actual = $resultado->fetch_assoc();

                $stmt->close();

                /*
                |--------------------------------------------------------------------------
                | Revisar si se subió una nueva imagen
                |--------------------------------------------------------------------------
                */

                $nueva_imagen = subirImagenAtestado($_FILES["imagen"] ?? null);

                if ($nueva_imagen === false) {

                    $error = "La nueva imagen no es válida o supera el tamaño permitido.";

                } else {

                    if ($nueva_imagen !== null) {

                        $imagen_final = $nueva_imagen;

                    } else {

                        $imagen_final = $atestado_actual["imagen"];
                    }

                    $sql = "UPDATE atestados SET
                                titulo = ?,
                                institucion = ?,
                                anio = ?,
                                descripcion = ?,
                                imagen = ?
                            WHERE id = ?";

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param(
                        "ssissi",
                        $titulo,
                        $institucion,
                        $anio,
                        $descripcion,
                        $imagen_final,
                        $id
                    );

                    if ($stmt->execute()) {

                        /*
                        |--------------------------------------------------------------------------
                        | Eliminar imagen anterior si fue reemplazada
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $nueva_imagen !== null &&
                            !empty($atestado_actual["imagen"])
                        ) {

                            $archivo_anterior =
                                __DIR__ . "/../" . $atestado_actual["imagen"];

                            if (file_exists($archivo_anterior)) {
                                unlink($archivo_anterior);
                            }
                        }

                        $mensaje = "El atestado se actualizó correctamente.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Si falló la BD, eliminar la nueva imagen
                        |--------------------------------------------------------------------------
                        */

                        if ($nueva_imagen !== null) {

                            $archivo_nuevo =
                                __DIR__ . "/../" . $nueva_imagen;

                            if (file_exists($archivo_nuevo)) {
                                unlink($archivo_nuevo);
                            }
                        }

                        $error = "No se pudo actualizar el atestado.";
                    }

                    $stmt->close();
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CARGAR ATESTADO PARA EDITAR
|--------------------------------------------------------------------------
*/

$atestado_editar = null;

if (isset($_GET["editar"])) {

    $id = (int) $_GET["editar"];

    $sql = "SELECT * FROM atestados WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado_editar = $stmt->get_result();

    if ($resultado_editar->num_rows === 1) {

        $atestado_editar = $resultado_editar->fetch_assoc();

    } else {

        $error = "El atestado no existe.";
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| OBTENER TODOS LOS ATESTADOS
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM atestados ORDER BY anio DESC";

$resultado = $conn->query($sql);

if (!$resultado) {
    die("Error al consultar los atestados: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar atestados</title>

</head>

<body>

<?php require_once __DIR__ . "/navbar.php"; ?>


<h1>Administrar atestados académicos</h1>


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


<hr>


<?php if ($atestado_editar !== null): ?>

    <h2>Editar atestado</h2>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="accion"
            value="editar"
        >

        <input
            type="hidden"
            name="id"
            value="<?php echo $atestado_editar["id"]; ?>"
        >


        <p>

            <label for="titulo">
                Título:
            </label>

            <br>

            <input
                type="text"
                id="titulo"
                name="titulo"
                value="<?php echo limpiar($atestado_editar["titulo"]); ?>"
                required
            >

        </p>


        <p>

            <label for="institucion">
                Institución:
            </label>

            <br>

            <input
                type="text"
                id="institucion"
                name="institucion"
                value="<?php echo limpiar($atestado_editar["institucion"]); ?>"
                required
            >

        </p>


        <p>

            <label for="anio">
                Año:
            </label>

            <br>

            <input
                type="number"
                id="anio"
                name="anio"
                min="1900"
                max="2100"
                value="<?php echo limpiar($atestado_editar["anio"]); ?>"
            >

        </p>


        <p>

            <label for="descripcion">
                Descripción:
            </label>

            <br>

            <textarea
                id="descripcion"
                name="descripcion"
                rows="5"
                cols="60"
            ><?php echo limpiar($atestado_editar["descripcion"]); ?></textarea>

        </p>


        <?php if (!empty($atestado_editar["imagen"])): ?>

            <p>
                <strong>Imagen actual:</strong>
            </p>

            <p>

                <img
                    src="../<?php echo limpiar($atestado_editar["imagen"]); ?>"
                    alt="Imagen del atestado"
                    width="300"
                >

            </p>

        <?php endif; ?>


        <p>

            <label for="imagen">
                Nueva imagen:
            </label>

            <br>

            <input
                type="file"
                id="imagen"
                name="imagen"
                accept=".jpg,.jpeg,.png,.webp"
            >

        </p>

        <p>
            Formatos permitidos: JPG, JPEG, PNG y WEBP.
            Tamaño máximo: 5 MB.
        </p>


        <button type="submit">
            Guardar cambios
        </button>

        <a href="atestados.php">
            Cancelar
        </a>

    </form>


<?php else: ?>

    <h2>Agregar atestado</h2>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="accion"
            value="agregar"
        >


        <p>

            <label for="titulo">
                Título:
            </label>

            <br>

            <input
                type="text"
                id="titulo"
                name="titulo"
                required
            >

        </p>


        <p>

            <label for="institucion">
                Institución:
            </label>

            <br>

            <input
                type="text"
                id="institucion"
                name="institucion"
                required
            >

        </p>


        <p>

            <label for="anio">
                Año:
            </label>

            <br>

            <input
                type="number"
                id="anio"
                name="anio"
                min="1900"
                max="2100"
            >

        </p>


        <p>

            <label for="descripcion">
                Descripción:
            </label>

            <br>

            <textarea
                id="descripcion"
                name="descripcion"
                rows="5"
                cols="60"
            ></textarea>

        </p>


        <p>

            <label for="imagen">
                Imagen del atestado:
            </label>

            <br>

            <input
                type="file"
                id="imagen"
                name="imagen"
                accept=".jpg,.jpeg,.png,.webp"
                required
            >

        </p>

        <p>
            Formatos permitidos: JPG, JPEG, PNG y WEBP.
            Tamaño máximo: 5 MB.
        </p>


        <button type="submit">
            Agregar atestado
        </button>

    </form>

<?php endif; ?>


<hr>


<h2>Atestados registrados</h2>


<?php if ($resultado->num_rows === 0): ?>

    <p>
        No hay atestados registrados.
    </p>


<?php else: ?>


    <?php while ($atestado = $resultado->fetch_assoc()): ?>

        <section>

            <h3>
                <?php echo limpiar($atestado["titulo"]); ?>
            </h3>


            <p>

                <strong>
                    Institución:
                </strong>

                <?php echo limpiar($atestado["institucion"]); ?>

            </p>


            <p>

                <strong>
                    Año:
                </strong>

                <?php echo limpiar($atestado["anio"]); ?>

            </p>


            <?php if (!empty($atestado["imagen"])): ?>

                <p>

                    <img
                        src="../<?php echo limpiar($atestado["imagen"]); ?>"
                        alt="<?php echo limpiar($atestado["titulo"]); ?>"
                        width="300"
                    >

                </p>

            <?php endif; ?>


            <?php if (!empty($atestado["descripcion"])): ?>

                <p>

                    <?php
                    echo nl2br(
                        limpiar($atestado["descripcion"])
                    );
                    ?>

                </p>

            <?php endif; ?>


            <p>

                <a href="atestados.php?editar=<?php echo $atestado["id"]; ?>">
                    Editar
                </a>

                |

                <a
                    href="atestados.php?eliminar=<?php echo $atestado["id"]; ?>"
                    onclick="return confirm('¿Está seguro de eliminar este atestado?');"
                >
                    Eliminar
                </a>

            </p>

        </section>

        <hr>

    <?php endwhile; ?>


<?php endif; ?>


<p>

    <a href="index.php">
        Volver al panel
    </a>

</p>


</body>
</html>