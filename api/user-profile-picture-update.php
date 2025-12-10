<?php

try
{
    // Ruta donde se guardarán las imágenes subidas
    $upload_dir = ROOT_DIR . "/" . MEDIA_DIR . "/";

    // Comprobar si se ha enviado una imagen
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK)
    {
        // Obtener información sobre el archivo subido
        $file_tmp_path = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        $file_type = $_FILES['avatar']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Obtener el MIME type del archivo
        $mime_type = mime_content_type($file_tmp_path);

        // Validar la extensión del archivo
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_extensions))
        {
            json_response("ko", "Sólo se permiten archivos JPG, JPEG y PNG", 1230609602);
        }

        // Validar el tamaño del archivo (5MB máximo)
        if ($file_size > 5 * 1024 * 1024)
        {
            json_response("ko", "El tamaño de archivo es demasiado grande.<br><br>El máximo permitido es de 5MB", 255556132);
        }

        // Crear un nombre único para el archivo
        $new_file_name = uniqid(USER["id"] . '_photo_', true) . '.' . $file_ext;

        // Ruta completa al nuevo archivo
        $dest_path = $upload_dir . $new_file_name;

        // Mover el archivo de la carpeta temporal a la ruta de destino
        if (!move_uploaded_file($file_tmp_path, $dest_path))
        {
            json_response("ko", "No se ha podido actualizar la imagen de perfil", 3387288415);
        }

        $query = "SELECT * FROM `user_pictures` WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $res = $stmt->fetchAll();

        if (empty($res))
        {
            $query = "INSERT INTO `user_pictures` SET user_id = :user_id, filename = :filename, mime_type = :mime_type";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":user_id", USER["id"]);
            $stmt->bindValue(":filename", $new_file_name);
            $stmt->bindValue(":mime_type", $mime_type);
            $stmt->execute();
            // $id_insertada = $pdo->lastInsertId();
            app_log("customer", USER["id"], "customer_update_profile_picture");
        }
        else
        {
            try
            {
                $current_file_name = $res[0]["filename"];
                unlink($upload_dir . $current_file_name);

                $query = "UPDATE `user_pictures` SET filename = :filename, mime_type = :mime_type WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":filename", $new_file_name);
                $stmt->bindValue(":mime_type", $mime_type);
                $stmt->bindValue(":user_id", USER["id"]);
                $stmt->execute();

                app_log("customer", USER["id"], "customer_update_profile_picture");
            }
            catch (\Throwable $th)
            {
                
            }
        }

        json_response("ok", "Imagen de perfil actualizada", 2513256785);
    }
    else
    {
        json_response("ko", "No se ha podido actualizar la imagen de perfil", 829182280);
    }
}
catch (Exception $e)
{
    json_response("ko", "No se ha podido actualizar la imagen de perfil.", 736943516);
}
