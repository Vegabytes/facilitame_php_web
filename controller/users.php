<?php
if (!admin())
{
    header("Location:home?r=458056425");
    exit;
}

if (!isset($_GET["type"]) || !in_array($_GET["type"], ["sales-rep", "provider"]))
{
    header("Location:home?r=1633991279");
    exit;
}

switch ($_GET["type"])
{
    case 'sales-rep':
        define("TYPE", "sales-rep");
        $users = get_sales_reps();
        $info = compact("users");
        break;
    case 'provider':
        define("TYPE", "provider");
        $users = get_providers();
        $info = compact("users");
        break;
    default:
        header("Location:home?r=2914785613");
        exit;
        break;
}
