<?php

function limpiar($texto)
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}