-- =====================================================================
-- Usuarios de prueba: uno por cada perfil exigido en R1.
-- Contraseña de todos: Cali2026*   (cambiarla en producción)
-- El hash se generó con password_hash() de PHP (bcrypt).
-- =====================================================================
USE encuestas_cali;

SET @hash = '$2y$10$yM7h.BKuVGYsecQmO6IXrO2jLPo8OhTC1/HVbq6TnjbU28tK/kIrq';

INSERT INTO usuarios (documento, nombres, apellidos, correo, password_hash, perfil_id, punto_atencion_id)
SELECT '1000000001', 'Ana', 'Administradora', 'admin@encuestas.test', @hash,
       (SELECT id FROM perfiles WHERE nombre = 'administrador'), NULL
UNION ALL
SELECT '1000000002', 'Valentina', 'Ventanilla', 'ventanilla@encuestas.test', @hash,
       (SELECT id FROM perfiles WHERE nombre = 'ventanilla'),
       (SELECT p.id FROM puntos_atencion p JOIN comunas_corregimientos c ON c.id = p.comuna_id WHERE c.numero = 17)
UNION ALL
SELECT '1000000003', 'Esteban', 'Encuestador', 'encuestador@encuestas.test', @hash,
       (SELECT id FROM perfiles WHERE nombre = 'encuestador'), NULL
UNION ALL
SELECT '1000000004', 'Camila', 'Calidad', 'calidad@encuestas.test', @hash,
       (SELECT id FROM perfiles WHERE nombre = 'calidad'), NULL
UNION ALL
SELECT '1000000005', 'Ernesto', 'Enrutador', 'enrutador@encuestas.test', @hash,
       (SELECT id FROM perfiles WHERE nombre = 'enrutador'), NULL
UNION ALL
SELECT '1000000006', 'Víctor', 'Ventanilla', 'ventanilla.c1@encuestas.test', @hash,
       (SELECT id FROM perfiles WHERE nombre = 'ventanilla'),
       (SELECT p.id FROM puntos_atencion p JOIN comunas_corregimientos c ON c.id = p.comuna_id WHERE c.numero = 1);
