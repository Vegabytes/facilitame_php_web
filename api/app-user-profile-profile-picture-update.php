<?php

use Ramsey\Uuid\Uuid;

if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

try
{
    $file_name_dir = __DIR__ . "/app-user-profile-profile-picture-update.log";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Inicio\n", FILE_APPEND | LOCK_EX);

    // Foto que haya tomado el usuario :: inicio
    if (isset($_FILES['image']))
    {
        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : hay algo en _FILES :) : " . json_encode($_FILES) . " \n", FILE_APPEND | LOCK_EX);
        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : CP0\n", FILE_APPEND | LOCK_EX);
        $uuid = Uuid::uuid4();
        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : CP1\n", FILE_APPEND | LOCK_EX);
        // Obtener detalles del archivo
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $fileSize = $fileSize / (1024 * 1024); // Convertir bytes a MB
        $fileName_store = str_replace("-", "_", USER["id"] . "_photo_" . $uuid . "_" . time() . "_" . $fileName);

        // // Define la ruta de almacenamiento
        $uploadFileDir = ROOT_DIR . "/" . MEDIA_DIR . "/";
        $dest_path = $uploadFileDir . $fileName_store;

        file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : se guardará en $dest_path\n", FILE_APPEND | LOCK_EX);

        // Mueve el archivo subido a la ubicación definida
        if (move_uploaded_file($fileTmpPath, $dest_path))
        {
            $pdo->beginTransaction();

            $query = "DELETE FROM `user_pictures` WHERE user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":user_id", USER["id"]);
            $stmt->execute();

            $query = "INSERT INTO `user_pictures` SET user_id = :user_id, filename = :filename, mime_type = :mime_type";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":user_id", USER["id"]);
            $stmt->bindValue(":filename", $fileName_store);
            $stmt->bindValue(":mime_type", $fileType);
            $stmt->execute();

            $pdo->commit();
        }
    }
    // Foto que haya tomado el usuario :: fin

    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : ALL GOOD\n", FILE_APPEND | LOCK_EX);
    json_response("ok", "", 2445089521);
}
catch (Throwable $e)
{
    $pdo->rollBack();
    $msg = DEBUG ? $e->getMessage() : "";
    file_put_contents($file_name_dir, date("d/m/Y H:i:s") . " : Error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    json_response("ko", "", 1889507333);
}
