<?php

$password = "Admin123";

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Hash generado:</h2>";
echo "<p>" . htmlspecialchars($hash) . "</p>";