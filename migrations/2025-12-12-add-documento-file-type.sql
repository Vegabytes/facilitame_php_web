-- Migración: Añadir tipo de archivo 'Documento'
-- Fecha: 2025-12-12
-- Descripción: Añade la opción 'Documento' a los tipos de archivo disponibles para subir

INSERT INTO file_types (id, name, created_at, updated_at)
VALUES (4, 'Documento', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Documento', updated_at = NOW();
