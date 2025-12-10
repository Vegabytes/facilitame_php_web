<?php
use Ramsey\Uuid\Uuid;

$code = $_POST["code"] ?? NULL;
$form_values = json_encode($_POST["form"]);

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

    // Inserción en tabla de comentarios del proveedor :: inicio
    $query = "INSERT INTO `provider_comments`
    SET
    request_id = :request_id,
    comments = ''";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    // Inserción en tabla de comentarios del proveedor :: fin

    // Procesar archivos - CORREGIDO: usar file_type_ids[] (plural, array)
    if (isset($_FILES['documents']) && is_array($_FILES['documents']['name']))
    {
        // Obtener array de tipos de archivo
        $fileTypeIds = $_POST["file_type_ids"] ?? [];
        
        foreach ($_FILES['documents']['tmp_name'] as $index => $tmpName)
        {
            if ($_FILES['documents']['error'][$index] === UPLOAD_ERR_OK)
            {
                $uuid = Uuid::uuid4();

                $fileTmpPath = $tmpName;
                $fileName = $_FILES['documents']['name'][$index];
                $fileName_store = $uuid . "-" . $fileName;
                $fileType = $_FILES['documents']['type'][$index];
                $fileSize = $_FILES['documents']['size'][$index] / (1024 * 1024); // MB

                $uploadFileDir = ROOT_DIR . "/" . DOCUMENTS_DIR . "/";
                $dest_path = $uploadFileDir . $fileName_store;

                if (move_uploaded_file($fileTmpPath, $dest_path))
                {
                    // CORREGIDO: usar el array file_type_ids con el índice correspondiente
                    $file_type_id = isset($fileTypeIds[$index]) ? intval($fileTypeIds[$index]) : 0;
                    
                    $query = "INSERT INTO `request_files` 
                    SET request_id = :request_id, url = :url, filename = :filename, 
                        filesize = :filesize, mime_type = :mime_type, file_type_id = :file_type_id";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindValue(":request_id", $request_id);
                    $stmt->bindValue(":url", $fileName_store);
                    $stmt->bindValue(":filename", $fileName);
                    $stmt->bindValue(":filesize", $fileSize);
                    $stmt->bindValue(":mime_type", $fileType);
                    $stmt->bindValue(":file_type_id", $file_type_id);
                    $stmt->execute();
                }
            }
        }
    }

    // Notificación + email para el colaborador :: inicio
    $template = "request-create";
    $category = get_category($_POST["category_id"]);
    $provider = get_request_provider($request_id);

    if ($provider && isset($provider["id"])) {
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
    }
    // Notificación + email para el colaborador :: fin 

    // Notificación + email para el comercial :: inicio
    $query = "SELECT sc.id as sales_code_id, sc.code, u.id as commercial_user_id
          FROM customers_sales_codes csc
          JOIN sales_codes sc ON csc.sales_code_id = sc.id
          JOIN users u ON sc.user_id = u.id
          WHERE csc.customer_id = :customer_id
          LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":customer_id", USER["id"]);
    $stmt->execute();
    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commercial && isset($commercial["commercial_user_id"])) {
        notification_v2(
            USER["id"],
            $commercial["commercial_user_id"],
            $request_id,
            "Un cliente tuyo ha hecho una solicitud",
            "Nueva solicitud de " . $category["name"],
            "Nuevo cliente ha realizado una solicitud",
            $template,
            [
                "category" => $category,
            ]
        );
    }
    // Notificación + email para el comercial :: fin

    app_log("request", $request_id, "create");

    $pdo->commit();
    json_response("ok", "Solicitud recibida<br><br>Nos pondremos en contacto contigo cuanto antes.", 1085498424);
}
catch (Exception $e)
{
    $pdo->rollBack();
    // CORREGIDO: comprobar si DEBUG está definido
    if (defined('DEBUG') && DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 3236405288);
    }
    else
    {
        json_response("ko", "Ha ocurrido un error.<br>Inténtalo de nuevo en unos minutos, por favor.", 3236405288);
    }
}