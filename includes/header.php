<?php

if (!isset($titulo_pagina)) {
    $titulo_pagina = "Mi sitio web personal";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($titulo_pagina); ?></title>

    <link rel="stylesheet" href="assets/css/estilos.css">

</head>

<body>

<?php require_once __DIR__ . "/navbar.php"; ?>

<main>