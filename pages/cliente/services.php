<?php
// Pantalla de servicios - Estilo limpio
$scripts = [];
?>

<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content">
        <div class="container-fluid px-4 py-4">
            
            <div class="services-header mb-4">
                <h1 class="services-title">¿Qué necesitas hoy?</h1>
                <p class="services-subtitle">Selecciona un servicio y te ayudamos</p>
            </div>
            
            <div class="services-grid" id="services-grid">
                <?php foreach ($services as $service) : ?>
                    <?php
                    $hasSubcategories = !empty($service["subcategories"]);
                    $phone = (empty($service["phone"]) || guest()) ? "" : "tel:" . $service["phone"];
                    ?>
                    
                    <div class="service-card <?php echo $hasSubcategories ? 'has-options' : 'direct-service' ?>"
                         data-service-id="<?php secho($service["id"]) ?>"
                         data-phone="<?php echo $phone ?>">
                        
                        <div class="service-card-inner">
                            <div class="service-icon">
                                <img src="<?php echo MEDIA_DIR . "/" . $service["img"] ?>" 
                                     alt="<?php secho($service["name"]) ?>">
                            </div>
                            <span class="service-name"><?php secho($service["name"]) ?></span>
                            <?php if ($hasSubcategories) : ?>
                                <i class="ki-outline ki-down service-arrow"></i>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($hasSubcategories) : ?>
                        <div class="service-dropdown">
                            <?php foreach ($service["subcategories"] as $sub) : ?>
                                <?php $phone_sub = (empty($sub["phone"]) || guest()) ? "" : "tel:" . $sub["phone"]; ?>
                                <a href="#" class="dropdown-item" 
                                   data-service-id="<?php secho($sub["id"]) ?>"
                                   data-phone="<?php echo $phone_sub ?>">
                                    <?php secho($sub["name"]) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
</div>

<div class="panel-overlay" id="panel-overlay"></div>

<div class="service-panel" id="service-panel">
    <div class="panel-header">
        <div class="panel-title">
            <img src="" alt="" id="panel-icon" class="panel-icon">
            <span id="panel-name">Servicio</span>
        </div>
        <button type="button" class="panel-close" id="panel-close">
            <i class="ki-outline ki-cross"></i>
        </button>
    </div>
    
    <div class="panel-body">
        <form id="service-form" method="POST">
            <input type="hidden" id="service-id" name="service_id">
            
            <a href="" class="call-banner" id="call-banner">
                <i class="ki-outline ki-phone"></i>
                <div>
                    <strong>¿Prefieres llamar?</strong>
                    <span>Te atendemos al momento</span>
                </div>
            </a>
            
            <div id="form-fields">
                <div class="field-skeleton"></div>
                <div class="field-skeleton"></div>
                <div class="field-skeleton short"></div>
            </div>
            
            <div class="upload-section">
                <label class="upload-label">Adjuntar documentos <span>(opcional)</span></label>
                <div class="upload-box" id="upload-box">
                    <input type="file" id="file-input" accept="image/*,application/pdf,.docx" multiple>
                    <i class="ki-outline ki-cloud-add"></i>
                    <span>Arrastra o haz clic para seleccionar</span>
                    <small>Puedes subir varios archivos</small>
                </div>
                <div id="file-list"></div>
            </div>
        </form>
    </div>
    
    <div class="panel-footer">
        <button type="button" class="btn-secondary" id="btn-cancel">Cancelar</button>
        <button type="submit" form="service-form" class="btn-primary" id="btn-submit">
            <span class="btn-text">Enviar Solicitud</span>
            <span class="btn-loading"><span class="spinner"></span> Enviando...</span>
        </button>
    </div>
</div>

<style>
/* Header */
.services-header{text-align:center;padding:1rem 0 .5rem}
.services-title{font-size:1.5rem;font-weight:700;color:var(--f-text-dark);margin:0 0 .25rem}
.services-subtitle{font-size:.875rem;color:var(--f-text-medium);margin:0}

/* Grid */
.services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:1rem}
@media(min-width:640px){.services-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1.25rem}}
@media(min-width:1024px){.services-grid{grid-template-columns:repeat(6,1fr)}}
@media(min-width:1400px){.services-grid{grid-template-columns:repeat(8,1fr)}}

/* Service Card */
.service-card{position:relative}
.service-card-inner{background:#fff;border:1px solid var(--f-border);border-radius:var(--f-radius);padding:1.25rem .75rem 1rem;text-align:center;cursor:pointer;transition:all .2s ease;display:flex;flex-direction:column;align-items:center;gap:.625rem}
.service-card-inner:hover{border-color:var(--f-primary);box-shadow:0 4px 12px rgba(0,194,203,.15);transform:translateY(-2px)}
.service-card.active .service-card-inner{border-color:var(--f-primary);background:rgba(0,194,203,.05)}
.service-icon{width:56px;height:56px;border-radius:8px;overflow:hidden}
.service-icon img{width:100%;height:100%;object-fit:cover}
.service-name{font-size:.75rem;font-weight:600;color:var(--f-text-dark);line-height:1.3}
.service-arrow{font-size:.5rem;color:var(--f-text-light);transition:transform .2s}
.service-card.open .service-arrow{transform:rotate(180deg)}

/* Indicador de subcategorías */
.service-card.has-options .service-card-inner::after{content:'';position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--f-primary);border-radius:50%;opacity:.7}
.service-card.has-options .service-card-inner{position:relative}

/* Dropdown */
.service-dropdown{position:absolute;top:calc(100% + 6px);left:50%;transform:translateX(-50%) scale(.95);background:#fff;border:1px solid var(--f-border);border-radius:var(--f-radius);box-shadow:0 10px 40px rgba(0,0,0,.15);min-width:200px;max-width:260px;z-index:100;opacity:0;visibility:hidden;transition:all .15s ease;padding:.5rem}
.service-card.open .service-dropdown{opacity:1;visibility:visible;transform:translateX(-50%) scale(1)}
.service-card.open{z-index:50}
.dropdown-item{display:block;padding:.625rem .875rem;font-size:.8125rem;color:var(--f-text-dark);text-decoration:none;border-radius:var(--f-radius-sm);transition:background .15s}
.dropdown-item:hover{background:rgba(0,194,203,.1);color:var(--f-primary-dark)}

/* Panel */
.panel-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;opacity:0;visibility:hidden;transition:all .25s}
.panel-overlay.open{opacity:1;visibility:visible}
.service-panel{position:fixed;top:0;right:0;width:100%;max-width:440px;height:100vh;background:#fff;z-index:1001;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .3s ease}
.service-panel.open{transform:translateX(0)}

.panel-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--f-border);background:var(--f-bg-light)}
.panel-title{display:flex;align-items:center;gap:.75rem}
.panel-icon{width:40px;height:40px;border-radius:8px;object-fit:cover}
.panel-title span{font-size:1rem;font-weight:600;color:var(--f-text-dark)}
.panel-close{width:36px;height:36px;border:none;background:#fff;border-radius:var(--f-radius-sm);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s}
.panel-close:hover{background:var(--f-border)}

.panel-body{flex:1;overflow-y:auto;padding:1.25rem}

/* Call Banner */
.call-banner{display:flex;align-items:center;gap:.75rem;background:#fef3c7;border:1px solid #fde68a;border-radius:var(--f-radius);padding:.875rem 1rem;margin-bottom:1.5rem;text-decoration:none;transition:background .15s}
.call-banner:hover{background:#fde68a}
.call-banner i{font-size:1.25rem;color:#d97706}
.call-banner div{display:flex;flex-direction:column}
.call-banner strong{font-size:.8125rem;color:#92400e}
.call-banner span{font-size:.75rem;color:#a16207}

/* Form */
.form-field{width:100%;padding:.75rem 1rem;font-size:.875rem;border:1px solid var(--f-border);border-radius:var(--f-radius-sm);background:#fff;color:var(--f-text-dark);transition:border-color .15s;margin-bottom:1rem;appearance:none;-webkit-appearance:none}
.form-field:focus{outline:none;border-color:var(--f-primary)}
select.form-field{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 1rem center;padding-right:2.5rem}
textarea.form-field{min-height:80px;resize:vertical}
.field-label{display:block;font-size:.8125rem;font-weight:500;color:var(--f-text-dark);margin-bottom:.375rem}
.field-group{margin-bottom:1rem}

/* Skeleton */
.field-skeleton{height:44px;background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 50%,#f3f4f6 75%);background-size:200% 100%;border-radius:var(--f-radius-sm);margin-bottom:1rem;animation:shimmer 1.2s infinite}
.field-skeleton.short{width:60%}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* Upload */
.upload-section{margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--f-border)}
.upload-label{display:block;font-size:.8125rem;font-weight:500;color:var(--f-text-dark);margin-bottom:.5rem}
.upload-label span{color:var(--f-text-light);font-weight:400}
.upload-box{position:relative;border:2px dashed var(--f-border);border-radius:var(--f-radius);padding:1.5rem;text-align:center;cursor:pointer;transition:all .15s;margin-bottom:.75rem}
.upload-box:hover{border-color:var(--f-primary);background:rgba(0,194,203,.05)}
.upload-box input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer}
.upload-box i{font-size:1.5rem;color:var(--f-text-light);display:block;margin-bottom:.25rem}
.upload-box span{font-size:.8125rem;color:var(--f-text-medium);display:block}
.upload-box small{font-size:.75rem;color:var(--f-text-light);display:block;margin-top:.25rem}

/* File list */
#file-list{display:flex;flex-direction:column;gap:.5rem}
.file-item{display:flex;align-items:center;gap:.75rem;background:var(--f-bg-light);border:1px solid var(--f-border);border-radius:var(--f-radius-sm);padding:.625rem .875rem}
.file-item-icon{width:32px;height:32px;background:var(--f-primary);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.file-item-icon i{color:#fff;font-size:1rem}
.file-item-info{flex:1;min-width:0}
.file-item-name{font-size:.8125rem;font-weight:500;color:var(--f-text-dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.file-item-meta{display:flex;align-items:center;gap:.5rem;margin-top:.25rem}
.file-item-size{font-size:.75rem;color:var(--f-text-light)}
.file-item-type{flex:1}
.file-item-type select{width:100%;padding:.375rem .5rem;font-size:.75rem;border:1px solid var(--f-border);border-radius:4px;background:#fff}
.file-item-remove{width:28px;height:28px;border:none;background:none;color:var(--f-text-light);cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:4px;transition:all .15s;flex-shrink:0}
.file-item-remove:hover{background:#fee2e2;color:var(--f-danger)}

/* Footer */
.panel-footer{display:flex;gap:.75rem;padding:1rem 1.25rem;border-top:1px solid var(--f-border);background:var(--f-bg-light)}
.panel-footer .btn-secondary,.panel-footer .btn-primary{flex:1;padding:.75rem 1rem;font-size:.875rem;font-weight:600;border-radius:var(--f-radius-sm);cursor:pointer;transition:all .15s}
.panel-footer .btn-secondary{background:#fff;border:1px solid var(--f-border);color:var(--f-text-medium)}
.panel-footer .btn-secondary:hover{background:var(--f-bg-light);color:var(--f-text-dark)}
.panel-footer .btn-primary{background:var(--f-primary);border:none;color:#fff}
.panel-footer .btn-primary:hover{background:var(--f-primary-dark)}
.panel-footer .btn-primary:disabled{opacity:.6;cursor:not-allowed}
.btn-text,.btn-loading{display:inline-flex;align-items:center;justify-content:center;gap:.5rem}
.btn-loading{display:none}
.panel-footer .btn-primary.loading .btn-text{display:none}
.panel-footer .btn-primary.loading .btn-loading{display:inline-flex}
.spinner{width:14px;height:14px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Responsive */
@media(max-width:640px){
    .services-header{padding:.5rem 0}
    .services-title{font-size:1.25rem}
    .service-card-inner{padding:1rem .5rem .75rem}
    .service-icon{width:48px;height:48px}
    .service-name{font-size:.6875rem}
    .service-panel{max-width:100%}
    .panel-footer{flex-direction:column}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaDir = '<?php echo MEDIA_DIR; ?>';
    const panel = document.getElementById('service-panel');
    const overlay = document.getElementById('panel-overlay');
    const fileTypes = <?php echo json_encode($file_types); ?>;
    let openCard = null;
    let uploadedFiles = []; // Array para gestionar archivos
    
    // Dropdowns
    document.querySelectorAll('.service-card.has-options .service-card-inner').forEach(inner => {
        inner.addEventListener('click', e => {
            e.stopPropagation();
            const card = inner.closest('.service-card');
            if (openCard && openCard !== card) openCard.classList.remove('open');
            card.classList.toggle('open');
            openCard = card.classList.contains('open') ? card : null;
        });
    });
    
    document.querySelectorAll('.service-card.direct-service .service-card-inner').forEach(inner => {
        inner.addEventListener('click', () => {
            const card = inner.closest('.service-card');
            selectService(card.dataset.serviceId, card.dataset.phone, card);
        });
    });
    
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            const card = item.closest('.service-card');
            closeDropdowns();
            selectService(item.dataset.serviceId, item.dataset.phone, card);
        });
    });
    
    document.addEventListener('click', closeDropdowns);
    
    function closeDropdowns() {
        document.querySelectorAll('.service-card.open').forEach(c => c.classList.remove('open'));
        openCard = null;
    }
    
    // Panel
    function selectService(serviceId, phone, card) {
        document.querySelectorAll('.service-card').forEach(c => c.classList.remove('active'));
        if (card) card.classList.add('active');
        openPanel();
        loadForm(serviceId, phone);
    }
    
    function openPanel() {
        panel.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    
    function closePanel() {
        panel.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        document.querySelectorAll('.service-card').forEach(c => c.classList.remove('active'));
        resetForm();
    }
    
    function resetForm() {
        document.getElementById('form-fields').innerHTML = '<div class="field-skeleton"></div><div class="field-skeleton"></div><div class="field-skeleton short"></div>';
        document.getElementById('file-list').innerHTML = '';
        document.getElementById('file-input').value = '';
        uploadedFiles = [];
    }
    
    document.getElementById('panel-close').addEventListener('click', closePanel);
    document.getElementById('btn-cancel').addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closePanel(); closeDropdowns(); } });
    
    // Load Form
    async function loadForm(serviceId, phone) {
        try {
            const res = await fetch('api/service-get-form', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'service_id=' + serviceId
            });
            let data = await res.json();
            if (typeof data === 'string') data = JSON.parse(data);
            
            if (data.status === 'ok') {
                const info = data.data.service_info;
                const fields = data.data.form_elements;
                
                document.getElementById('panel-name').textContent = info.name;
                document.getElementById('service-id').value = serviceId;
                if (info.img) document.getElementById('panel-icon').src = mediaDir + '/' + info.img;
                
                const callBanner = document.getElementById('call-banner');
                if (phone || info.phone) {
                    callBanner.href = phone || 'tel:' + info.phone;
                    callBanner.style.display = 'flex';
                } else {
                    callBanner.style.display = 'none';
                }
                
                setTimeout(() => { document.getElementById('form-fields').innerHTML = buildFields(fields); }, 100);
            }
        } catch (err) { console.error(err); }
    }
    
    function buildFields(fields) {
        return fields.map(f => {
            const req = f.required == 1 ? 'required' : '';
            const name = esc(f.name);
            const key = esc(f.key);
            
            switch (f.type) {
                case 'select':
                    let opts = f.values;
                    if (typeof opts === 'string') { try { opts = JSON.parse(opts); } catch(e) { opts = []; } }
                    return `<div class="field-group"><label class="field-label">${name}</label><select ${req} name="${name}" data-key="${key}" class="form-field"><option value="">Selecciona...</option>${opts.map(o => `<option value="${esc(o)}">${esc(o)}</option>`).join('')}</select></div>`;
                case 'textarea':
                    return `<div class="field-group"><label class="field-label">${name}</label><textarea ${req} name="${name}" data-key="${key}" class="form-field" placeholder="${name}"></textarea></div>`;
                case 'number':
                    return `<div class="field-group"><label class="field-label">${name}</label><input type="number" ${req} name="${name}" data-key="${key}" class="form-field" placeholder="${name}"></div>`;
                case 'date':
                    return `<div class="field-group"><label class="field-label">${name}</label><input type="date" ${req} name="${name}" data-key="${key}" class="form-field"></div>`;
                default:
                    return `<div class="field-group"><label class="field-label">${name}</label><input type="text" ${req} name="${name}" data-key="${key}" class="form-field" placeholder="${name}"></div>`;
            }
        }).join('');
    }
    
    function esc(str) { if (!str) return ''; const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
    
    // Campo "Otro"
    document.addEventListener('change', e => {
        if (e.target.matches('#form-fields select.form-field')) {
            const sel = e.target;
            const group = sel.closest('.field-group');
            const next = group.nextElementSibling;
            
            if (sel.value.toLowerCase() === 'otro') {
                if (next && next.classList.contains('other-field')) return;
                const key = sel.dataset.key;
                group.insertAdjacentHTML('afterend', `<div class="field-group other-field"><input type="text" name="Especifica" data-key="${key}_other" class="form-field" placeholder="Por favor, especifica"></div>`);
            } else if (next && next.classList.contains('other-field')) {
                next.remove();
            }
        }
    });
    
    // ========== GESTIÓN DE ARCHIVOS MÚLTIPLES ==========
    const uploadBox = document.getElementById('upload-box');
    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');
    
    // Drag & drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
        uploadBox.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
    });
    ['dragenter', 'dragover'].forEach(ev => { 
        uploadBox.addEventListener(ev, () => uploadBox.style.borderColor = 'var(--f-primary)'); 
    });
    ['dragleave', 'drop'].forEach(ev => { 
        uploadBox.addEventListener(ev, () => uploadBox.style.borderColor = ''); 
    });
    
    uploadBox.addEventListener('drop', e => {
        addFiles(e.dataTransfer.files);
    });
    
    fileInput.addEventListener('change', () => {
        addFiles(fileInput.files);
        fileInput.value = ''; // Reset para permitir seleccionar el mismo archivo
    });
    
    function addFiles(files) {
        Array.from(files).forEach(file => {
            // Evitar duplicados por nombre
            if (uploadedFiles.some(f => f.name === file.name && f.size === file.size)) return;
            
            uploadedFiles.push({
                file: file,
                name: file.name,
                size: file.size,
                typeId: '' // Tipo de documento seleccionado
            });
        });
        renderFileList();
    }
    
    function removeFile(index) {
        uploadedFiles.splice(index, 1);
        renderFileList();
    }
    
    function updateFileType(index, typeId) {
        uploadedFiles[index].typeId = typeId;
    }
    
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'ki-picture';
        if (ext === 'pdf') return 'ki-document';
        if (['doc', 'docx'].includes(ext)) return 'ki-document';
        return 'ki-file';
    }
    
    function renderFileList() {
        if (uploadedFiles.length === 0) {
            fileList.innerHTML = '';
            return;
        }
        
        const typeOptions = fileTypes.map(ft => 
            `<option value="${ft.id}">${esc(ft.name)}</option>`
        ).join('');
        
        fileList.innerHTML = uploadedFiles.map((f, i) => `
            <div class="file-item" data-index="${i}">
                <div class="file-item-icon">
                    <i class="ki-outline ${getFileIcon(f.name)}"></i>
                </div>
                <div class="file-item-info">
                    <div class="file-item-name" title="${esc(f.name)}">${esc(f.name)}</div>
                    <div class="file-item-meta">
                        <span class="file-item-size">${formatFileSize(f.size)}</span>
                        <div class="file-item-type">
                            <select data-file-index="${i}" class="file-type-select">
                                <option value="">Tipo de documento</option>
                                ${typeOptions}
                            </select>
                        </div>
                    </div>
                </div>
                <button type="button" class="file-item-remove" data-index="${i}" title="Eliminar">
                    <i class="ki-outline ki-trash"></i>
                </button>
            </div>
        `).join('');
        
        // Event listeners para los nuevos elementos
        fileList.querySelectorAll('.file-item-remove').forEach(btn => {
            btn.addEventListener('click', () => removeFile(parseInt(btn.dataset.index)));
        });
        
        fileList.querySelectorAll('.file-type-select').forEach(sel => {
            sel.value = uploadedFiles[sel.dataset.fileIndex].typeId || '';
            sel.addEventListener('change', () => {
                updateFileType(parseInt(sel.dataset.fileIndex), sel.value);
            });
        });
    }
    // ========== FIN GESTIÓN DE ARCHIVOS ==========
    
    // Submit
    document.getElementById('service-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('btn-submit');
        
        if (!form.checkValidity()) { form.reportValidity(); return; }
        
        // Validar que todos los archivos tengan tipo seleccionado
        if (uploadedFiles.length > 0) {
            const missingType = uploadedFiles.some(f => !f.typeId);
            if (missingType) {
                FH.warning('Selecciona el tipo de documento para cada archivo');
                return;
            }
        }
        
        const formData = new FormData();
        const inputs = form.querySelectorAll('#form-fields .form-field');
        const values = [];
        
        inputs.forEach(inp => {
            values.push({ value: inp.value, name: inp.name, key: inp.dataset.key });
        });
        
        formData.append('category_id', document.getElementById('service-id').value);
        formData.append('form', JSON.stringify(values));
        
        // Añadir archivos con los nombres que espera el backend
        uploadedFiles.forEach(f => {
            formData.append('documents[]', f.file);
            formData.append('file_type_ids[]', f.typeId);
        });
        
        btn.disabled = true;
        btn.classList.add('loading');
        
        try {
            const res = await fetch('api/services-form-main-submit', { method: 'POST', body: formData });
            let data = await res.json();
            if (typeof data === 'string') data = JSON.parse(data);
            
            if (data.status === 'ok') {
                FH.success(data.message_plain || 'Solicitud enviada correctamente');
                setTimeout(() => location.href = 'my-services', 2000);
            } else {
                FH.warning(data.message_plain || 'No se pudo procesar la solicitud');
            }
        } catch (err) {
            FH.error('Ha ocurrido un error');
        } finally {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
    });
    
    // Lead de llamada
    document.getElementById('call-banner').addEventListener('click', async function() {
        const inputs = document.querySelectorAll('#form-fields .form-field');
        const values = [];
        inputs.forEach(inp => values.push({ value: inp.value, name: inp.name, key: inp.dataset.key }));
        const fd = new FormData();
        fd.append('category_id', document.getElementById('service-id').value);
        fd.append('form', JSON.stringify(values));
        try { await fetch('api/make-call-lead', { method: 'POST', body: fd }); } catch(e) {}
    });
});
</script>