<div class="py-3">
    <?php if (empty($documents)): ?>
        <div class="text-center py-4">
            <i class="ki-outline ki-folder fs-3x text-muted mb-2 d-block"></i>
            <p class="text-muted fs-7 mb-0">No hay documentos</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($documents as $doc): ?>
                <div class="document-item">
                    <div class="document-icon">
                        <i class="ki-outline ki-file fs-3"></i>
                    </div>
                    <div class="document-info">
                        <div class="fw-semibold fs-7"><?php secho($doc["name"]); ?></div>
                        <div class="fs-9 text-muted"><?php echo fdate($doc["created_at"]); ?></div>
                    </div>
                    <a href="<?php echo $doc["url"]; ?>" target="_blank" class="btn btn-sm btn-icon btn-icon-primary">
                        <i class="ki-outline ki-eye fs-6"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>