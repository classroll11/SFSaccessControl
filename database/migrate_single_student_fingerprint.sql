-- Keep the oldest registered fingerprint for each student and discard extras.
-- If a student has a legacy template, synchronize it to the retained fingerprint.
START TRANSACTION;

DELETE newer
FROM huellas_dactilares AS newer
JOIN usuarios AS estudiante ON estudiante.id = newer.usuario_id
JOIN huellas_dactilares AS older
  ON older.usuario_id = newer.usuario_id
 AND (
      older.creado_en < newer.creado_en
      OR (older.creado_en = newer.creado_en AND older.id < newer.id)
 )
WHERE estudiante.rol = 'ESTUDIANTE';

UPDATE huellas_dactilares AS h
JOIN usuarios AS estudiante ON estudiante.id = h.usuario_id
SET h.slot_numero = 1
WHERE estudiante.rol = 'ESTUDIANTE';

INSERT INTO huellas_dactilares (usuario_id, slot_numero, dedo, huella_template)
SELECT estudiante.id, 1, 'Huella Principal', estudiante.huella_template
FROM usuarios AS estudiante
WHERE estudiante.rol = 'ESTUDIANTE'
  AND estudiante.huella_template IS NOT NULL
  AND estudiante.huella_template <> ''
  AND NOT EXISTS (
      SELECT 1 FROM huellas_dactilares AS h WHERE h.usuario_id = estudiante.id
  );

UPDATE usuarios AS estudiante
JOIN huellas_dactilares AS h ON h.usuario_id = estudiante.id
SET estudiante.huella_template = h.huella_template
WHERE estudiante.rol = 'ESTUDIANTE';

COMMIT;
