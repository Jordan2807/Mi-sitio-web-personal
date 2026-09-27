<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

$mensaje = "";
$error = "";


/*
|--------------------------------------------------------------------------
| Función para subir foto de perfil
|--------------------------------------------------------------------------
*/

function subirFotoPerfil($archivo)
{
    $carpeta = __DIR__ . "/../uploads/";

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

    $nombre = uniqid("perfil_", true) . "." . $extension;

    $ruta_destino = $carpeta . $nombre;

    if (!move_uploaded_file(
        $archivo["tmp_name"],
        $ruta_destino
    )) {
        return false;
    }

    return "uploads/" . $nombre;
}


/*
|--------------------------------------------------------------------------
| OBTENER PERFIL
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM perfil WHERE id = 1 LIMIT 1";

$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    die("No existe información del perfil.");
}

$perfil = $resultado->fetch_assoc();


/*
|--------------------------------------------------------------------------
| ACTUALIZAR PERFIL
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre_completo = trim($_POST["nombre_completo"] ?? "");
    $titulo_profesional = trim($_POST["titulo_profesional"] ?? "");
    $descripcion_corta = trim($_POST["descripcion_corta"] ?? "");
    $acerca_de = trim($_POST["acerca_de"] ?? "");
    $intereses = trim($_POST["intereses"] ?? "");
    $habilidades = trim($_POST["habilidades"] ?? "");
    $experiencia = trim($_POST["experiencia"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $redes_sociales = trim($_POST["redes_sociales"] ?? "");


    if ($nombre_completo === "") {

        $error = "El nombre completo es obligatorio.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Subir nueva foto si se seleccionó una
        |--------------------------------------------------------------------------
        */

        $nueva_foto = subirFotoPerfil(
            $_FILES["foto_perfil"] ?? null
        );


        if ($nueva_foto === false) {

            $error = "La foto no es válida o supera el tamaño máximo de 5 MB.";

        } else {

            if ($nueva_foto !== null) {

                $foto_final = $nueva_foto;

            } else {

                $foto_final = $perfil["foto_perfil"];
            }


            /*
            |--------------------------------------------------------------------------
            | Actualizar base de datos
            |--------------------------------------------------------------------------
            */

            $sql = "UPDATE perfil SET
                        nombre_completo = ?,
                        titulo_profesional = ?,
                        descripcion_corta = ?,
                        acerca_de = ?,
                        intereses = ?,
                        habilidades = ?,
                        experiencia = ?,
                        foto_perfil = ?,
                        correo = ?,
                        telefono = ?,
                        redes_sociales = ?
                    WHERE id = 1";


            $stmt = $conn->prepare($sql);


            $stmt->bind_param(
                "sssssssssss",
                $nombre_completo,
                $titulo_profesional,
                $descripcion_corta,
                $acerca_de,
                $intereses,
                $habilidades,
                $experiencia,
                $foto_final,
                $correo,
                $telefono,
                $redes_sociales
            );


            if ($stmt->execute()) {

                /*
                |--------------------------------------------------------------------------
                | Eliminar foto anterior si fue reemplazada
                |--------------------------------------------------------------------------
                */

                if (
                    $nueva_foto !== null &&
                    !empty($perfil["foto_perfil"])
                ) {

                    $foto_anterior =
                        __DIR__ . "/../" . $perfil["foto_perfil"];

                    if (file_exists($foto_anterior)) {
                        unlink($foto_anterior);
                    }
                }


                $mensaje = "El perfil se actualizó correctamente.";

                /*
                |--------------------------------------------------------------------------
                | Actualizar datos mostrados en el formulario
                |--------------------------------------------------------------------------
                */

                $perfil["nombre_completo"] = $nombre_completo;
                $perfil["titulo_profesional"] = $titulo_profesional;
                $perfil["descripcion_corta"] = $descripcion_corta;
                $perfil["acerca_de"] = $acerca_de;
                $perfil["intereses"] = $intereses;
                $perfil["habilidades"] = $habilidades;
                $perfil["experiencia"] = $experiencia;
                $perfil["foto_perfil"] = $foto_final;
                $perfil["correo"] = $correo;
                $perfil["telefono"] = $telefono;
                $perfil["redes_sociales"] = $redes_sociales;

            } else {

                /*
                |--------------------------------------------------------------------------
                | Si falló la BD, eliminar nueva foto
                |--------------------------------------------------------------------------
                */

                if ($nueva_foto !== null) {

                    $foto_nueva =
                        __DIR__ . "/../" . $nueva_foto;

                    if (file_exists($foto_nueva)) {
                        unlink($foto_nueva);
                    }
                }

                $error = "No se pudo actualizar el perfil.";
            }

            $stmt->close();
        }
    }
}

?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar perfil</title>

</head>

<body>


<?php require_once __DIR__ . "/navbar.php"; ?>


<h1>Administrar perfil</h1>


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


<h2>Foto de perfil</h2>


<?php if (!empty($perfil["foto_perfil"])): ?>

    <p>
        <strong>Foto actual:</strong>
    </p>

    <p>

        <img
            src="../<?php echo limpiar($perfil["foto_perfil"]); ?>"
            alt="Foto de perfil"
            width="250"
        >

    </p>

<?php else: ?>

    <p>
        No hay una foto de perfil registrada.
    </p>

<?php endif; ?>


<form
    method="POST"
    enctype="multipart/form-data"
>


    <p>

        <label for="foto_perfil">
            Cambiar foto de perfil:
        </label>

        <br>

        <input
            type="file"
            id="foto_perfil"
            name="foto_perfil"
            accept=".jpg,.jpeg,.png,.webp"
        >

    </p>


    <p>
        Formatos permitidos: JPG, JPEG, PNG y WEBP.
        Tamaño máximo: 5 MB.
    </p>


    <hr>


    <h2>Información personal</h2>


    <p>

        <label for="nombre_completo">
            Nombre completo:
        </label>

        <br>

        <input
            type="text"
            id="nombre_completo"
            name="nombre_completo"
            value="<?php echo limpiar($perfil["nombre_completo"]); ?>"
            required
        >

    </p>


    <p>

        <label for="titulo_profesional">
            Título profesional:
        </label>

        <br>

        <input
            type="text"
            id="titulo_profesional"
            name="titulo_profesional"
            value="<?php echo limpiar($perfil["titulo_profesional"]); ?>"
        >

    </p>


    <p>

        <label for="descripcion_corta">
            Descripción corta:
        </label>

        <br>

        <textarea
            id="descripcion_corta"
            name="descripcion_corta"
            rows="4"
            cols="60"
        ><?php echo limpiar($perfil["descripcion_corta"]); ?></textarea>

    </p>


    <p>

        <label for="acerca_de">
            Acerca de mí:
        </label>

        <br>

        <textarea
            id="acerca_de"
            name="acerca_de"
            rows="6"
            cols="60"
        ><?php echo limpiar($perfil["acerca_de"]); ?></textarea>

    </p>


    <p>

        <label for="intereses">
            Intereses:
        </label>

        <br>

        <textarea
            id="intereses"
            name="intereses"
            rows="5"
            cols="60"
        ><?php echo limpiar($perfil["intereses"]); ?></textarea>

    </p>


    <p>

        <label for="habilidades">
            Habilidades:
        </label>

        <br>

        <textarea
            id="habilidades"
            name="habilidades"
            rows="5"
            cols="60"
        ><?php echo limpiar($perfil["habilidades"]); ?></textarea>

    </p>


    <p>

        <label for="experiencia">
            Experiencia:
        </label>

        <br>

        <textarea
            id="experiencia"
            name="experiencia"
            rows="6"
            cols="60"
        ><?php echo limpiar($perfil["experiencia"]); ?></textarea>

    </p>


    <hr>


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
            rows="4"
            cols="60"
        ><?php echo limpiar($perfil["redes_sociales"]); ?></textarea>

    </p>


    <br>


    <button type="submit">
        Guardar cambios
    </button>


</form>


<br>


<a href="index.php">
    Volver al panel
</a>


</body>
</html>