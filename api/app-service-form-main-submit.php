<?php

use Ramsey\Uuid\Uuid;

$code = $_POST["code"] ?? NULL;
$form_values = json_encode($_POST["form"]);

$file_name_dir = __DIR__ . "/app-service-form-main-submit.log";
file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Inicio\n", FILE_APPEND | LOCK_EX);

try
{
    $pdo->beginTransaction();

    $query = "INSERT INTO `requests` SET 
    category_id = :category_id,
    user_id = :user_id,
    call_providers = 0,
    code = :code,
    allow_call = 0,
    form_values = :form_values,
    status_id = 1,
    request_date = CURRENT_TIMESTAMP()";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":category_id", $_POST["category_id"]);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":code", $code);
    $stmt->bindValue(":form_values", $form_values);
    $stmt->execute();
    $request_id = $pdo->lastInsertId();

    if (IS_MOBILE_APP)
    {
        $query = "UPDATE `requests` SET origin = 'app' WHERE id = :request_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $request_id);
        $stmt->execute();
    }

    // Inserción en tabla de comentarios del proveedor :: inicio
    $query = "INSERT INTO `provider_comments`
    SET
    request_id = :request_id,
    comments = ''";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    // Inserción en tabla de comentarios del proveedor :: fin

    // Procesar archivos enviados (imágenes y/o documentos)
    if (isset($_FILES['documents']) && count($_FILES['documents']['name']) > 0)
    {
        // Se espera que el array de file_type_ids esté en $_POST
        $fileTypeIds = $_POST["file_type_ids"] ?? [];
        $numFiles = count($_FILES['documents']['name']);

        for ($i = 0; $i < $numFiles; $i++)
        {
            $uuid = Uuid::uuid4();
            $fileTmpPath = $_FILES['documents']['tmp_name'][$i];
            $fileName = $_FILES['documents']['name'][$i];
            $fileName_store = $uuid . "-" . $fileName;
            $fileType = $_FILES['documents']['type'][$i];
            $fileSize = $_FILES['documents']['size'][$i] / (1024 * 1024); // en MB

            // Define la ruta de almacenamiento
            $uploadFileDir = ROOT_DIR . "/" . DOCUMENTS_DIR . "/";
            $dest_path = $uploadFileDir . $fileName_store;

            if (move_uploaded_file($fileTmpPath, $dest_path))
            {
                $query = "INSERT INTO `request_files` SET 
                          request_id = :request_id, 
                          url = :url, 
                          filename = :filename, 
                          filesize = :filesize, 
                          mime_type = :mime_type, 
                          file_type_id = :file_type_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":request_id", $request_id);
                $stmt->bindValue(":url", $fileName_store);
                $stmt->bindValue(":filename", $fileName);
                $stmt->bindValue(":filesize", $fileSize);
                $stmt->bindValue(":mime_type", $fileType);
                $file_type_id = isset($fileTypeIds[$i]) ? $fileTypeIds[$i] : 0;
                $stmt->bindValue(":file_type_id", $file_type_id);
                $stmt->execute();

                file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Archivo {$fileName_store} movido correctamente\n", FILE_APPEND | LOCK_EX);
            }
            else
            {
                file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Error moviendo archivo {$fileName}\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
    else
    {
        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : no hay archivos en documents\n", FILE_APPEND | LOCK_EX);
    }

    // Notificación + email para el colaborador :: inicio
    $template = "request-create";
    $category = get_category($_POST["category_id"]);
    $provider = get_request_provider($request_id);

    notification_v2(
        USER["id"],
        $provider["id"],
        $request_id,
        "Nueva solicitud de servicio",
        "Nueva solicitud de " . $category["name"],
        "Nueva solicitud de servicio",
        $template,
        [
            "category" => $category,
        ]
    );
    // Notificación + email para el colaborador :: fin 

    app_log("request", $request_id, "create");

    $pdo->commit();
    json_response("ok", "Solicitud recibida<br><br>Nos pondremos en contacto contigo cuanto antes.", 1085498424);
}
catch (Exception $e)
{
    $pdo->rollBack();
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 3236405288);
    }
    else
    {
        json_response("ko", "Ha ocurrido un error.<br>Inténtalo de nuevo en unos minutos, por favor.", 3236405288);
    }
}
