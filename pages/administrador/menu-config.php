<?php
$currentPage = 'menu-config';
$pageTitle = 'Configuración de Menús';
?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center bg-light-primary rounded border-primary border border-dashed p-4 mb-6">
            <div class="d-flex align-items-center">
                <i class="ki-outline ki-information-5 fs-2tx me-4 text-facilitame"></i>
                <div class="d-flex flex-column">
                    <span class="fw-bold fs-6">Instrucciones</span>
                    <span class="text-gray-700">Arrastra los items para reordenarlos. Usa el switch para mostrar/ocultar.</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary-facilitame" id="btn-save">
                <i class="ki-outline ki-check me-1 text-white"></i>
                Guardar cambios
            </button>
        </div>

        <div id="menu-items-container">
            <div class="text-center py-10">
                <div class="spinner-border spinner-facilitame" role="status"></div>
                <p class="mt-3 text-muted">Cargando configuración...</p>
            </div>
        </div>
    </div>
</div>

<!-- Estilos en bold.css: .menu-item-card, .menu-config-section-header -->

<script>
(function() {
    'use strict';

    const API_URL = '/api/menu-config';
    let currentRole = 'cliente';
    let menuItems = [];
    let draggedItem = null;

    const container = document.getElementById('menu-items-container');
    const roleSelector = document.getElementById('role-selector');
    const btnSave = document.getElementById('btn-save');
    const btnReset = document.getElementById('btn-reset');

    function init() {
        roleSelector.addEventListener('change', (e) => {
            currentRole = e.target.value;
            loadConfig();
        });

        btnSave.addEventListener('click', saveConfig);
        btnReset.addEventListener('click', loadConfig);

        loadConfig();
    }

    async function loadConfig() {
        container.innerHTML = `
            <div class="text-center py-10">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Cargando configuración...</p>
            </div>`;

        try {
            const response = await fetch(`${API_URL}?role=${currentRole}`);
            const result = await response.json();

            if (result.status === 'ok') {
                menuItems = result.data.items;
                renderItems();
            } else {
                showError(result.message);
            }
        } catch (e) {
            console.error(e);
            showError('Error de conexión');
        }
    }

    function renderItems() {
        // Agrupar por sección
        const sections = {};
        menuItems.forEach(item => {
            if (!sections[item.section]) {
                sections[item.section] = [];
            }
            sections[item.section].push(item);
        });

        let html = '';

        Object.keys(sections).forEach(section => {
            html += `<div class="menu-config-section-header">${escapeHtml(section)}</div>`;

            sections[section].forEach(item => {
                const disabledClass = item.is_visible ? '' : 'disabled-item';
                // Añadir prefijo ki-outline si no lo tiene
                const iconClass = item.icon.startsWith('ki-outline') ? item.icon : `ki-outline ${item.icon}`;
                html += `
                    <div class="menu-item-card ${disabledClass}" data-key="${item.key}" draggable="true">
                        <div class="drag-handle">
                            <i class="ki-outline ki-dots-vertical fs-3"></i>
                        </div>
                        <div class="item-icon">
                            <i class="${iconClass}"></i>
                        </div>
                        <div class="item-info">
                            <div class="item-label">${escapeHtml(item.label)}</div>
                            <div class="item-section">${escapeHtml(item.section)}</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="toggle-${item.key}" ${item.is_visible ? 'checked' : ''}>
                        </div>
                    </div>`;
            });
        });

        container.innerHTML = html;

        // Añadir eventos
        container.querySelectorAll('.menu-item-card').forEach(card => {
            // Drag events
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            card.addEventListener('dragover', handleDragOver);
            card.addEventListener('drop', handleDrop);

            // Toggle visibility
            const toggle = card.querySelector('input[type="checkbox"]');
            toggle.addEventListener('change', (e) => {
                const key = card.dataset.key;
                const item = menuItems.find(i => i.key === key);
                if (item) {
                    item.is_visible = e.target.checked;
                    card.classList.toggle('disabled-item', !e.target.checked);
                }
            });
        });
    }

    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        draggedItem = null;
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDrop(e) {
        e.preventDefault();

        if (draggedItem === this) return;

        const cards = Array.from(container.querySelectorAll('.menu-item-card'));
        const fromIndex = cards.indexOf(draggedItem);
        const toIndex = cards.indexOf(this);

        // Reordenar en el array
        const draggedKey = draggedItem.dataset.key;
        const draggedItemData = menuItems.find(i => i.key === draggedKey);

        menuItems = menuItems.filter(i => i.key !== draggedKey);

        const targetKey = this.dataset.key;
        const targetIndex = menuItems.findIndex(i => i.key === targetKey);

        if (fromIndex < toIndex) {
            menuItems.splice(targetIndex + 1, 0, draggedItemData);
        } else {
            menuItems.splice(targetIndex, 0, draggedItemData);
        }

        // Re-renderizar
        renderItems();
    }

    async function saveConfig() {
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    role: currentRole,
                    items: menuItems
                })
            });

            const result = await response.json();

            if (result.status === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'La configuración del menú se ha actualizado',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión'
            });
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="ki-outline ki-check me-1"></i>Guardar cambios';
        }
    }

    function showError(message) {
        container.innerHTML = `
            <div class="text-center py-10">
                <i class="ki-outline ki-cross-circle fs-3x text-danger"></i>
                <p class="mt-3 text-muted">${escapeHtml(message)}</p>
                <button class="btn btn-sm btn-light-primary mt-3" onclick="location.reload()">
                    Reintentar
                </button>
            </div>`;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
