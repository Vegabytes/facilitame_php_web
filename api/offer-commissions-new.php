<?php
if (!admin())
{
    header("HTTP/1.1 404");
    exit;
}


try
{
    $pdo->beginTransaction();

    // 1. Comprobar si la solicitud tiene alguna oferta en estado activo (7: activada, 8: revisión solicitada )
    $query = "SELECT * FROM `offers` WHERE status_id IN (7,8) AND request_id = :request_id AND deleted_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $_POST["request_id"]);    
    $stmt->execute();
    $res = $stmt->fetchAll();
    if (count($res) > 1)
    {
        json_response("ko", "Esta solicitud tiene más de una oferta activada o pendiente de revisión.", 314142450);
    }
    if (count($res) === 0)
    {
        json_response("ko", "Esta solicitud no tiene ninguna oferta activada o pendiente de revisión", 1177676611);
    }




    // 2. Comprobar si la solicitud tiene alguna oferta 'fantasma' no desactivada
    // $query = "SELECT * FROM `commissions_admin` WHERE request_id = :request_id AND deactivated_at IS NOT NULL";
    // $stmt = $pdo->prepare($query);
    // $stmt->bindValue(":request_id", $_POST["request_id"]);
    // $stmt->execute();
    // $res = $stmt->fetch();
    // if ($res !== false)
    // {
    //     json_response("ko", "Esta solicitud ya tiene una oferta de administrador asociada. Oferta id: {$res["id"]}", 1579038579);
    // }




    // 3. Inserción en commissions_admin
    function pdo_insert($pdo, $table, $data)
    {
        $query = "DESCRIBE $table";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $columns_db = $stmt->fetchAll();

        $query_array = [];

        foreach ($columns_db as $col)
        {
            if ($col["Extra"] == "auto_increment") continue;
            
            if (isset($_POST[$col["Field"]]))
                $query_array[$col["Field"]] = $_POST[$col["Field"]];
        }

        $query_string = [];
        foreach ($query_array as $col => $val)
        {
            $query_string[] = "$col = :$col";
        }
        $query_string = implode(", ", $query_string);

        $query = "INSERT INTO `$table` SET $query_string";
        $stmt = $pdo->prepare($query);
        foreach ($query_array as $col => $val)
        {
            $stmt->bindValue(":$col", $val);
        }
        $stmt->execute();
    }
    pdo_insert($pdo, "commissions_admin", $_POST);

    $pdo->commit();

    json_response("ok", "Oferta para comisiones creada", 895695149);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    if (DEBUG)
    {
        json_response("ko", $e->getMessage(), 1461522424);
    }
    else
    {
        json_response("ko", "Error al crear la comisión", 666666);
    }
}