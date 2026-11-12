-- ตรวจ SHOW CREATE TABLE ก่อนรัน: request_id และ user_id ต้องเป็น INT UNSIGNED / InnoDB
-- รันใน database ที่เลือกไว้; ไม่ลบ/แก้ข้อมูลเดิม
-- IF NOT EXISTS ไม่อัปเกรดตารางทดลองที่มี schema ต่างกัน ต้องตรวจแยกก่อน
CREATE TABLE IF NOT EXISTS repair_completion (
    request_id INT UNSIGNED NOT NULL,
    submitted_by_user_id INT UNSIGNED NOT NULL,
    completion_photo_path VARCHAR(255) NOT NULL,
    note TEXT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    confirmed_by_user_id INT UNSIGNED NULL,
    PRIMARY KEY (request_id),
    CONSTRAINT fk_completion_request FOREIGN KEY (request_id)
        REFERENCES repair_request(request_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_completion_submitter FOREIGN KEY (submitted_by_user_id)
        REFERENCES `user`(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_completion_confirmer FOREIGN KEY (confirmed_by_user_id)
        REFERENCES `user`(user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
