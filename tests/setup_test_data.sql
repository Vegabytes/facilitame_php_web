-- =====================================================
-- FACILITAME - Datos de Prueba para Tests
-- =====================================================
-- Ejecutar antes de los tests para tener datos de prueba
-- mysql -u root -p facilitame < tests/setup_test_data.sql
-- =====================================================

-- Configuración de variables
SET @test_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; -- password: 'password'
SET @test_password_simple = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm'; -- password: 'test123'

-- =====================================================
-- 1. ROLES (si no existen)
-- =====================================================
INSERT IGNORE INTO roles (id, name, guard_name, created_at, updated_at) VALUES
(1, 'administrador', 'web', NOW(), NOW()),
(2, 'comercial', 'web', NOW(), NOW()),
(3, 'proveedor', 'web', NOW(), NOW()),
(4, 'cliente', 'web', NOW(), NOW()),
(5, 'asesoria', 'web', NOW(), NOW());

-- =====================================================
-- 2. USUARIOS DE PRUEBA
-- =====================================================

-- Limpiar usuarios de prueba existentes
DELETE FROM users WHERE email LIKE '%@test.com';

-- Usuario Admin
INSERT INTO users (name, email, password, phone, created_at, updated_at) VALUES
('Admin Test', 'admin@test.com', @test_password_simple, '600000001', NOW(), NOW());
SET @admin_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (1, 'App\\Models\\User', @admin_id);

-- Usuario Comercial
INSERT INTO users (name, email, password, phone, created_at, updated_at) VALUES
('Comercial Test', 'comercial@test.com', @test_password_simple, '600000002', NOW(), NOW());
SET @comercial_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (2, 'App\\Models\\User', @comercial_id);

-- Usuario Proveedor
INSERT INTO users (name, email, password, phone, created_at, updated_at) VALUES
('Proveedor Test', 'proveedor@test.com', @test_password_simple, '600000003', NOW(), NOW());
SET @proveedor_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (3, 'App\\Models\\User', @proveedor_id);

-- Usuario Asesoría
INSERT INTO users (name, email, password, phone, created_at, updated_at) VALUES
('Asesoria Test', 'asesoria@test.com', @test_password_simple, '600000004', NOW(), NOW());
SET @asesoria_user_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (5, 'App\\Models\\User', @asesoria_user_id);

-- Usuario Cliente (vinculado a asesoría)
INSERT INTO users (name, email, password, phone, client_type, created_at, updated_at) VALUES
('Cliente Test', 'cliente@test.com', @test_password_simple, '600000005', 'autonomo', NOW(), NOW());
SET @cliente_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (4, 'App\\Models\\User', @cliente_id);

-- Cliente 2 (empresa)
INSERT INTO users (name, email, password, phone, client_type, created_at, updated_at) VALUES
('Cliente Empresa Test', 'cliente2@test.com', @test_password_simple, '600000006', 'empresa', NOW(), NOW());
SET @cliente2_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (4, 'App\\Models\\User', @cliente2_id);

-- Cliente 3 (sin asesoría)
INSERT INTO users (name, email, password, phone, client_type, created_at, updated_at) VALUES
('Cliente Sin Asesoria', 'cliente3@test.com', @test_password_simple, '600000007', 'autonomo', NOW(), NOW());
SET @cliente3_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (4, 'App\\Models\\User', @cliente3_id);

-- =====================================================
-- 3. ASESORÍA DE PRUEBA
-- =====================================================

-- Limpiar asesorías de prueba
DELETE FROM advisories WHERE cif LIKE 'TEST%';

-- Crear asesoría
INSERT INTO advisories (user_id, razon_social, cif, direccion, telefono, codigo_unico, plan, created_at, updated_at) VALUES
(@asesoria_user_id, 'Asesoría Test S.L.', 'TESTB12345678', 'Calle Test 123, Madrid', '910000001', 'ASE-TEST001', 'basic', NOW(), NOW());
SET @asesoria_id = LAST_INSERT_ID();

-- Segunda asesoría (plan gratuito)
INSERT INTO users (name, email, password, phone, created_at, updated_at) VALUES
('Asesoria Gratuita', 'asesoria2@test.com', @test_password_simple, '600000008', NOW(), NOW());
SET @asesoria2_user_id = LAST_INSERT_ID();
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (5, 'App\\Models\\User', @asesoria2_user_id);

INSERT INTO advisories (user_id, razon_social, cif, direccion, telefono, codigo_unico, plan, created_at, updated_at) VALUES
(@asesoria2_user_id, 'Asesoría Gratuita Test', 'TESTB87654321', 'Calle Gratis 456, Barcelona', '930000001', 'ASE-TEST002', 'gratuito', NOW(), NOW());
SET @asesoria2_id = LAST_INSERT_ID();

-- =====================================================
-- 4. VINCULAR CLIENTES A ASESORÍA
-- =====================================================

-- Limpiar vínculos de prueba
DELETE FROM customers_advisories WHERE advisory_id IN (@asesoria_id, @asesoria2_id);

-- Vincular clientes
INSERT INTO customers_advisories (customer_id, advisory_id, created_at) VALUES
(@cliente_id, @asesoria_id, NOW()),
(@cliente2_id, @asesoria_id, NOW());

-- =====================================================
-- 5. CATEGORÍAS DE PRUEBA (para proveedor)
-- =====================================================

-- Asegurar que existen categorías
INSERT IGNORE INTO categories (id, name, parent_id, created_at, updated_at) VALUES
(1, 'Servicios Generales', NULL, NOW(), NOW()),
(2, 'Limpieza', 1, NOW(), NOW()),
(3, 'Reparaciones', 1, NOW(), NOW());

-- Vincular proveedor a categorías
DELETE FROM provider_categories WHERE provider_id = @proveedor_id;
INSERT INTO provider_categories (provider_id, category_id, created_at) VALUES
(@proveedor_id, 2, NOW()),
(@proveedor_id, 3, NOW());

-- =====================================================
-- 6. CITAS DE PRUEBA (diferentes estados)
-- =====================================================

-- Limpiar citas de prueba
DELETE FROM advisory_appointments WHERE advisory_id = @asesoria_id;

-- Cita solicitada
INSERT INTO advisory_appointments (advisory_id, customer_id, type, department, preferred_time, reason, status, created_at, updated_at) VALUES
(@asesoria_id, @cliente_id, 'llamada', 'contabilidad', 'mañana', 'Consulta sobre IVA trimestral', 'solicitado', NOW(), NOW());

-- Cita agendada
INSERT INTO advisory_appointments (advisory_id, customer_id, type, department, preferred_time, reason, status, scheduled_date, scheduled_time, created_at, updated_at) VALUES
(@asesoria_id, @cliente_id, 'reunion_virtual', 'fiscalidad', 'tarde', 'Planificación fiscal anual', 'agendado', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '16:00:00', NOW(), NOW());

-- Cita finalizada
INSERT INTO advisory_appointments (advisory_id, customer_id, type, department, preferred_time, reason, status, scheduled_date, scheduled_time, notes, created_at, updated_at) VALUES
(@asesoria_id, @cliente_id, 'reunion_presencial', 'laboral', 'específico', 'Contratación de empleado', 'finalizado', DATE_SUB(CURDATE(), INTERVAL 7 DAY), '10:00:00', 'Se revisaron los documentos necesarios', DATE_SUB(NOW(), INTERVAL 7 DAY), NOW());

-- Cita cancelada
INSERT INTO advisory_appointments (advisory_id, customer_id, type, department, preferred_time, reason, status, created_at, updated_at) VALUES
(@asesoria_id, @cliente2_id, 'llamada', 'gestion', 'mañana', 'Consulta cancelada', 'cancelado', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW());

-- Cita de otro cliente
INSERT INTO advisory_appointments (advisory_id, customer_id, type, department, preferred_time, reason, status, created_at, updated_at) VALUES
(@asesoria_id, @cliente2_id, 'reunion_virtual', 'contabilidad', 'tarde', 'Revisión mensual', 'solicitado', NOW(), NOW());

-- =====================================================
-- 7. FACTURAS DE PRUEBA
-- =====================================================

-- Limpiar facturas de prueba
DELETE FROM advisory_invoices WHERE advisory_id = @asesoria_id;

-- Factura pendiente
INSERT INTO advisory_invoices (advisory_id, customer_id, type, label, filename, url, notes, is_processed, created_at, updated_at) VALUES
(@asesoria_id, @cliente_id, 'gasto', 'restaurante', 'factura_test_001.pdf', 'advisory_invoices/factura_test_001.pdf', 'Comida de trabajo', 0, NOW(), NOW());

-- Factura procesada
INSERT INTO advisory_invoices (advisory_id, customer_id, type, label, filename, url, notes, is_processed, processed_at, created_at, updated_at) VALUES
(@asesoria_id, @cliente_id, 'gasto', 'gasolina', 'factura_test_002.pdf', 'advisory_invoices/factura_test_002.pdf', 'Combustible mes octubre', 1, NOW(), DATE_SUB(NOW(), INTERVAL 15 DAY), NOW());

-- Factura de ingreso
INSERT INTO advisory_invoices (advisory_id, customer_id, type, label, filename, url, notes, is_processed, created_at, updated_at) VALUES
(@asesoria_id, @cliente_id, 'ingreso', 'servicios', 'factura_test_003.pdf', 'advisory_invoices/factura_test_003.pdf', 'Factura a cliente', 0, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW());

-- Facturas de otro cliente
INSERT INTO advisory_invoices (advisory_id, customer_id, type, label, filename, url, is_processed, created_at, updated_at) VALUES
(@asesoria_id, @cliente2_id, 'gasto', 'proveedores', 'factura_test_004.pdf', 'advisory_invoices/factura_test_004.pdf', 0, NOW(), NOW()),
(@asesoria_id, @cliente2_id, 'gasto', 'material', 'factura_test_005.pdf', 'advisory_invoices/factura_test_005.pdf', 1, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW());

-- =====================================================
-- 8. COMUNICACIONES DE PRUEBA
-- =====================================================

-- Limpiar comunicaciones de prueba
DELETE FROM advisory_communication_recipients WHERE communication_id IN (SELECT id FROM advisory_communications WHERE advisory_id = @asesoria_id);
DELETE FROM advisory_communication_files WHERE communication_id IN (SELECT id FROM advisory_communications WHERE advisory_id = @asesoria_id);
DELETE FROM advisory_communications WHERE advisory_id = @asesoria_id;

-- Comunicación importante
INSERT INTO advisory_communications (advisory_id, subject, message, importance, recipient_filter, created_at) VALUES
(@asesoria_id, 'URGENTE: Fecha límite declaración IVA', 'Recordamos que el próximo día 20 finaliza el plazo para presentar la declaración trimestral del IVA. Por favor, asegúrese de tener toda la documentación preparada.', 'importante', 'todos', NOW());
SET @comm1_id = LAST_INSERT_ID();

-- Añadir destinatarios
INSERT INTO advisory_communication_recipients (communication_id, customer_id, is_read, created_at) VALUES
(@comm1_id, @cliente_id, 1, NOW()),
(@comm1_id, @cliente2_id, 0, NOW());

-- Comunicación normal
INSERT INTO advisory_communications (advisory_id, subject, message, importance, recipient_filter, created_at) VALUES
(@asesoria_id, 'Nuevos horarios de atención', 'Les informamos que a partir del próximo mes nuestro horario de atención será de 9:00 a 18:00.', 'media', 'todos', DATE_SUB(NOW(), INTERVAL 7 DAY));
SET @comm2_id = LAST_INSERT_ID();

INSERT INTO advisory_communication_recipients (communication_id, customer_id, is_read, read_at, created_at) VALUES
(@comm2_id, @cliente_id, 1, NOW(), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(@comm2_id, @cliente2_id, 1, NOW(), DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Comunicación solo para autónomos
INSERT INTO advisory_communications (advisory_id, subject, message, importance, recipient_filter, created_at) VALUES
(@asesoria_id, 'Información para autónomos', 'Nueva cuota de autónomos para el próximo año.', 'leve', 'autonomos', DATE_SUB(NOW(), INTERVAL 14 DAY));
SET @comm3_id = LAST_INSERT_ID();

INSERT INTO advisory_communication_recipients (communication_id, customer_id, is_read, created_at) VALUES
(@comm3_id, @cliente_id, 0, DATE_SUB(NOW(), INTERVAL 14 DAY));

-- =====================================================
-- 9. MENSAJES DE CHAT DE PRUEBA
-- =====================================================

-- Limpiar mensajes de prueba
DELETE FROM advisory_messages WHERE advisory_id = @asesoria_id;

-- Conversación entre asesoría y cliente
INSERT INTO advisory_messages (advisory_id, customer_id, sender_type, content, is_read, created_at) VALUES
(@asesoria_id, @cliente_id, 'customer', 'Hola, tengo una duda sobre mi última factura', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@asesoria_id, @cliente_id, 'advisory', 'Buenos días, ¿qué duda tiene exactamente?', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@asesoria_id, @cliente_id, 'customer', 'No entiendo el concepto de retención', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@asesoria_id, @cliente_id, 'advisory', 'La retención es un pago adelantado del IRPF. Le explico...', 0, NOW());

-- Conversación con otro cliente
INSERT INTO advisory_messages (advisory_id, customer_id, sender_type, content, is_read, created_at) VALUES
(@asesoria_id, @cliente2_id, 'customer', '¿Cuándo tengo que presentar el modelo 303?', 0, NOW());

-- =====================================================
-- 10. NOTIFICACIONES DE PRUEBA
-- =====================================================

-- Limpiar notificaciones de prueba
DELETE FROM notifications WHERE sender_id IN (@asesoria_user_id, @cliente_id, @cliente2_id) OR receiver_id IN (@asesoria_user_id, @cliente_id, @cliente2_id);

-- Notificaciones para asesoría
INSERT INTO notifications (sender_id, receiver_id, request_id, title, description, status, created_at) VALUES
(@cliente_id, @asesoria_user_id, NULL, 'Nueva solicitud de cita', 'El cliente Cliente Test ha solicitado una cita', 0, NOW()),
(@cliente2_id, @asesoria_user_id, NULL, 'Nueva factura recibida', 'Cliente Empresa Test ha enviado una nueva factura', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@cliente_id, @asesoria_user_id, NULL, 'Mensaje de chat', 'Nuevo mensaje de Cliente Test', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Notificaciones para cliente
INSERT INTO notifications (sender_id, receiver_id, request_id, title, description, status, created_at) VALUES
(@asesoria_user_id, @cliente_id, NULL, 'Cita agendada', 'Su cita ha sido agendada para el próximo lunes', 0, NOW()),
(@asesoria_user_id, @cliente_id, NULL, 'Nueva comunicación', 'Ha recibido una nueva comunicación de su asesoría', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- =====================================================
-- 11. CÓDIGO DE VENTAS (para comercial)
-- =====================================================

-- Limpiar códigos de prueba
DELETE FROM sales_codes WHERE code LIKE 'TEST%';

-- Crear código de ventas
INSERT INTO sales_codes (user_id, code, description, created_at, updated_at) VALUES
(@comercial_id, 'TEST-COM-001', 'Código de prueba para comercial', NOW(), NOW());
SET @sales_code_id = LAST_INSERT_ID();

-- Vincular cliente a código de ventas
DELETE FROM customers_sales_codes WHERE sales_code_id = @sales_code_id;
INSERT INTO customers_sales_codes (customer_id, sales_code_id, created_at) VALUES
(@cliente_id, @sales_code_id, NOW());

-- =====================================================
-- 12. SOLICITUDES DE PRUEBA (para tests generales)
-- =====================================================

-- Limpiar solicitudes de prueba
DELETE FROM requests WHERE user_id IN (@cliente_id, @cliente2_id, @cliente3_id);

-- Solicitud pendiente
INSERT INTO requests (user_id, category_id, title, description, status, created_at, updated_at) VALUES
(@cliente_id, 2, 'Solicitud de limpieza', 'Necesito servicio de limpieza para oficina', 'pendiente', NOW(), NOW());
SET @request1_id = LAST_INSERT_ID();

-- Solicitud en proceso
INSERT INTO requests (user_id, category_id, title, description, status, created_at, updated_at) VALUES
(@cliente_id, 3, 'Reparación de aire acondicionado', 'El aire acondicionado no enfría', 'en_proceso', DATE_SUB(NOW(), INTERVAL 10 DAY), NOW());
SET @request2_id = LAST_INSERT_ID();

-- Solicitud completada
INSERT INTO requests (user_id, category_id, title, description, status, created_at, updated_at) VALUES
(@cliente2_id, 2, 'Limpieza mensual', 'Servicio mensual de limpieza', 'completado', DATE_SUB(NOW(), INTERVAL 30 DAY), NOW());

-- =====================================================
-- RESUMEN DE DATOS CREADOS
-- =====================================================
SELECT '========================================' AS '';
SELECT 'DATOS DE PRUEBA CREADOS CORRECTAMENTE' AS '';
SELECT '========================================' AS '';
SELECT 'USUARIOS:' AS '';
SELECT CONCAT('  - Admin: admin@test.com (ID: ', @admin_id, ')') AS '';
SELECT CONCAT('  - Comercial: comercial@test.com (ID: ', @comercial_id, ')') AS '';
SELECT CONCAT('  - Proveedor: proveedor@test.com (ID: ', @proveedor_id, ')') AS '';
SELECT CONCAT('  - Asesoría: asesoria@test.com (ID: ', @asesoria_user_id, ')') AS '';
SELECT CONCAT('  - Asesoría Gratuita: asesoria2@test.com (ID: ', @asesoria2_user_id, ')') AS '';
SELECT CONCAT('  - Cliente 1: cliente@test.com (ID: ', @cliente_id, ')') AS '';
SELECT CONCAT('  - Cliente 2: cliente2@test.com (ID: ', @cliente2_id, ')') AS '';
SELECT CONCAT('  - Cliente 3: cliente3@test.com (ID: ', @cliente3_id, ') - Sin asesoría') AS '';
SELECT '' AS '';
SELECT 'ASESORÍAS:' AS '';
SELECT CONCAT('  - Asesoría Test (Plan Basic): ID ', @asesoria_id, ', Código: ASE-TEST001') AS '';
SELECT CONCAT('  - Asesoría Gratuita: ID ', @asesoria2_id, ', Código: ASE-TEST002') AS '';
SELECT '' AS '';
SELECT 'PASSWORD PARA TODOS: test123' AS '';
SELECT '========================================' AS '';
