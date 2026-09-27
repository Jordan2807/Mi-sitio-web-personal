<?php

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$sql = "SELECT correo, telefono, redes_sociales FROM perfil LIMIT 1";
$resultado = $conn->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    die("No existe información de contacto.");
}

$perfil = $resultado->fetch_assoc();

$titulo_pagina = "Contacto";

require_once __DIR__ . "/includes/header.php";
?>

<h1>Contacto</h1>

<section>

    <h2>Información de contacto</h2>

    <?php if (!empty($perfil['correo'])): ?>

        <p>
            <strong>Correo electrónico:</strong>
            <a href="mailto:<?php echo limpiar($perfil['correo']); ?>">
                <?php echo limpiar($perfil['correo']); ?>
            </a>
        </p>

    <?php endif; ?>


    <?php if (!empty($perfil['telefono'])): ?>

        <p>
            <strong>Teléfono:</strong>
            <?php echo limpiar($perfil['telefono']); ?>
        </p>

    <?php endif; ?>


    <?php if (!empty($perfil['redes_sociales'])): ?>

        <p>
            <strong>Redes sociales:</strong>
            <?php echo nl2br(limpiar($perfil['redes_sociales'])); ?>
        </p>

    <?php endif; ?>

</section>

<?php
require_once __DIR__ . "/includes/footer.php";
?>