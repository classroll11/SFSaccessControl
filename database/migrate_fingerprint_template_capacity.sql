-- Expand serialized biometric-template columns to MEDIUMTEXT (up to 16 MiB).
-- Drop legacy full-column indexes before changing VARCHAR to MEDIUMTEXT.
SET @drop_legacy_unique_template_index = (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE `huellas_dactilares` DROP INDEX `huella_template`',
        'SELECT 1'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'huellas_dactilares'
      AND index_name = 'huella_template'
      AND non_unique = 0
);
PREPARE drop_legacy_unique_template_index FROM @drop_legacy_unique_template_index;
EXECUTE drop_legacy_unique_template_index;
DEALLOCATE PREPARE drop_legacy_unique_template_index;

SET @drop_sourceafis_prefix_index = (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE `huellas_dactilares` DROP INDEX `uk_huella_template`',
        'SELECT 1'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'huellas_dactilares'
      AND index_name = 'uk_huella_template'
      AND non_unique = 0
);
PREPARE drop_sourceafis_prefix_index FROM @drop_sourceafis_prefix_index;
EXECUTE drop_sourceafis_prefix_index;
DEALLOCATE PREPARE drop_sourceafis_prefix_index;

SET @drop_template_lookup_index = (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE `huellas_dactilares` DROP INDEX `idx_huella_template`',
        'SELECT 1'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'huellas_dactilares'
      AND index_name = 'idx_huella_template'
);
PREPARE drop_template_lookup_index FROM @drop_template_lookup_index;
EXECUTE drop_template_lookup_index;
DEALLOCATE PREPARE drop_template_lookup_index;

ALTER TABLE `huellas_dactilares`
    MODIFY COLUMN `huella_template` MEDIUMTEXT NOT NULL
    COMMENT 'Plantilla biométrica serializada';

ALTER TABLE `usuarios`
    MODIFY COLUMN `huella_template` MEDIUMTEXT NULL
    COMMENT 'Plantilla biométrica serializada de respaldo';
