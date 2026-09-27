<?php

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$sql = "SELECT * FROM galeria ORDER BY creado_en DESC";
$resultado = $conn->query($sql);

if (!$resultado) {
    die("Error al consultar la galería: " . $conn->error);
}

$titulo_pagina = "Galería";

require_once __DIR__ . "/includes/header.php";
?>

<h1>Galería</h1>

<?php if ($resultado->num_rows === 0): ?>

    <p>No hay imágenes registradas en la galería.</p>

<?php else: ?>

    <?php while ($foto = $resultado->fetch_assoc()): ?>

        <section>

            <h2>
                <?php echo limpiar($foto['titulo']); ?>
            </h2>

            <?php if (!empty($foto['imagen'])): ?>

                <img
                    src="<?php echo limpiar($foto['imagen']); ?>"
                    alt="<?php echo limpiar($foto['titulo']); ?>"
                    width="400"
                >

            <?php endif; ?>

            <?php if (!empty($foto['descripcion'])): ?>

                <p>
                    <?php echo nl2br(limpiar($foto['descripcion'])); ?>
                </p>

            <?php endif; ?>

            <?php if (!empty($foto['categoria'])): ?>

                <p>
                    <strong>Categoría:</strong>
                    <?php echo limpiar($foto['categoria']); ?>
                </p>

            <?php endif; ?>

        </section>

    <?php endwhile; ?>

<?php endif; ?>

<?php
require_once __DIR__ . "/includes/footer.php";
?>