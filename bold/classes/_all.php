<?php
// require __DIR__ . "/guzzle.php";
$requires = [
    "User",
    "Category",
    "Status",
    "Request"
];

foreach ($requires as $require)
{
    require __DIR__ . "/" . $require . ".php";
}

unset($requires, $require);
?>