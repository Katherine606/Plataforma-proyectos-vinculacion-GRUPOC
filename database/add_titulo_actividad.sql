-- Agregar columna titulo a actividades
ALTER TABLE actividades ADD COLUMN titulo VARCHAR(200) NOT NULL DEFAULT '' AFTER tutor_id;

-- Migrar datos existentes: usar los primeros 200 chars de descripcion como titulo
UPDATE actividades SET titulo = LEFT(descripcion, 200) WHERE titulo = '';
