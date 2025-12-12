<?php
try
{
    // Validaciones :: inicio
    $check = ["request_id", "invoice_date", "description"];
    foreach ($check as $ch)
    {
        if (!isset($_POST[$ch]) || empty($_POST[$ch]) || $_POST[$ch] == "")
        {
            json_response("ko", "Completa todos los campos", 2685303364);
        }
    }
    // Validaciones :: fin

    // Comprobar si se ha enviado una imagen
    if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK)
    {
        $upload_dir = ROOT_DIR . "/" . INVOICES_DIR . "/";

        // Obtener información sobre el archivo subido
        $file_tmp_path = $_FILES['invoice_file']['tmp_name'];
        $file_name = $_FILES['invoice_file']['name'];
        $file_size = $_FILES['invoice_file']['size'];
        $file_type = $_FILES['invoice_file']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Obtener el MIME type del archivo
        $mime_type = mime_content_type($file_tmp_path);

        // Validar la extensión del archivo
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($file_ext, $allowed_extensions))
        {
            json_response("ko", "Sólo se permiten archivos PDF, JPG, JPEG y PNG", 770364384);
        }

        // Validar el tamaño del archivo
        if ($file_size > 3 * 1024 * 1024)
        {
            json_response("ko", "El tamaño de archivo es demasiado grande.<br><br>El máximo permitido es de 3MB", 755160816);
        }

        // Crear un nombre único para el archivo
        $new_file_name = uniqid($_POST["request_id"] . '_invoice_', true) . '.' . $file_ext;

        // Ruta completa al nuevo archivo
        $dest_path = $upload_dir . $new_file_name;

        // Mover el archivo de la carpeta temporal a la ruta de destino
        if (!move_uploaded_file($file_tmp_path, $dest_path))
        {
            json_response("ko", "No se ha podido cargar la factura", 4224348152);
        }

        $query = "INSERT INTO `invoices` SET 
        request_id = :request_id,
        filename = :filename,
        description = :description,
        type = 'factura',
        invoice_date = :invoice_date,
        created_by = :current_user";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":request_id", $_POST["request_id"]);
        $stmt->bindValue(":filename", $new_file_name);
        $stmt->bindValue(":description", htmlspecialchars($_POST["description"], ENT_QUOTES, 'UTF-8'));
        $stmt->bindValue(":invoice_date", $_POST["invoice_date"]);
        $stmt->bindValue(":current_user", USER["id"]);
        $stmt->execute();
        $invoice_id = $pdo->lastInsertId();

        app_log("invoice", $invoice_id, "create", "request", $_POST["request_id"]);

        json_response("ok", "Factura cargada", 863548658);
    }
    else
    {
        json_response("ko", "Selecciona un documento para cargar", 92542448);
    }
}
catch (Exception $e)
{
    json_response("ko", "No se ha podido cargar la factura.", 2443659538);
}
