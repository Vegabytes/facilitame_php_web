<?php
// Configuración básica
$uploadDir = ROOT_DIR . '/_temp-dev/'; // Directorio donde se guardarán los archivos subidos

// Verifica que se haya recibido un archivo y que no haya errores
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {

    // Asegúrate de que el directorio de destino existe, si no, créalo
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Recoge información sobre el archivo subido
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = basename($_FILES['file']['name']);
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // Define un nuevo nombre único para el archivo (opcional)
    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

    // Define la ruta completa donde se guardará el archivo
    $dest_path = $uploadDir . $newFileName;

    // Mueve el archivo subido al destino final
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Archivo subido exitosamente.',
            'file_name' => $newFileName,
            'file_size' => $fileSize,
            'file_type' => $fileType,
            'file_path' => $dest_path
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al mover el archivo subido.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se ha recibido ningún archivo o hubo un error al subirlo.']);
}
