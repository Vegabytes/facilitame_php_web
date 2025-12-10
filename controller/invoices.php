<?php
global $pdo;
if (asesoria()) {
    // Asesoría ve facturas recibidas de clientes
    // La lógica está en pages/asesoria/invoices.php
    $info = []; // <-- AÑADIR ESTO
}
elseif (proveedor())  
{
    $user = new User();

    $customers = get_customers($user);
    $category_ids = $user->getCategoryIds();
    $placeholders = placeholders($category_ids);

    $query = "SELECT
        req.*,
        sta.status_name AS status_name,
        cat.name AS category_name,
        MAX(inv.invoice_date) AS last_invoice
    FROM `requests` req
    LEFT JOIN requests_statuses sta ON sta.id = req.status_id
    LEFT JOIN categories cat ON cat.id = req.category_id
    LEFT JOIN invoices inv ON req.id = inv.request_id
    WHERE 1
    AND req.status_id = 7
    AND req.category_id IN ($placeholders)
    AND req.deleted_at IS NULL
    GROUP BY req.id, sta.status_name, cat.name;";
    $stmt = $pdo->prepare($query);
    // $stmt->bindValue(":", $);
    $stmt->execute($category_ids);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $current_year = date("Y");
    $current_month = date("m");

    // Construcción del array que contiene información de clientes y servicios :: inicio
    foreach ($customers as $i => $customer)
    {
        foreach ($requests as $j => $request)
        {
            if ($request["user_id"] == $customer["id"])
            {
                // Comprobar si el servicio está verificado :: inicio
                if (is_null($request["last_invoice"]))
                {
                    $request["last_invoice"] = "1970-01-01";
                }
                $verified = "0";
                $last_invoice_year = date("Y", strtotime($request["last_invoice"]));
                $last_invoice_month = date("m", strtotime($request["last_invoice"]));

                if ($current_year == $last_invoice_year && $current_month == $last_invoice_month)
                {
                    $verified = "1";
                }
                // Comprobar si el servicio está verificado :: fin

                $request["verified"] = $verified;
                $customers[$i]["requests"][] = $request;
                unset($requests[$j]);
            }
        }
        if (!isset($customers[$i]["requests"]))
        {
            $customers[$i]["requests"] = [];
        }
    }
    // Construcción del array que contiene información de clientes y servicios :: fin




    // Comprobar si los clientes están verificados :: inicio
    foreach ($customers as $i => $customer)
    {
        $verified = "1";

        foreach ($customer["requests"] as $j => $request)
        {
            if ($request["verified"] === "0")
            {
                $verified = "0";
            }
        }

        $customers[$i]["verified"] = $verified;
    }
    // Comprobar si los clientes están verificados :: fin

    $info = compact("user", "customers");
}
elseif (cliente())
{
    $user = new User();

    $query = "SELECT req.*, cat.name AS category_name
    FROM `requests` req
    LEFT JOIN categories cat ON cat.id = req.category_id
    WHERE 1
    AND req.user_id = :user_id
    AND req.status_id = 7 -- activada
    AND req.deleted_at IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":user_id", $user->id);
    $stmt->execute();
    $requests = $stmt->fetchAll();

    foreach ($requests as $i => $r)
    {
        $query = "SELECT * FROM `invoices` WHERE 1 AND request_id = :request_id AND YEAR(invoice_date) = YEAR(CURRENT_DATE) AND MONTH(invoice_date) = MONTH(CURRENT_DATE)";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $r["id"]);
        $stmt->execute();
        $aux = $stmt->fetch();

        if ($aux === false)
        {
            $requests[$i]["verified"] = "0";
        }
        else
        {
            $requests[$i]["verified"] = "1";
        }

        $requests[$i]["details"] = get_request_category_info($r);
    }

    $info = compact("requests");
}
