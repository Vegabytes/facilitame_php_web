<?php
// Pre-calcular la URL base
$base_url = ROOT_URL . "/" . DOCUMENTS_DIR . "/";

// Iconos según tipo MIME
$mimeIcons = [
    'image'       => ['icon' => 'ki-picture',      'color' => 'success'],
    'pdf'         => ['icon' => 'ki-document',     'color' => 'danger'],
    'word'        => ['icon' => 'ki-document',     'color' => 'primary'],
    'spreadsheet' => ['icon' => 'ki-chart-simple', 'color' => 'success'],
    'default'     => ['icon' => 'ki-file',         'color' => 'info'],
];

function getFileIcon($mime) {
    global $mimeIcons;
    if (strpos($mime, 'image') !== false) return $mimeIcons['image'];
    if (strpos($mime, 'pdf') !== false) return $mimeIcons['pdf'];
    if (strpos($mime, 'word') !== false || strpos($mime, 'document') !== false) return $mimeIcons['word'];
    if (strpos($mime, 'sheet') !== false || strpos($mime, 'excel') !== false) return $mimeIcons['spreadsheet'];
    return $mimeIcons['default'];
}
?>

<!-- Toolbar fijo -->
<div class="tab-toolbar">
    <div class="toolbar-upload-group">
        <!-- Zona de drop / selector -->
        <div class="upload-drop-zone" id="doc-drop-zone">
            <i class="ki-outline ki-file-up"></i>
            <span class="drop-zone-text">Arrastra o haz clic</span>
            <input id="document" 
                   type="file" 
                   name="documents[]" 
                   class="drop-zone-input" 
                   accept="image/*,application/pdf,.docx" 
                   multiple>
        </div>
        
        <select name="file_type" id="file_type" class="form-select form-select-sm">
            <option value="">Tipo de documento</option>
            <?php foreach ($file_types as $ft): ?>
                <option value="<?= $ft["id"] ?>"><?= htmlspecialchars($ft["name"]) ?></option>
            <?php endforeach; ?>
        </select>
        
        <button id="btn-upload-new-doc" class="btn btn-primary btn-sm" data-request-id="<?= $request["id"] ?>" disabled>
            <i class="ki-outline ki-cloud-add"></i>
            <span class="btn-text">Subir</span>
            <span class="btn-count badge bg-white text-primary ms-1" style="display:none;">0</span>
        </button>
    </div>
    
    <!-- Preview de archivos seleccionados -->
    <div class="upload-preview" id="doc-upload-preview" style="display:none;">
        <div class="upload-preview-header">
            <span class="preview-title">
                <i class="ki-outline ki-documents"></i>
                <span id="doc-preview-count">0 archivos</span>
            </span>
            <button type="button" class="btn-clear-all" id="btn-clear-docs" title="Limpiar todo">
                <i class="ki-outline ki-trash"></i>
            </button>
        </div>
        <div class="upload-preview-list" id="doc-preview-list"></div>
    </div>
</div>

<!-- Lista scrolleable -->
<div class="tab-list-container">
    <?php if (empty($documents)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="ki-outline ki-folder-added"></i>
            </div>
            <div class="empty-state-title">No hay documentos</div>
            <p class="empty-state-text">Sube el primer documento usando el formulario de arriba</p>
        </div>
    <?php else: ?>
        <?php foreach ($documents as $doc):
            // Encodear URL para caracteres especiales y espacios
            $file_url = $base_url . rawurlencode($doc["url"]);
            $mime = $doc["mime_type"] ?? '';
            $is_img = strpos($mime, 'image') !== false;
            $iconConfig = getFileIcon($mime);
            $file_type = $file_types_kp[$doc["file_type_id"]] ?? "Documento";
            $filename = $doc["filename"] ?? "Archivo";
        ?>
            <div class="list-card list-card-<?= $iconConfig['color'] ?>">
                <!-- Thumbnail o icono -->
                <div class="doc-thumbnail doc-thumbnail-<?= $iconConfig['color'] ?>">
                    <?php if ($is_img): ?>
                        <img src="<?= $file_url ?>" alt="<?= htmlspecialchars($filename) ?>" loading="lazy">
                    <?php else: ?>
                        <i class="ki-outline <?= $iconConfig['icon'] ?>"></i>
                    <?php endif; ?>
                </div>
                
                <div class="list-card-content">
                    <!-- Header -->
                    <div class="list-card-header">
                        <a href="<?= $file_url ?>" target="_blank" rel="noopener" class="list-card-title">
                            <?= htmlspecialchars(trim_text($filename, 40)) ?>
                        </a>
                        <span class="badge-status badge-status-info">
                            <i class="ki-outline ki-tag"></i>
                            <?= htmlspecialchars($file_type) ?>
                        </span>
                    </div>
                    
                    <!-- Meta -->
                    <div class="list-card-meta">
                        <span class="meta-item">
                            <i class="ki-outline ki-calendar"></i>
                            <?= fdate($doc["created_at"] ?? "") ?>
                        </span>
                        <?php if ($doc["filesize"] ?? 0): ?>
                            <span class="meta-item">
                                <i class="ki-outline ki-weight"></i>
                                <?= number_format($doc["filesize"], 2) ?> MB
                            </span>
                        <?php endif; ?>
                        <span class="meta-item meta-mime">
                            <?= strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: 'FILE') ?>
                        </span>
                    </div>
                </div>
                
                <!-- Acciones -->
                <div class="list-card-actions">
                    <a href="<?= $file_url ?>" 
                       target="_blank" 
                       rel="noopener" 
                       class="btn-icon btn-light-primary" 
                       title="Ver documento"
                       data-bs-toggle="tooltip">
                        <i class="ki-outline ki-eye"></i>
                    </a>
                    <a href="<?= $file_url ?>" 
                       download="<?= htmlspecialchars($filename) ?>" 
                       class="btn-icon btn-light-success" 
                       title="Descargar"
                       data-bs-toggle="tooltip">
                        <i class="ki-outline ki-cloud-download"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>