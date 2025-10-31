CREATE INDEX IF NOT EXISTS ix_xillix_schedule_teacher_date ON b_hlbd_xillix_schedule(UF_TEACHER_ID, UF_DATE);
CREATE INDEX IF NOT EXISTS ix_xillix_schedule_status ON b_hlbd_xillix_schedule(UF_STATUS);
CREATE INDEX IF NOT EXISTS ix_xillix_schedule_student ON b_hlbd_xillix_schedule(UF_STUDENT_ID);