<?php

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$sql = "SELECT * FROM perfil LIMIT 1";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    die("No existe información del perfil.");
}

$perfil = $resultado->fetch_assoc();

$titulo_pagina = "Acerca de mí";

require_once __DIR__ . "/includes/header.php";

?>

<h1>Acerca de mí</h1>

<section>

    <h2>Sobre mí</h2>

    <p>
        <?php echo nl2br(limpiar($perfil['acerca_de'])); ?>
    </p>

</section>

<section>

    <h2>Intereses</h2>

    <p>
        <?php echo nl2br(limpiar($perfil['intereses'])); ?>
    </p>

</section>

<section>

    <h2>Habilidades</h2>

    <p>
        <?php echo nl2br(limpiar($perfil['habilidades'])); ?>
    </p>

</section>

<section>

    <h2>Experiencia y conocimientos</h2>

    <p>
        <?php echo nl2br(limpiar($perfil['experiencia'])); ?>
    </p>

</section>

<?php

require_once __DIR__ . "/includes/footer.php";

?>