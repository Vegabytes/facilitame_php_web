<?php

use Ramsey\Uuid\Uuid;

$file_name_dir = ROOT_DIR . "/request-upload-new-document.log";

if (!user_can_access_request($_POST["request_id"])) {
    json_response("ko", "No puedes adjuntar documentaci&oacute;n a esta solicitud.", 1684243255);
}

try {
    $pdo->beginTransaction();

    $uploaded_count = 0;
    $errors = [];

    // =========================================================================
    // SOPORTE MÚLTIPLE: documents[] (array)
    // =========================================================================
    if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $files_count = count($_FILES['documents']['name']);

        for ($i = 0; $i < $files_count; $i++) {
            if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = $_FILES['documents']['name'][$i];
                continue;
            }

            $uuid = Uuid::uuid4();

            $fileTmpPath = $_FILES['documents']['tmp_name'][$i];
            $fileName = $_FILES['documents']['name'][$i];
            $fileName_store = $uuid . "-" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $fileType = $_FILES['documents']['type'][$i];
            $fileSize = $_FILES['documents']['size'][$i];
            $fileSize = $fileSize / (1024 * 1024); // Convertir bytes a MB

            $uploadFileDir = ROOT_DIR . "/" . DOCUMENTS_DIR . "/";
            $dest_path = $uploadFileDir . $fileName_store;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $query = "INSERT INTO `request_files` SET request_id = :request_id, url = :url, filename = :filename, filesize = :filesize, mime_type = :mime_type, file_type_id = :file_type_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":request_id", $_POST["request_id"]);
                $stmt->bindValue(":url", $fileName_store);
                $stmt->bindValue(":filename", $fileName);
                $stmt->bindValue(":filesize", $fileSize);
                $stmt->bindValue(":mime_type", $fileType);
                $stmt->bindValue(":file_type_id", $_POST["file_type_id"]);
                $stmt->execute();

                $new_document_id = $pdo->lastInsertId();
                app_log("document", $new_document_id, "create", "request", $_POST["request_id"]);
                $uploaded_count++;
            } else {
                $errors[] = $fileName;
            }
        }
    }
    // =========================================================================
    // SOPORTE SIMPLE: document (un solo archivo) - compatibilidad hacia atrás
    // =========================================================================
    elseif (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uuid = Uuid::uuid4();

        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $fileName_store = $uuid . "-" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $fileType = $_FILES['document']['type'];
        $fileSize = $_FILES['document']['size'];
        $fileSize = $fileSize / (1024 * 1024);

        $uploadFileDir = ROOT_DIR . "/" . DOCUMENTS_DIR . "/";
        $dest_path = $uploadFileDir . $fileName_store;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $query = "INSERT INTO `request_files` SET request_id = :request_id, url = :url, filename = :filename, filesize = :filesize, mime_type = :mime_type, file_type_id = :file_type_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":request_id", $_POST["request_id"]);
            $stmt->bindValue(":url", $fileName_store);
            $stmt->bindValue(":filename", $fileName);
            $stmt->bindValue(":filesize", $fileSize);
            $stmt->bindValue(":mime_type", $fileType);
            $stmt->bindValue(":file_type_id", $_POST["file_type_id"]);
            $stmt->execute();

            $new_document_id = $pdo->lastInsertId();
            app_log("document", $new_document_id, "create", "request", $_POST["request_id"]);
            $uploaded_count++;
        } else {
            $pdo->rollBack();
            json_response("ko", "No se ha podido cargar el documento.<br><br>Int&eacute;ntalo de nuevo, por favor", 1124089761);
        }
    }

    // Si no se subió ningún archivo
    if ($uploaded_count === 0) {
        $pdo->rollBack();
        json_response("ko", "No se ha podido cargar ning&uacute;n documento.", 1124089762);
    }

    $pdo->commit();

    // =========================================================================
    // NOTIFICACIONES (una sola vez, independiente del número de archivos)
    // =========================================================================
    $request = get_request($_POST["request_id"]);
    $provider_id = get_request_provider($request["id"])["id"];
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . "Proveedor: " . json_encode($provider_id) . "\n", FILE_APPEND | LOCK_EX);
    $sales_rep_id = customer_get_sales_rep($request["user_id"]);

    $doc_text = $uploaded_count === 1 ? "un nuevo documento" : "$uploaded_count nuevos documentos";
    $notification_subject = $uploaded_count === 1 ? "Nuevo documento" : "Nuevos documentos";

    if (admin()) {
        $notification_message = "El administrador ha cargado $doc_text en la solicitud #" . $request["id"];
        notification(USER["id"], $provider_id, $request["id"], $notification_subject, $notification_message);
        if ($sales_rep_id) {
            notification(USER["id"], $sales_rep_id, $request["id"], $notification_subject, $notification_message);
        }
    } elseif (comercial()) {
        $notification_message = "El equipo de ventas ha cargado $doc_text en la solicitud #" . $request["id"];
        notification(USER["id"], $provider_id, $request["id"], $notification_subject, $notification_message);
        notification(USER["id"], ADMIN_ID, $request["id"], $notification_subject, $notification_message);
    } elseif (proveedor()) {
        $notification_message = "El colaborador ha cargado $doc_text en la solicitud #" . $request["id"];
        notification(USER["id"], ADMIN_ID, $request["id"], $notification_subject, $notification_message);
        if ($sales_rep_id) {
            notification(USER["id"], $sales_rep_id, $request["id"], $notification_subject, $notification_message);
        }
    } elseif (cliente()) {
        $notification_message = "El cliente ha cargado $doc_text en la solicitud #" . $request["id"];
        notification_v2(
            USER["id"],
            $provider_id,
            $request["id"],
            "Documentación nueva",
            USER["name"] . " ha cargado documentación nueva en la solicitud #" . $request["id"],
            "Documentación nueva",
            "document-uploaded"
        );
        notification(
            USER["id"],
            ADMIN_ID,
            $request["id"],
            "Documentaci&oacute;n nueva",
            $notification_message
        );
        if ($sales_rep_id) {
            notification(
                USER["id"],
                $sales_rep_id,
                $request["id"],
                "Documentaci&oacute;n nueva",
                $notification_message
            );
        }
    }

    // Respuesta con información de subida
    $response_data = ['uploaded' => $uploaded_count];
    if (!empty($errors)) {
        $response_data['errors'] = $errors;
    }

    json_response("ok", "", 3301361025, $response_data);

} catch (Exception $e) {
    $pdo->rollBack();

    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);

    if (DEBUG === true) {
        json_response("ko", $e->getMessage(), 2798683574);
    } else {
        json_response("ko", "Ha ocurrido un error.<br>Int&eacute;ntalo de nuevo en unos minutos, por favor.", 2798683574);
    }
}
?>