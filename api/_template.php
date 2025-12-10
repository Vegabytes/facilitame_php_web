<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}

try
{
    $pdo->beginTransaction();

    

    $pdo->commit();

    json_response("ok", "MENSAJE OK", 999999);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    json_response("ko", MSG, 666666);
}