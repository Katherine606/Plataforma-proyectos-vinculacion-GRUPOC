-- ============================================================
-- FIX: Alinear ENUM de estados con el código PHP/TypeScript
-- El backend escribe 'aceptada'/'denegada', el ENUM debe coincidir
-- ============================================================

-- Fix ENUM en tabla solicitudes
ALTER TABLE solicitudes
  MODIFY COLUMN estado ENUM('pendiente','aceptada','denegada') DEFAULT 'pendiente';

-- Fix ENUM en tabla horas_vinculacion
ALTER TABLE horas_vinculacion
  MODIFY COLUMN estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente';

-- Corregir datos viejos por si existían con valores incorrectos
UPDATE solicitudes SET estado = 'aceptada' WHERE estado = 'aceptado';
UPDATE solicitudes SET estado = 'denegada' WHERE estado = 'denegado';
