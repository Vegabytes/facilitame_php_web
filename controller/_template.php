<?php
$request = get_request($_GET["id"]);
if ($request === false)
{
    set_toastr("ko", "No se puede mostrar.");
    header("Location:home");
    exit;
}

$category = get_category($request["category_id"]);

$info = compact("request", "category");
?>