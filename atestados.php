<?php

require_once __DIR__ . "/config/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$sql = "SELECT * FROM atestados ORDER BY anio DESC";

$resultado = $conn->query($sql);

if (!$resultado) {
    die("Error al consultar los atestados: " . $conn->error);
}

$titulo_pagina = "Atestados académicos";

require_once __DIR__ . "/includes/header.php";

?>

<h1>Atestados académicos</h1>


<?php if ($resultado->num_rows === 0): ?>

    <p>
        No hay atestados académicos registrados.
    </p>


<?php else: ?>


    <?php while ($atestado = $resultado->fetch_assoc()): ?>

        <section>

            <h2>
                <?php echo limpiar($atestado["titulo"]); ?>
            </h2>


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
                        src="<?php echo limpiar($atestado["imagen"]); ?>"
                        alt="<?php echo limpiar($atestado["titulo"]); ?>"
                        width="400"
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

        </section>

        <hr>

    <?php endwhile; ?>


<?php endif; ?>


<?php

require_once __DIR__ . "/includes/footer.php";

?>