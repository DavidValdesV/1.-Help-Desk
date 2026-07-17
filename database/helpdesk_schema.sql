-- ============================================================
--  HELP DESK IT - Script de Base de Datos Completo
--  Versión: 1.1
--  Motor: MySQL 8.0+
-- ============================================================

-- Crear y seleccionar la base de datos
DROP DATABASE IF EXISTS helpdesk_db;
CREATE DATABASE helpdesk_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE helpdesk_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE usuarios (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre            VARCHAR(100)    NOT NULL,
    email             VARCHAR(150)    NOT NULL,
    password          VARCHAR(255)    NOT NULL COMMENT 'Hash bcrypt',
    rol               ENUM('cliente', 'tecnico', 'admin') NOT NULL DEFAULT 'cliente',
    departamento      VARCHAR(100)    NULL,
    telefono          VARCHAR(20)     NULL,
    avatar            VARCHAR(255)    NULL,
    activo            TINYINT(1)      NOT NULL DEFAULT 1,
    email_verified_at TIMESTAMP       NULL,
    remember_token    VARCHAR(100)    NULL,
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuarios del sistema con roles diferenciados';

-- ============================================================
-- TABLA: categorias
-- ============================================================
CREATE TABLE categorias (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(80)     NOT NULL,
    descripcion TEXT            NULL,
    icono       VARCHAR(60)     NULL COMMENT 'Clase Font Awesome (ej: fa-wifi)',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: prioridades
-- ============================================================
CREATE TABLE prioridades (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(40)     NOT NULL,
    color       VARCHAR(7)      NOT NULL DEFAULT '#6c757d',
    horas_sla   INT UNSIGNED    NOT NULL COMMENT 'Horas maximas para resolver',
    orden       TINYINT         NOT NULL DEFAULT 0,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Prioridades y SLA';

-- ============================================================
-- TABLA: tickets (tabla central)
-- ============================================================
CREATE TABLE tickets (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero_ticket     VARCHAR(20)     NOT NULL COMMENT 'Ej: TKT-2026-0001',
    usuario_id        BIGINT UNSIGNED NOT NULL COMMENT 'Cliente que abre el ticket',
    categoria_id      INT UNSIGNED    NOT NULL,
    prioridad_id      INT UNSIGNED    NOT NULL DEFAULT 2 COMMENT 'Por defecto: Media',
    asignado_a        BIGINT UNSIGNED NULL     COMMENT 'Tecnico asignado (NULL = sin asignar)',
    titulo            VARCHAR(255)    NOT NULL,
    descripcion       TEXT            NOT NULL,
    estado            ENUM('abierto','en_progreso','en_espera','resuelto','cerrado')
                                      NOT NULL DEFAULT 'abierto',
    notas_resolucion  TEXT            NULL,
    due_at            TIMESTAMP       NULL     COMMENT 'Fecha limite segun SLA',
    resolved_at       TIMESTAMP       NULL,
    closed_at         TIMESTAMP       NULL,
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ticket_numero      (numero_ticket),
    KEY idx_tickets_usuario_id       (usuario_id),
    KEY idx_tickets_asignado_a       (asignado_a),
    KEY idx_tickets_estado           (estado),
    KEY idx_tickets_prioridad_id     (prioridad_id),
    KEY idx_tickets_created_at       (created_at),

    CONSTRAINT fk_tickets_usuario
        FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)    ON DELETE RESTRICT  ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)  ON DELETE RESTRICT  ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_prioridad
        FOREIGN KEY (prioridad_id) REFERENCES prioridades(id) ON DELETE RESTRICT  ON UPDATE CASCADE,
    CONSTRAINT fk_tickets_asignado
        FOREIGN KEY (asignado_a)   REFERENCES usuarios(id)    ON DELETE SET NULL  ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla central de tickets de soporte';

-- ============================================================
-- TABLA: comentarios_tickets
-- ============================================================
CREATE TABLE comentarios_tickets (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id   BIGINT UNSIGNED NOT NULL,
    usuario_id  BIGINT UNSIGNED NOT NULL,
    cuerpo      TEXT            NOT NULL,
    es_interno  TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = nota solo para tecnicos/admin',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_comentarios_ticket_id  (ticket_id),
    KEY idx_comentarios_usuario_id (usuario_id),

    CONSTRAINT fk_comentarios_ticket
        FOREIGN KEY (ticket_id)  REFERENCES tickets(id)  ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_comentarios_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Comentarios publicos y notas internas de tickets';

-- ============================================================
-- TABLA: registro_auditoria_tickets
-- ============================================================
CREATE TABLE registro_auditoria_tickets (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id      BIGINT UNSIGNED NOT NULL,
    usuario_id     BIGINT UNSIGNED NULL     COMMENT 'NULL si fue accion automatica del sistema',
    accion         VARCHAR(80)     NOT NULL COMMENT 'estado_cambiado | asignado | prioridad_cambiada | creado | resuelto',
    valor_anterior JSON            NULL     COMMENT 'Valor anterior en JSON',
    valor_nuevo    JSON            NULL     COMMENT 'Valor nuevo en JSON',
    descripcion    VARCHAR(500)    NOT NULL COMMENT 'Texto legible para la UI',
    direccion_ip   VARCHAR(45)     NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_auditoria_ticket_id  (ticket_id),
    KEY idx_auditoria_usuario_id (usuario_id),
    KEY idx_auditoria_created_at (created_at),
    KEY idx_auditoria_accion     (accion),

    CONSTRAINT fk_auditoria_ticket
        FOREIGN KEY (ticket_id)  REFERENCES tickets(id)  ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auditoria: registro de cada cambio realizado en tickets';

-- ============================================================
-- TABLA: archivos_adjuntos_tickets
-- ============================================================
CREATE TABLE archivos_adjuntos_tickets (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id       BIGINT UNSIGNED NOT NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255)    NOT NULL,
    ruta            VARCHAR(500)    NOT NULL,
    tipo_mime       VARCHAR(100)    NOT NULL,
    tamano_bytes    INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_adjuntos_ticket (ticket_id),

    CONSTRAINT fk_adjuntos_ticket
        FOREIGN KEY (ticket_id)  REFERENCES tickets(id)  ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_adjuntos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Archivos adjuntos de tickets';

-- ============================================================
-- TABLA: carga_tecnicos
-- ============================================================
CREATE TABLE carga_tecnicos (
    id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id       BIGINT UNSIGNED NOT NULL,
    tickets_activos  INT UNSIGNED    NOT NULL DEFAULT 0,
    updated_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_carga_usuario (usuario_id),

    CONSTRAINT fk_carga_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cache de carga de trabajo por tecnico (asignacion automatica)';

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
--  SEED DATA
-- ============================================================

INSERT INTO prioridades (nombre, color, horas_sla, orden) VALUES
('Baja',    '#3498db', 72, 1),
('Media',   '#f39c12', 24, 2),
('Alta',    '#e67e22',  8, 3),
('Critica', '#e74c3c',  2, 4);

INSERT INTO categorias (nombre, descripcion, icono) VALUES
('Red / Conectividad',       'Problemas de internet, VPN, switches, WiFi',               'fa-wifi'),
('Hardware',                 'Fallas en PC, laptop, teclado, mouse, monitor',             'fa-computer'),
('Software / Aplicaciones',  'Errores en programas, instalaciones, actualizaciones',      'fa-laptop-code'),
('Acceso y Seguridad',       'Contrasenas, permisos, bloqueos de cuenta, antivirus',      'fa-shield-halved'),
('Impresoras y Perifericos', 'Impresoras, escaneres, lectores de codigo de barras',       'fa-print'),
('Servidores',               'Problemas con servidores locales o en la nube',             'fa-server'),
('Correo Electronico',       'Outlook, Gmail corporativo, listas de distribucion',        'fa-envelope'),
('Otro',                     'Solicitudes que no encajan en las categorias anteriores',   'fa-circle-question');

-- Contrasena para todos: "password"  (hash bcrypt cost=10)
INSERT INTO usuarios (nombre, email, password, rol, departamento, telefono, activo, email_verified_at) VALUES
('Carlos Admin',    'admin@helpdesk.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',   'TI',               '+58-412-0000001', 1, NOW()),
('Ana Garcia',      'ana.garcia@helpdesk.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico', 'Soporte TI',       '+58-412-0000002', 1, NOW()),
('Luis Martinez',   'luis.martinez@helpdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico', 'Soporte TI',       '+58-412-0000003', 1, NOW()),
('Elena Torres',    'elena.torres@helpdesk.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico', 'Infraestructura',  '+58-412-0000004', 1, NOW()),
('Roberto Sanchez', 'roberto@empresa.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Contabilidad',     '+58-412-0000005', 1, NOW()),
('Maria Lopez',     'maria@empresa.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Recursos Humanos', '+58-412-0000006', 1, NOW()),
('Jorge Ramirez',   'jorge@empresa.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Ventas',           '+58-412-0000007', 1, NOW()),
('Patricia Vega',   'patricia@empresa.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Logistica',        '+58-412-0000008', 1, NOW());

INSERT INTO carga_tecnicos (usuario_id, tickets_activos) VALUES
(2, 0), (3, 0), (4, 0);

INSERT INTO tickets
    (numero_ticket, usuario_id, categoria_id, prioridad_id, asignado_a, titulo, descripcion, estado, due_at)
VALUES
('TKT-2026-0001', 5, 1, 3, 2,
 'Sin acceso a internet en toda la oficina de contabilidad',
 'Desde esta manana ninguno de los 8 equipos del area de contabilidad puede acceder a internet. El switch del piso 2 tiene las luces intermitentes. No podemos acceder al sistema bancario.',
 'en_progreso', DATE_ADD(NOW(), INTERVAL 8 HOUR)),

('TKT-2026-0002', 6, 2, 2, 3,
 'Teclado y mouse no responden en laptop HP',
 'Mi laptop HP EliteBook 840 no reconoce el teclado ni el mouse desde el reinicio de ayer. Probé desconectando y reconectando sin resultado. Tengo presentacion manana.',
 'abierto', DATE_ADD(NOW(), INTERVAL 24 HOUR)),

('TKT-2026-0003', 7, 3, 1, 4,
 'Error al abrir Microsoft Excel - Licencia vencida',
 'Al intentar abrir Excel aparece su licencia de Office ha vencido. Los demas programas de Office tambien fallan. Afecta mis reportes de ventas diarios.',
 'abierto', DATE_ADD(NOW(), INTERVAL 72 HOUR)),

('TKT-2026-0004', 8, 4, 4, 2,
 'URGENTE: Cuenta admin bloqueada - Sistema de nomina',
 'La cuenta admin del sistema de nomina esta bloqueada por multiples intentos fallidos. Manana es dia de pago para 200 empleados. CRITICO.',
 'en_progreso', DATE_ADD(NOW(), INTERVAL 2 HOUR)),

('TKT-2026-0005', 5, 5, 2, 3,
 'Impresora no imprime en color',
 'La impresora HP LaserJet Pro del piso 1 solo imprime en blanco y negro. Ya intentamos cambiar la configuracion del driver.',
 'resuelto', DATE_ADD(NOW(), INTERVAL 24 HOUR)),

('TKT-2026-0006', 6, 1, 3, NULL,
 'VPN no conecta desde trabajo remoto',
 'Desde casa no puedo conectarme a la VPN corporativa. Error 800: No se pudo establecer la conexion VPN.',
 'abierto', DATE_ADD(NOW(), INTERVAL 8 HOUR));

UPDATE carga_tecnicos SET tickets_activos = 2 WHERE usuario_id = 2;
UPDATE carga_tecnicos SET tickets_activos = 1 WHERE usuario_id = 3;
UPDATE carga_tecnicos SET tickets_activos = 1 WHERE usuario_id = 4;
UPDATE tickets SET resolved_at = DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE id = 5;

INSERT INTO comentarios_tickets (ticket_id, usuario_id, cuerpo, es_interno) VALUES
(1, 2, 'Me encuentro en camino al area de contabilidad. Revisare el switch del piso 2. ETA: 15 minutos.', 0),
(1, 5, 'Gracias Ana, te esperamos. El equipo #3 tampoco enciende correctamente.', 0),
(1, 2, 'NOTA INTERNA: El switch tiene el puerto 4 danado. Necesito autorizacion para reemplazarlo. Costo aprox $150.', 1),
(1, 1, 'NOTA INTERNA: Autorizado el reemplazo. Procede.', 1),
(4, 2, 'Revisando el sistema ahora mismo. Cuantos intentos fallidos registra?', 0),
(4, 8, 'El log muestra 10 intentos entre las 8:00 y 8:45 AM. Nadie de mi equipo intento entrar a esa hora.', 0),
(4, 2, 'Posible ataque de fuerza bruta. Desbloqueo y fuerzo cambio de contrasena. Recomiendo auditoria de seguridad.', 0),
(5, 3, 'El cartucho de color estaba vacio. Reemplazado. Impresora funcionando correctamente.', 0),
(5, 8, 'Confirmado, ya imprime en color. Muchas gracias Luis!', 0);

INSERT INTO registro_auditoria_tickets (ticket_id, usuario_id, accion, valor_anterior, valor_nuevo, descripcion, direccion_ip) VALUES
(1, 5,    'creado',             NULL,                                    JSON_OBJECT('estado','abierto','prioridad','Alta'),   'Roberto Sanchez creo el ticket TKT-2026-0001',                                     '192.168.1.50'),
(1, NULL, 'auto_asignado',      JSON_OBJECT('asignado_a',NULL),          JSON_OBJECT('asignado_a','Ana Garcia'),               'Sistema asigno automaticamente a Ana Garcia (menor carga: 0 tickets activos)',     NULL),
(1, 2,    'estado_cambiado',    JSON_OBJECT('estado','abierto'),         JSON_OBJECT('estado','en_progreso'),                  'Ana Garcia cambio el estado de Abierto a En Progreso',                             '192.168.1.10'),
(4, 8,    'creado',             NULL,                                    JSON_OBJECT('estado','abierto','prioridad','Alta'),   'Patricia Vega creo el ticket TKT-2026-0004',                                       '192.168.1.55'),
(4, 1,    'prioridad_cambiada', JSON_OBJECT('prioridad','Alta'),         JSON_OBJECT('prioridad','Critica'),                   'Carlos Admin elevo la prioridad de Alta a Critica',                                '192.168.1.1'),
(4, 1,    'asignado',           JSON_OBJECT('asignado_a',NULL),          JSON_OBJECT('asignado_a','Ana Garcia'),               'Carlos Admin asigno el ticket a Ana Garcia manualmente',                           '192.168.1.1'),
(4, 2,    'estado_cambiado',    JSON_OBJECT('estado','abierto'),         JSON_OBJECT('estado','en_progreso'),                  'Ana Garcia cambio el estado de Abierto a En Progreso',                             '192.168.1.10'),
(5, 3,    'estado_cambiado',    JSON_OBJECT('estado','en_progreso'),     JSON_OBJECT('estado','resuelto'),                     'Luis Martinez cambio el estado de En Progreso a Resuelto',                        '192.168.1.11');


-- ============================================================
-- VISTAS
-- ============================================================

CREATE OR REPLACE VIEW v_tickets_completos AS
SELECT
    t.id, t.numero_ticket, t.titulo, t.estado, t.created_at, t.due_at, t.resolved_at,
    u_c.nombre        AS cliente_nombre,
    u_c.email         AS cliente_email,
    u_c.departamento  AS cliente_departamento,
    u_t.nombre        AS tecnico_nombre,
    u_t.email         AS tecnico_email,
    c.nombre          AS categoria,
    c.icono           AS categoria_icono,
    p.nombre          AS prioridad,
    p.color           AS prioridad_color,
    p.horas_sla       AS sla_horas,
    CASE WHEN t.estado NOT IN ('resuelto','cerrado') AND t.due_at < NOW() THEN 1 ELSE 0 END AS vencido
FROM tickets t
JOIN  usuarios u_c      ON t.usuario_id   = u_c.id
LEFT JOIN usuarios u_t  ON t.asignado_a   = u_t.id
JOIN  categorias c      ON t.categoria_id = c.id
JOIN  prioridades p     ON t.prioridad_id = p.id;

CREATE OR REPLACE VIEW v_estadisticas_tecnicos AS
SELECT
    u.id, u.nombre, u.email, u.departamento, u.activo,
    COALESCE(ct.tickets_activos, 0)                          AS tickets_activos,
    COUNT(tr.id)                                              AS tickets_resueltos_total,
    AVG(TIMESTAMPDIFF(HOUR, tr.created_at, tr.resolved_at))   AS promedio_horas_resolucion
FROM usuarios u
LEFT JOIN carga_tecnicos ct ON u.id = ct.usuario_id
LEFT JOIN tickets tr ON u.id = tr.asignado_a AND tr.estado IN ('resuelto','cerrado')
WHERE u.rol = 'tecnico'
GROUP BY u.id, u.nombre, u.email, u.departamento, u.activo, ct.tickets_activos;


-- ============================================================
-- CONSULTAS DE REFERENCIA (comentadas - para usar como guia)
-- ============================================================
/*
-- Ver todos los tickets con info completa:
SELECT * FROM v_tickets_completos ORDER BY created_at DESC;

-- Tecnico con menor carga (logica asignacion automatica):
SELECT u.id, u.nombre, ct.tickets_activos
FROM usuarios u JOIN carga_tecnicos ct ON u.id = ct.usuario_id
WHERE u.rol = 'tecnico' AND u.activo = 1
ORDER BY ct.tickets_activos ASC LIMIT 1;

-- Auditoria de un ticket:
SELECT al.created_at, COALESCE(u.nombre, 'Sistema') AS actor, al.accion, al.descripcion
FROM registro_auditoria_tickets al LEFT JOIN usuarios u ON al.usuario_id = u.id
WHERE al.ticket_id = 1 ORDER BY al.created_at ASC;

-- Tickets vencidos:
SELECT numero_ticket, titulo, due_at, estado FROM tickets
WHERE due_at < NOW() AND estado NOT IN ('resuelto','cerrado');

-- KPIs para dashboard:
SELECT COUNT(*) total,
    SUM(estado='abierto') abiertos, SUM(estado='en_progreso') en_progreso,
    SUM(estado='resuelto') resueltos, SUM(estado='cerrado') cerrados
FROM tickets;

-- Stats por tecnico:
SELECT * FROM v_estadisticas_tecnicos ORDER BY tickets_activos ASC;
*/

SELECT 'Base de datos helpdesk_db creada exitosamente.' AS resultado;