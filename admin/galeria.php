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

function subirImagenGaleria($archivo)
{
    $carpeta = __DIR__ . "/../uploads/galeria/";

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

    $extension = strtolower(
        pathinfo($archivo["name"], PATHINFO_EXTENSION)
    );

    $extensiones_permitidas = [
        "jpg",
        "jpeg",
        "png",
        "webp"
    ];

    if (!in_array($extension, $extensiones_permitidas, true)) {
        return false;
    }

    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    $nombre = uniqid("galeria_", true) . "." . $extension;

    $ruta_destino = $carpeta . $nombre;

    if (!move_uploaded_file(
        $archivo["tmp_name"],
        $ruta_destino
    )) {
        return false;
    }

    return "uploads/galeria/" . $nombre;
}


/*
|--------------------------------------------------------------------------
| ELIMINAR IMAGEN / REGISTRO
|--------------------------------------------------------------------------
*/

if (isset($_GET["eliminar"])) {

    $id = (int) $_GET["eliminar"];


    /*
    | Obtener primero la imagen
    */

    $sql = "SELECT imagen FROM galeria WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $resultado_eliminar = $stmt->get_result();


    if ($resultado_eliminar->num_rows === 1) {

        $galeria_actual = $resultado_eliminar->fetch_assoc();

        $stmt->close();


        /*
        | Eliminar registro de la base de datos
        */

        $sql = "DELETE FROM galeria WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("i", $id);


        if ($stmt->execute()) {

            /*
            | Eliminar archivo físico
            */

            if (!empty($galeria_actual["imagen"])) {

                $archivo = __DIR__ . "/../" . $galeria_actual["imagen"];

                if (file_exists($archivo)) {
                    unlink($archivo);
                }
            }

            $mensaje = "La imagen se eliminó correctamente.";

        } else {

            $error = "No se pudo eliminar la imagen.";
        }


        $stmt->close();

    } else {

        $stmt->close();

        $error = "La imagen no existe.";
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
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");


    if ($titulo === "") {

        $error = "El título es obligatorio.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | AGREGAR
        |--------------------------------------------------------------------------
        */

        if ($accion === "agregar") {

            $imagen = subirImagenGaleria(
                $_FILES["imagen"] ?? null
            );


            if ($imagen === null) {

                $error = "Debe seleccionar una imagen.";

            } elseif ($imagen === false) {

                $error = "La imagen no es válida o supera el tamaño máximo de 5 MB.";

            } else {

                $sql = "INSERT INTO galeria
                        (titulo, descripcion, imagen, categoria)
                        VALUES (?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "ssss",
                    $titulo,
                    $descripcion,
                    $imagen,
                    $categoria
                );


                if ($stmt->execute()) {

                    $mensaje = "La imagen se agregó correctamente.";

                } else {

                    /*
                    | Si falla la BD, borrar archivo
                    */

                    $archivo = __DIR__ . "/../" . $imagen;

                    if (file_exists($archivo)) {
                        unlink($archivo);
                    }

                    $error = "No se pudo guardar la imagen.";
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
            | Obtener registro actual
            */

            $sql = "SELECT * FROM galeria WHERE id = ?";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param("i", $id);

            $stmt->execute();

            $resultado_editar = $stmt->get_result();


            if ($resultado_editar->num_rows !== 1) {

                $stmt->close();

                $error = "La imagen no existe.";

            } else {

                $galeria_actual = $resultado_editar->fetch_assoc();

                $stmt->close();


                /*
                | Intentar subir nueva imagen
                */

                $nueva_imagen = subirImagenGaleria(
                    $_FILES["imagen"] ?? null
                );


                if ($nueva_imagen === false) {

                    $error = "La nueva imagen no es válida o supera el tamaño máximo de 5 MB.";

                } else {

                    /*
                    | Si no se subió una nueva,
                    | conservar la anterior
                    */

                    if ($nueva_imagen !== null) {

                        $imagen_final = $nueva_imagen;

                    } else {

                        $imagen_final = $galeria_actual["imagen"];
                    }


                    /*
                    | Actualizar registro
                    */

                    $sql = "UPDATE galeria SET
                                titulo = ?,
                                descripcion = ?,
                                imagen = ?,
                                categoria = ?
                            WHERE id = ?";

                    $stmt = $conn->prepare($sql);

                    $stmt->bind_param(
                        "ssssi",
                        $titulo,
                        $descripcion,
                        $imagen_final,
                        $categoria,
                        $id
                    );


                    if ($stmt->execute()) {

                        /*
                        | Eliminar imagen anterior
                        | si fue reemplazada
                        */

                        if (
                            $nueva_imagen !== null &&
                            !empty($galeria_actual["imagen"])
                        ) {

                            $archivo_anterior =
                                __DIR__ . "/../" .
                                $galeria_actual["imagen"];

                            if (file_exists($archivo_anterior)) {
                                unlink($archivo_anterior);
                            }
                        }


                        $mensaje = "La imagen se actualizó correctamente.";

                    } else {

                        /*
                        | Si falla la BD,
                        | borrar nueva imagen
                        */

                        if ($nueva_imagen !== null) {

                            $archivo_nuevo =
                                __DIR__ . "/../" .
                                $nueva_imagen;

                            if (file_exists($archivo_nuevo)) {
                                unlink($archivo_nuevo);
                            }
                        }

                        $error = "No se pudo actualizar la imagen.";
                    }


                    $stmt->close();
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CARGAR REGISTRO PARA EDITAR
|--------------------------------------------------------------------------
*/

$galeria_editar = null;


if (isset($_GET["editar"])) {

    $id = (int) $_GET["editar"];

    $sql = "SELECT * FROM galeria WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $resultado_editar = $stmt->get_result();


    if ($resultado_editar->num_rows === 1) {

        $galeria_editar = $resultado_editar->fetch_assoc();

    } else {

        $error = "La imagen no existe.";
    }


    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| OBTENER TODAS LAS IMÁGENES
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM galeria ORDER BY id DESC";

$resultado = $conn->query($sql);


if (!$resultado) {
    die("Error al consultar la galería: " . $conn->error);
}

?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar galería</title>

</head>

<body>


<?php require_once __DIR__ . "/navbar.php"; ?>


<h1>Administrar galería</h1>


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


<?php if ($galeria_editar !== null): ?>


    <h2>Editar imagen</h2>


    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="accion"
            value="editar"
        >


        <input
            type="hidden"
            name="id"
            value="<?php echo $galeria_editar["id"]; ?>"
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
                value="<?php echo limpiar($galeria_editar["titulo"]); ?>"
                required
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
            ><?php echo limpiar($galeria_editar["descripcion"]); ?></textarea>

        </p>


        <p>

            <label for="categoria">
                Categoría:
            </label>

            <br>

            <input
                type="text"
                id="categoria"
                name="categoria"
                value="<?php echo limpiar($galeria_editar["categoria"]); ?>"
            >

        </p>


        <p>
            <strong>Imagen actual:</strong>
        </p>


        <p>

            <img
                src="../<?php echo limpiar($galeria_editar["imagen"]); ?>"
                alt="<?php echo limpiar($galeria_editar["titulo"]); ?>"
                width="300"
            >

        </p>


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


        <a href="galeria.php">
            Cancelar
        </a>

    </form>


<?php else: ?>


    <h2>Agregar imagen</h2>


    <form
        method="POST"
        enctype="multipart/form-data"
    >

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

            <label for="categoria">
                Categoría:
            </label>

            <br>

            <input
                type="text"
                id="categoria"
                name="categoria"
            >

        </p>


        <p>

            <label for="imagen">
                Imagen:
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
            Agregar imagen
        </button>

    </form>


<?php endif; ?>


<hr>


<h2>Imágenes de la galería</h2>


<?php if ($resultado->num_rows === 0): ?>


    <p>
        No hay imágenes registradas.
    </p>


<?php else: ?>


    <?php while ($imagen = $resultado->fetch_assoc()): ?>


        <section>

            <h3>
                <?php echo limpiar($imagen["titulo"]); ?>
            </h3>


            <?php if (!empty($imagen["imagen"])): ?>

                <p>

                    <img
                        src="../<?php echo limpiar($imagen["imagen"]); ?>"
                        alt="<?php echo limpiar($imagen["titulo"]); ?>"
                        width="300"
                    >

                </p>

            <?php endif; ?>


            <?php if (!empty($imagen["categoria"])): ?>

                <p>

                    <strong>
                        Categoría:
                    </strong>

                    <?php echo limpiar($imagen["categoria"]); ?>

                </p>

            <?php endif; ?>


            <?php if (!empty($imagen["descripcion"])): ?>

                <p>

                    <?php
                    echo nl2br(
                        limpiar($imagen["descripcion"])
                    );
                    ?>

                </p>

            <?php endif; ?>


            <p>

                <a
                    href="galeria.php?editar=<?php echo $imagen["id"]; ?>"
                >
                    Editar
                </a>

                |

                <a
                    href="galeria.php?eliminar=<?php echo $imagen["id"]; ?>"
                    onclick="return confirm('¿Está seguro de eliminar esta imagen?');"
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