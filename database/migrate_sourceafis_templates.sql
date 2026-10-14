-- Run once against an existing SFS Access database before enrolling SourceAFIS templates.
-- The previous 255-character prefix index can reject different serialized templates
-- that happen to share the same prefix. Duplicate fingerprints are checked by matching.
SET @drop_fingerprint_prefix_index = (
    SELECT IF(
        COUNT(*) > 0,
        'ALTER TABLE `huellas_dactilares` DROP INDEX `uk_huella_template`',
        'SELECT 1'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'huellas_dactilares'
      AND index_name = 'uk_huella_template'
);
PREPARE drop_fingerprint_prefix_index FROM @drop_fingerprint_prefix_index;
EXECUTE drop_fingerprint_prefix_index;
DEALLOCATE PREPARE drop_fingerprint_prefix_index;
