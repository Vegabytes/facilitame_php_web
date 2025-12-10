<?php
// // Configuración de la conexión
// $host = 'localhost'; // Dirección del servidor de la base de datos
// $db   = 'facilitame_pro'; // Nombre de la base de datos
// $user = 'appyfy_1'; // Nombre de usuario
// $pass = 'aArc4FG8gK5ejWvJ'; // Contraseña
// $charset = 'utf8mb4'; // Conjunto de caracteres

// Configuración de Data Source Name (DSN)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Opciones adicionales para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Manejo de errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Modo de obtención de datos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Deshabilita la emulación de consultas preparadas
];

try
{
    // Crear una nueva instancia de PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // echo "Conexión exitosa a la base de datos.";
}
catch (\PDOException $e)
{
    // Manejo de errores
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
