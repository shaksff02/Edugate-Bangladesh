-- Phase 7: Programs and program detail
-- study_mode column (mode filter per PRD) + indexes for search fields.

ALTER TABLE programs
  ADD COLUMN study_mode ENUM('ONCAMPUS','ONLINE','HYBRID') NOT NULL DEFAULT 'ONCAMPUS' AFTER language_of_instruction;

CREATE INDEX idx_programs_mode     ON programs (study_mode);
CREATE INDEX idx_programs_language ON programs (language_of_instruction);
