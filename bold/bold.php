<?php
// Agregar encabezados CORS al inicio del archivo
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
session_start();
// ¡AQUÍ define ROOT_DIR antes de usarlo!
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__ . '/..'));
}
define("IS_MOBILE_APP", isset($_SERVER['HTTP_X_ORIGIN']) && $_SERVER['HTTP_X_ORIGIN'] === 'app');
if (isset($_GET['page']) && $_GET['page'] != "")
{
    $page = ltrim($_GET['page'], '/');
    if (strpos($page, "/") === false)
    {
        define("RESOURCE", "/pages");
        define("PAGE", "/" . $page);
    }
    else
    {
        $aux = explode("/", $page);
        if ($aux[0] === "api")
        {
            define("RESOURCE", "/api");
            define("PAGE", "/" . $aux[1]);
        }
        else
        {
            exit("467042892");
        }
    }
}
else
{
    define("RESOURCE", "/pages");
    define("PAGE", "/login");
}
require_once("vars.php");
require(ROOT_DIR . "/vendor/autoload.php");
require(ROOT_DIR . "/bold/db.php");
require(ROOT_DIR . "/bold/functions.php");
require(ROOT_DIR . "/bold/utils/firebase-console-message.php");
require(ROOT_DIR . "/bold/utils/apple-apn.php");
// Rutas que no requieren que el usuario esté autenticado :: inicio
$no_auth = [
    "/login",
    "/sign-up",
    "/recovery",
    "/restore",
    "/activate",
    "/terms",
    "/legal",
    "/privacy",
    "/cookies",
];
// APIs que no requieren autenticación (adicionales)
$no_auth_api = [
    "/activate-with-password",
    "/_debug-session",
];
// Páginas públicas
if (in_array(PAGE, $no_auth) && RESOURCE === "/pages")
{
    // Páginas de auth usan el index.php unificado
    $auth_pages = ["/login", "/sign-up", "/recovery", "/restore"];
    
    if (in_array(PAGE, $auth_pages)) {
        define('USER', ['role' => null, 'view' => 'public']);
        define('PUBLIC_PAGE', PAGE);
        $scripts = [substr(PAGE, 1)]; // quita el "/" inicial -> "login", "sign-up", etc.
        require ROOT_DIR . "/index.php";
        close_pdo();
        exit;
    }
    
    // Resto de páginas públicas (terms, legal, privacy, cookies, activate)
    require ROOT_DIR . RESOURCE . PAGE . ".php";
    if (isset($scripts) && !empty($scripts))
    {
        foreach ($scripts as $script)
        {
        ?> <script src="assets/js/bold/<?php echo $script ?>.js?v=<?php echo time() ?>"></script> <?php
        }
    }
    ?> <script src="assets/js/bold/cookie-policy.js?v=<?php echo time() ?>"></script> <?php
    toastr();
    close_pdo();
    exit;
}
// APIs públicas (login, sign-up, recovery, etc.)
if (in_array(PAGE, $no_auth) && RESOURCE === "/api")
{
    require ROOT_DIR . "/api" . PAGE . ".php";
    close_pdo();
    exit;
}
// APIs adicionales sin autenticación
if (RESOURCE === "/api" && in_array(PAGE, $no_auth_api))
{
    require ROOT_DIR . "/api" . PAGE . ".php";
    close_pdo();
    exit;
}
// Rutas que no requieren que el usuario esté autenticado :: fin
// Autenticación del usuario
require(ROOT_DIR . "/bold/auth.php");
require(ROOT_DIR . "/bold/classes/_all.php");
if (PAGE === "/_dev")
{
    require ROOT_DIR . "/bold/_dev.php";
    exit;
}
if (RESOURCE === "/api") // Rutas API
{
    check_guest();
    $api_file = ROOT_DIR . "/api" . PAGE . ".php";
    $controller_file = ROOT_DIR . "/controller/api" . str_replace("/", "-", PAGE) . ".php";

    if (file_exists($api_file)) {
        require $api_file;
    } elseif (file_exists($controller_file)) {
        require $controller_file;
    } else {
        http_response_code(404);
        json_response("ko", "API no encontrada", 404);
    }
    close_pdo();
}
else // Rutas WEB
{    
    $info = extract(json_decode(controller(), true));
    require ROOT_DIR . "/index.php";
    close_pdo();
}