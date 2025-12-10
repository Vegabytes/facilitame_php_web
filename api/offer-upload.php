<?php
use Ramsey\Uuid\Uuid;

if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes adjuntar ofertas.", 4258076846);
}

try
{
    $pdo->beginTransaction();

    if (isset($_FILES['offer_file']) && $_FILES['offer_file']['error'] === UPLOAD_ERR_OK)
    {
        $uuid = Uuid::uuid4();

        // Obtener detalles del archivo
        $fileTmpPath = $_FILES['offer_file']['tmp_name'];
        $fileName = $_FILES['offer_file']['name'];
        $fileName_store = $uuid . "-" . $_FILES['offer_file']['name'];

        // Define la ruta de almacenamiento
        $uploadFileDir = ROOT_DIR . "/" . DOCUMENTS_DIR . "/";
        $dest_path = $uploadFileDir . $fileName_store;

        // Mueve el archivo subido a la ubicación definida
        if (move_uploaded_file($fileTmpPath, $dest_path))
        {
            // Archivo movido con éxito, ahora puedes insertar los detalles en la base de datos
            $query = "INSERT INTO `offers` SET 
                request_id = :request_id,
                provider_id = :provider_id,
                offer_title = :offer_title,
                offer_content = :offer_content,
                offer_file = :offer_file,
                status_id = 2";
            
            $stmt = $pdo->prepare($query);

            $stmt->bindValue(":request_id", $_POST["request_id"]);
            $stmt->bindValue(":provider_id", USER["id"]);
            $stmt->bindValue(":offer_title", $_POST["offer_title"]);
            $stmt->bindValue(":offer_content", $_POST["offer_content"]);
            $stmt->bindValue(":offer_file", $fileName_store);

            $stmt->execute();
            $offer_id = $pdo->lastInsertId();
            app_log("offer", $offer_id, "create", "request", $_POST["request_id"]);
        }
    }

    set_toastr("ok", "Oferta cargada correctamente");

    $current_status = get_request_status($_POST["request_id"]);
    if ($current_status < 2 || $current_status == 10)
    {
        $query = "UPDATE `requests` SET status_id = 2 WHERE id = :request_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $_POST["request_id"]);
        $stmt->execute();
        app_log("request", $_POST["request_id"], "offer_available", "request", $_POST["request_id"]);
    }
    // Datos de la solicitud y usuarios
    $request = get_request($_POST["request_id"]);
    $sender_id = USER["id"];
    $receiver_id = $request["user_id"];    
    $title = "Nueva oferta disponible";
    $description = "Tu asesor ha cargado una nueva oferta";

    // Notificar al admin (siempre con notification básica)
    notification(
        $sender_id,
        ADMIN_ID,
        $_POST["request_id"],
        $title,
        "El colaborador ha cargado una nueva oferta en la solicitud <a target='_blank' href='" . ROOT_URL . "/request?id=" . $request["id"] . "'>" . $request["id"] . "</a>"
    );

    // Notificar al comercial (notification_v2)
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);
    $email_subject = "Ofertas disponibles para tu solicitud";
    $email_template = "offer-upload";

    if ($sales_rep_id)
    {
        notification_v2(
            $sender_id,
            $sales_rep_id,
            $_POST["request_id"],
            $title,
            $description,
            $email_subject,
            $email_template
        );
    }

    // Notificar al cliente (notification_v2)
    notification_v2(
        $sender_id,
        $receiver_id,
        $_POST["request_id"],
        $title,
        $description,
        $email_subject,
        $email_template
    );

    // Si quieres notificar al proveedor (tú), descomenta esto:
    /*
    notification_v2(
        $sender_id,
        $sender_id,
        $_POST["request_id"],
        $title,
        "Has cargado una nueva oferta en la solicitud",
        $email_subject,
        $email_template
    );
    */

    $pdo->commit();
    json_response("ok", "", 3301361025);
}
catch (Exception $e)
{
    $pdo->rollBack();

    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 2798683574);
    }
    else
    {
        json_response("ko", "Ha ocurrido un error.<br>Inténtalo de nuevo en unos minutos, por favor.", 2798683574);
    }
}
