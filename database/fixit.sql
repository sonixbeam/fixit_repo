CREATE DATABASE IF NOT EXISTS fixit
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE fixit;

CREATE TABLE IF NOT EXISTS room (
    room_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    floor TINYINT UNSIGNED NOT NULL,
    room_status ENUM('AVAILABLE', 'OCCUPIED', 'MAINTENANCE') NOT NULL DEFAULT 'AVAILABLE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `user` (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(150) NULL UNIQUE,
    role ENUM('STUDENT', 'TECHNICIAN', 'ADMIN') NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_room
            FOREIGN KEY (room_id) REFERENCES room(room_id)
            ON UPDATE RESTRICT ON DELETE RESTRICT,
        CONSTRAINT chk_user_room_by_role
        CHECK (
            (role = 'STUDENT' AND room_id IS NOT NULL)
            OR
            (role IN ('TECHNICIAN', 'ADMIN') AND room_id IS NULL)
        ),
    INDEX idx_user_role (role),
    INDEX idx_user_room (room_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS common_area (
    area_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS repair_category (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS repair_request (
    request_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    area_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL DEFAULT 'MEDIUM',
    status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED')
        NOT NULL DEFAULT 'PENDING',
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME NULL,
    completed_at DATETIME NULL,
    CONSTRAINT fk_repair_request_user
        FOREIGN KEY (user_id) REFERENCES `user`(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_repair_request_room
        FOREIGN KEY (room_id) REFERENCES room(room_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_repair_request_area
        FOREIGN KEY (area_id) REFERENCES common_area(area_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_repair_request_category
        FOREIGN KEY (category_id) REFERENCES repair_category(category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_repair_request_location
        CHECK (
            (room_id IS NOT NULL AND area_id IS NULL)
            OR
            (room_id IS NULL AND area_id IS NOT NULL)
        ),
    INDEX idx_repair_request_user (user_id),
    INDEX idx_repair_request_status (status),
    INDEX idx_repair_request_created (created_at),
    INDEX idx_repair_request_room (room_id),
    INDEX idx_repair_request_area (area_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS repair_assignment (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    technician_user_id INT UNSIGNED NOT NULL,
    assigned_by_user_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note TEXT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_assignment_request
        FOREIGN KEY (request_id) REFERENCES repair_request(request_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_technician
        FOREIGN KEY (technician_user_id) REFERENCES `user`(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_admin
        FOREIGN KEY (assigned_by_user_id) REFERENCES `user`(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_assignment_request_active (request_id, is_active),
    INDEX idx_assignment_technician (technician_user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS repair_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    changed_by_user_id INT UNSIGNED NOT NULL,
    old_status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') NULL,
    new_status ENUM('PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED') NOT NULL,
    note TEXT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_request
        FOREIGN KEY (request_id) REFERENCES repair_request(request_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_history_user
        FOREIGN KEY (changed_by_user_id) REFERENCES `user`(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_history_request_time (request_id, changed_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO room (room_number, floor, room_status) VALUES
('101', 1, 'OCCUPIED'),
('102', 1, 'AVAILABLE'),
('201', 2, 'AVAILABLE'),
('202', 2, 'AVAILABLE');

INSERT IGNORE INTO common_area (area_name, description) VALUES
('โถงชั้น 1', 'พื้นที่โถงส่วนกลางชั้น 1'),
('ห้องซักผ้า', 'พื้นที่สำหรับเครื่องซักผ้าและตากผ้า'),
('พื้นที่จอดรถ', 'พื้นที่จอดรถของหอพัก');

INSERT IGNORE INTO repair_category (category_name, description) VALUES
('ไฟฟ้า', 'ระบบไฟฟ้าและอุปกรณ์ไฟฟ้า'),
('ประปา', 'ท่อน้ำ ก๊อกน้ำ และระบบประปา'),
('เครื่องปรับอากาศ', 'เครื่องปรับอากาศและระบบระบายอากาศ'),
('เฟอร์นิเจอร์', 'เตียง โต๊ะ เก้าอี้ และเฟอร์นิเจอร์'),
('สุขภัณฑ์', 'โถสุขภัณฑ์ อ่างล้างหน้า และอุปกรณ์ห้องน้ำ'),
('ประตูและหน้าต่าง', 'ประตู ลูกบิด กุญแจ และหน้าต่าง'),
('อินเทอร์เน็ต/เครือข่าย', 'ระบบอินเทอร์เน็ตและเครือข่าย'),
('อุปกรณ์ส่วนกลาง', 'อุปกรณ์ที่ใช้ร่วมกันในพื้นที่ส่วนกลาง'),
('อาคารและโครงสร้าง', 'ผนัง พื้น เพดาน และโครงสร้างอาคาร'),
('อื่น ๆ', 'ปัญหาที่ไม่อยู่ในหมวดหมู่ข้างต้น');

INSERT IGNORE INTO `user`
(room_id, username, password, first_name, last_name, phone, email, role)
SELECT room_id, 'student001',
'$2y$12$jfpieVsp7us9nSyNnwuAeOf1aixnJBzA8GwbnqKGr1A2GFZFGSQwi',
'Demo', 'Student', NULL, 'student001@example.test', 'STUDENT'
FROM room WHERE room_number = '101';

INSERT IGNORE INTO `user`
(room_id, username, password, first_name, last_name, phone, email, role)
VALUES
(NULL, 'tech001',
'$2y$12$U.FRUtqDi/V8hBF7/v7FE.1LrL1ESlkWWxnDAt6Tu02kGFUUjaPuq',
'Demo', 'Technician', NULL, 'tech001@example.test', 'TECHNICIAN'),
(NULL, 'admin001',
'$2y$12$jJICVFd5fkiFQThAScu2yuiFjtEXcfSHN0Pt7QUS4R/Okd2zehszq',
'Demo', 'Admin', NULL, 'admin001@example.test', 'ADMIN');
