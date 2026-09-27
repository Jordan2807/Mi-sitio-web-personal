<?php

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$sql = "SELECT * FROM perfil LIMIT 1";
$resultado = $conn->query($sql);

if (!$resultado) {
    die("Error al consultar el perfil: " . $conn->error);
}

if ($resultado->num_rows === 0) {
    die("No existe información del perfil.");
}

$perfil = $resultado->fetch_assoc();

$titulo_pagina = $perfil['nombre_completo'];

require_once __DIR__ . "/includes/header.php";

?>

<section>

    <h1>
        <?php echo limpiar($perfil['nombre_completo']); ?>
    </h1>


    <?php if (!empty($perfil['foto_perfil'])): ?>

        <p>

            <img
                src="<?php echo limpiar($perfil['foto_perfil']); ?>"
                alt="Foto de perfil de <?php echo limpiar($perfil['nombre_completo']); ?>"
                width="300"
            >

        </p>

    <?php endif; ?>


    <h2>
        <?php echo limpiar($perfil['titulo_profesional']); ?>
    </h2>


    <p>
        <?php echo limpiar($perfil['descripcion_corta']); ?>
    </p>

</section>

<?php

require_once __DIR__ . "/includes/footer.php";

?>