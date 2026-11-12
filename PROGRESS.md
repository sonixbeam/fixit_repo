# PROGRESS.md — FixIT ระบบแจ้งซ่อมหอพัก

> บันทึกความคืบหน้าและแผนส่งต่องานสำหรับ Codex / ผู้พัฒนา
>
> **อัปเดตฐานข้อมูลเริ่มต้นของเอกสาร:** 13 พฤศจิกายน 2026 (จากบทสนทนาการพัฒนาเดิม ไม่ได้ตรวจ Workspace หรือฐานข้อมูลจริง)
>
> **กติกา:** อ่าน `AGENTS.md` เพื่อรู้ Requirements และข้อกำหนดถาวร อ่าน `PROGRESS.md` เพื่อทราบสถานะงานปัจจุบันและขั้นตอนถัดไป หากสองไฟล์ขัดกันเรื่องความคืบหน้า ให้ยึดหลักฐานจากไฟล์/ฐานข้อมูล/ผลทดสอบจริง แล้วปรับ `PROGRESS.md` ให้ถูกต้อง; หากขัดกันเรื่อง Requirement ห้ามเปลี่ยนเองโดยพลการ

## 1. สถานะล่าสุด (Current Checkpoint)

- **Current Phase:** `8 — Desktop UI/UX Refinement`
- **Current State:** `NOT STARTED — รอวางแผนจากข้อเสนอแนะผู้ใช้`
- **Last Implemented Phase:** `7 — Reports & Integration — DONE — VERIFIED`
- **Current Goal:** Phase 7 ปิดแล้ว; ขั้นถัดไปวางแผน Phase 8 จาก UI ปัจจุบันและข้อเสนอแนะผู้ใช้ ยังไม่เริ่ม implementation
- **Important:** ผู้ใช้ยืนยัน “UI Phase 7 ผ่านแล้ว”; ผู้ใช้ตรวจ UI Desktop เอง Agent เตรียม Checklist และทดสอบ backend/HTTP/DB ตามปกติ ไม่ทดสอบ Mobile
- **Verification:** ตรวจโค้ดและ SHOW CREATE TABLE จากฐานข้อมูลจริง `fixit` แล้วเมื่อ 13 พฤศจิกายน 2026; ดู Change Log ด้านล่างสำหรับผลทดสอบล่าสุด

### คำอธิบายสถานะ

- `NOT STARTED` = ยังไม่เริ่มงานที่นับเป็นการพัฒนาปัจจุบัน
- `IN PROGRESS` = กำลังพัฒนา มีบางส่วนแล้ว แต่ยังไม่ผ่านเกณฑ์ทั้งหมด
- `IMPLEMENTED — NEEDS VERIFICATION` = เขียนโค้ดแล้ว แต่ยังไม่ยืนยันผลทดสอบครบ
- `DONE — VERIFIED` = ผ่านเกณฑ์ยอมรับและมีหลักฐานการทดสอบจริง
- `BLOCKED` = มีข้อขัดข้องที่ต้องแก้ก่อนดำเนินต่อ

## 2. ประวัติ Phase

| Phase | Scope | สถานะตามข้อมูลที่ผ่านมา | สิ่งที่ต้องตรวจยืนยัน |
|---|---|---|---|
| 1 | Database, ERD, ตัด `dormitory`, 10 Categories | พัฒนาแล้ว | ตรวจ Schema ปัจจุบันและ Migration ที่ใช้จริง |
| 2 | PHP PDO / เชื่อม MySQL | ผู้ใช้เคยทดสอบผ่าน | ตรวจ Connection ใน Environment ปัจจุบัน |
| 3 | Login, Session, Logout, Role Access | ผู้ใช้เคยทดสอบผ่าน 7 ข้อ | ตรวจ Role/Session Regression |
| 4.1 | Student สร้างคำร้องห้องตน/พื้นที่ส่วนกลาง, Optional image, `PENDING`, `MEDIUM` | พัฒนาแล้ว | ทดสอบสร้างคำร้องและ History ซ้ำ |
| 4.2 | Student ดู/ค้นหา/กรอง, รายละเอียด, ยกเลิก `PENDING` | พัฒนาแล้ว | ทดสอบ Ownership/Cancel/History ซ้ำ |
| **4.3** | **Progress Timeline + Completion Evidence + Student Confirmation** | **DONE — VERIFIED (13 พ.ย. 2026)** | **Automated 35 checks + ผู้ใช้ยืนยัน UI/รูปอัปโหลด; Technician integration pending** |
| **5** | Admin: Dashboard, กำหนด Priority, มอบหมายช่าง, ห้องพัก | **DONE — VERIFIED (13 พ.ย. 2026)** | **ชุดทดสอบ 109 checks + ผู้ใช้ยืนยัน Browser UI ครบ** |
| **6** | Technician: งานที่ได้รับมอบหมาย, เริ่มงาน, รูปหลังซ่อม, ส่งยืนยัน | **DONE — VERIFIED (13 พ.ย. 2026)** | **189 automated checks + ผู้ใช้ยืนยัน UI ที่เหลือ** |
| **7** | Reports และ Integration/System Testing | **DONE — VERIFIED (13 พ.ย. 2026)** | **247 automated checks + ผู้ใช้ยืนยัน Desktop UI ผ่านแล้ว** |
| 8 | Desktop UI/UX Refinement | NOT STARTED | ปรับ UI ภายหลัง; Mobile ไม่เป็นเกณฑ์ทดสอบ |

> Phase 5–6 สามารถสลับลำดับหรือแบ่งงานเป็นช่วงสั้น ๆ ตาม Dependencies จริง โดยบันทึกเหตุผลที่นี่ก่อนปรับ Roadmap; ห้ามขยาย Scope เงียบ ๆ

## 3. Checklist ที่ปิดแล้ว — Phase 4.3 (ดู Current Phase 7 ในหัวข้อ 10)

### 3.1 Audit ก่อนเขียนโค้ด

- [x] อ่าน `AGENTS.md` และ `PROGRESS.md` ให้ครบ
- [x] สำรวจ Project Tree และไฟล์ PHP/CSS/JS ที่ใช้งานจริง
- [x] ตรวจ `config/database.php`, `includes/auth.php`, `student/create-request.php`, `student/my-requests.php`, `student/request-detail.php` และ `fixit.sql` (ถ้ามี)
- [x] ตรวจ Schema จริงของ `repair_request`, `repair_history`, `repair_assignment`, `user` และตาราง completion หากมี
- [x] ตรวจโค้ด/SQL ทดลอง Phase 4.3 ที่อาจค้าง; ระบุส่วนที่ใช้ได้หรือเป็นปัญหาโดยไม่ลบโดยพลการ
- [x] ตรวจปุ่ม Cancel `PENDING` เดิมใน `student/request-detail.php` และปัญหา helper `e()` ซ้ำ
- [x] สรุป Minimal Change Plan / Migration ก่อนลงมือแก้

### 3.2 Implementation

- [x] แสดง Progress Timeline ของเจ้าของคำร้องจากข้อมูล `repair_history` จริง
- [x] แสดงสถานะปัจจุบันและข้อมูลผู้รับงาน/หมายเหตุเท่าที่มีจริง
- [x] แสดงภาพหลักฐานหลังซ่อมแยกจากภาพที่นักศึกษาแจ้งปัญหา
- [x] มี Storage สำหรับการส่งงานของช่าง / การยืนยันของนักศึกษา โดยใช้ Schema เดิมให้มากที่สุด
- [x] หากเพิ่ม `repair_completion` ต้องตรวจชนิด FK ตรงกับ `repair_request.request_id` รวม signed/unsigned
- [x] เพิ่มปุ่มยืนยันเฉพาะเจ้าของคำร้อง เมื่อมีหลักฐานและสถานะพร้อมเท่านั้น
- [x] Confirmation ใช้ POST + CSRF + Role/Ownership/State Validation ฝั่ง Server
- [x] ใช้ Transaction เพื่ออัปเดต completion + status `COMPLETED` + history ให้สอดคล้องกัน
- [x] กันการยืนยันซ้ำ, Double Submit, คำร้อง `CANCELLED`, คำร้องที่ยังไม่มีหลักฐาน
- [x] รักษาการยกเลิกคำร้อง `PENDING` เดิมและลิงก์จาก `my-requests.php`

### 3.3 Tests / Acceptance Criteria

- [x] Student เจ้าของคำร้องเห็น Timeline ของตนเองได้
- [x] Student อื่นเปิดรายละเอียด/ส่ง POST ยืนยันงานของคนอื่นไม่ได้
- [x] Timeline แสดงเหตุการณ์จริง — เพิ่ม HTTP assertions หลายเหตุการณ์ต่างเวลา/เวลาเท่ากัน เรียง changed_at/history_id ผ่านแล้ว
- [x] คำร้อง `PENDING` หรือคำร้องไม่มีรูปหลังซ่อมยืนยันไม่ได้
- [x] งานที่ช่างส่งพร้อมรูปถูกต้อง เจ้าของคำร้องยืนยันได้
- [x] หลังยืนยัน สถานะเป็น `COMPLETED` พร้อมบันทึกผู้ยืนยัน/เวลา/History
- [x] ยืนยันซ้ำหรือยิงคำขอพร้อมกันไม่บันทึกซ้ำ
- [x] `CANCELLED` หรือสถานะไม่ถูกต้อง ยืนยันไม่ได้
- [x] กรณี Transaction ล้มเหลว ไม่มีข้อมูลค้างครึ่งทาง
- [x] Regression Phase 4.1–4.2: สร้าง/ดู/ค้นหา/กรอง/ยกเลิก `PENDING` ผ่าน automated tests; รูปอัปโหลดผ่านตามการยืนยันของผู้ใช้
- [x] ทดสอบ PHP syntax / SQL Migration ตามเครื่องมือที่ใช้งานได้
- [x] ระบุรายการที่ **ทดสอบไม่ได้จริง** อย่างชัดเจน (ห้ามติ๊กว่าเสร็จ)

### 3.4 หลักฐานที่ต้องบันทึกเมื่อปิด Phase 4.3

- รายชื่อไฟล์ที่สร้าง/แก้ พร้อมเหตุผล
- SQL Migration ที่สร้าง/แก้ และยืนยันว่าใช้ใน Database ใด
- ขั้นตอนทดสอบและผล **PASS / FAIL / NOT RUN** แยกรายการ
- ผลตรวจ Regression Phase 4.1–4.2
- ปัญหา/ข้อจำกัดคงเหลือ โดยเฉพาะถ้า Technician UI ยังไม่พร้อม
- วันที่ปิด Phase และ Phase ถัดไปที่ตกลงจะทำ

## 4. Known Issues / Risks ที่ต้องตรวจ

| ประเด็น | สถานะ/หมายเหตุ | วิธีจัดการ |
|---|---|---|
| MySQL Error 3823 ใน `user` CHECK Constraint | เคยแก้สำเร็จใน Phase 1 | ไม่รื้อ Schema เดิมโดยไม่มีเหตุผล |
| Port `3306` ถูกใช้โดย Service `mysql267` | เคยเชื่อมผ่าน MySQL Server นั้นได้ | อย่าเปิด MySQL ใน XAMPP ซ้ำโดยไม่ตรวจ Service |
| `Fatal error: Cannot redeclare e()` | ตรวจล่าสุดมี helper กลางใน auth.php จุดเดียว; lint/HTTP ผ่าน | ใช้ helper เดิมต่อ |
| MySQL Error 3780 ของ `fk_completion_request` | ตรวจ schema จริงล่าสุด: completion และ FK เป็น INT UNSIGNED ตรงกัน | ไม่เปลี่ยน PK; ตรวจ schema ก่อน migration ครั้งถัดไป |
| โค้ด/SQL ทดลอง Phase 4.3 | อาจค้างอยู่ ไม่ยืนยันว่ามีจริง | ตรวจ diff, schema และแยกจาก baseline ก่อนแก้ |
| ยังไม่มี Technician UI สำหรับส่งหลักฐานจริง | อาจทำให้ E2E Phase 4.3 ทดสอบไม่ครบ | ใช้ Test Data แยก Production; ระบุข้อจำกัด |

## 5. Roadmap หลัง Phase 4.3

**เมื่อ Phase 4.3 ผ่านการทดสอบจริงแล้ว ให้กำหนด Current Phase ใหม่เป็น Phase 5** และจัดลำดับงานร่วมกับ Dependencies จริง:

1. **Phase 5 — Admin:** จัดการคำร้องทั้งหมด, กำหนด Priority, มอบหมายช่าง, จัดการข้อมูลห้องพัก, Dashboard พื้นฐาน
2. **Phase 6 — Technician:** ดูงานที่ได้รับ, เริ่มงาน, บันทึกการซ่อม, อัปโหลดรูปหลังซ่อม, ส่งงานให้เจ้าของคำร้องยืนยัน
3. **Phase 7 — Reports & Integration:** รายงานตามสถานะ/หมวดหมู่/ช่าง และทดสอบวงจรแจ้งซ่อม → มอบหมาย → ซ่อม → ส่งรูป → ยืนยัน
4. **Phase 8 — UI/UX:** Responsive, Navigation, Layout, Dashboard, Component Consistency

หากทดสอบปลายทาง Phase 4.3 ต้องพึ่งพาฝั่ง Admin/Technician ที่ยังไม่มี UI ให้บันทึกว่าเป็น **Integration Pending** ไม่อ้างว่าทุก Role ผ่าน End-to-End แล้ว

## 6. ขั้นตอนอัปเดตเอกสารหลังจบทุก Phase (สำหรับ Codex)

1. เปิดอ่าน `AGENTS.md` + `PROGRESS.md` + โค้ด/Schema/Tests จริงทุกครั้งก่อนเริ่มงานใหม่
2. ทบทวนรายการงานและ Acceptance Criteria ของ Phase ปัจจุบันกับผลจริง
3. หากยังไม่ครบ ให้ใช้ `IN PROGRESS`, `IMPLEMENTED — NEEDS VERIFICATION` หรือ `BLOCKED` ตามจริง; **ห้ามเปลี่ยนเป็น DONE เพื่อให้ดูเรียบร้อย**
4. เมื่อผ่านครบ จึงอัปเดต Phase ปัจจุบันเป็น `DONE — VERIFIED` พร้อมวัน/หลักฐานทดสอบ, ไฟล์ที่เปลี่ยน, SQL และข้อจำกัด
5. ปรับ **Current Phase** และ Checklist ในไฟล์นี้ให้เป็นงาน Phase ถัดไป โดยเก็บบันทึกของ Phase เดิมใน Change Log
6. ทบทวน `AGENTS.md` เฉพาะกรณี Requirements/กฎถาวร/Architecture หรือคำสั่งเฉพาะ Phase เก่าขัดกับสถานะใหม่; อย่าลบข้อกำหนดเก่าเพียงเพื่อย่อไฟล์
7. ก่อนเริ่ม Phase ถัดไป ให้เสนอแผนตาม Dependencies; แยกงาน Phase ใหม่จากงานที่ยังไม่ผ่าน Verification
8. รายงานผู้ใช้ว่าทดสอบอะไรจริงและอะไรไม่ได้ทดสอบ พร้อมรายการไฟล์ที่แก้

### Template สำหรับ Change Log

```markdown
### YYYY-MM-DD — Phase X.Y
- สถานะ: DONE — VERIFIED / IN PROGRESS / BLOCKED / ...
- งานที่ทำ: ...
- ไฟล์ที่แก้/เพิ่ม: ...
- Database/Migration: ...
- Tests (PASS/FAIL/NOT RUN): ...
- ข้อจำกัด/งานค้าง: ...
- Phase ถัดไป: ...
```

## 7. Change Log

### 2026-11-13 — สร้างเอกสารส่งต่อให้ Codex
- สถานะ: Baseline จากประวัติสนทนา (ยังไม่ตรวจโค้ดจริงใน Workspace)
- Last Implemented Phase: 4.2
- Current Phase: 4.3 — NOT STARTED (เริ่มใหม่)
- Next Action: Inspect โค้ดและ Schema → ทำ Phase 4.3 → ทดสอบ → บันทึกผล → เตรียม Phase 5
- ไม่มีการอ้างว่ารัน Test หรือ Migration ใดในขั้นตอนสร้างเอกสารนี้

### 2026-11-13 — Phase 4.3 implementation และ automated verification
- สถานะ: IMPLEMENTED — NEEDS VERIFICATION; Current Phase ยังคง 4.3 ไม่เลื่อนไป Phase 5
- สิ่งที่พบ: ไม่มี `fixit.sql` และ `student/repair-progress.php` ใน disk แม้ IDE เปิดชื่อไฟล์นี้ไว้; ไม่มี completion table ก่อนเริ่มงาน; PK/FK จริงเป็น INT UNSIGNED; ทุกตารางเกี่ยวข้องใช้ InnoDB
- Baseline: helper `e()` อยู่ใน auth.php จุดเดียว; cancel เดิมใช้ CSRF + conditional UPDATE + transaction อยู่แล้ว; student dashboard และ admin/technician dashboard ยังมี placeholder (ไม่ขยาย scope)
- Minimal change: รวม Timeline/assignment/evidence/confirmation ใน request-detail.php; คง IN_PROGRESS ระหว่างรอยืนยัน; เพิ่ม repair_completion หนึ่งแถวต่อ request ไม่แก้ ENUM
- ไฟล์แก้: `student/request-detail.php` เพิ่ม timeline จาก history เรียง changed_at/history_id, ช่างที่ active, หลักฐาน/empty state, POST confirm, ownership ก่อน action, CSRF แยก และปุ่มยกเลิกเดิม
- ไฟล์แก้: `student/create-request.php` เอา unreachable redirect ออก หมุน CSRF token ก่อน redirect เดิม
- ไฟล์เพิ่ม: `includes/repair-completion.php` ตรวจไฟล์จริง MIME/extension/ขนาด/image signature, ช่างที่ได้รับมอบหมาย, row lock และ transaction completion/status/history
- ไฟล์เพิ่ม: migration SQL + CLI runner ใน `migrations/`; `uploads/completions/.htaccess` ป้องกัน execution และ directory listing; `tests/phase43.php`, router, concurrent worker, .htaccess/.gitignore และ README
- Database: รัน `migrations/apply-phase43.php` กับ `fixit` จริงสำเร็จ เพิ่มตารางว่าง `repair_completion`; รันซ้ำแล้วไม่เปลี่ยนข้อมูล; ไม่ลบข้อมูลเดิม ไม่แก้ PK/ENUM ไม่ seed คำร้องจริง
- Tests PASS: PHP syntax ทั้งโปรเจกต์, git diff --check, HTTP localhost login ตอบ 200
- Tests PASS: migration สร้างและรันซ้ำในฐานทดสอบ; FK ตรงชนิดจริง
- Tests PASS: owner timeline/escape HTML/ปุ่มยืนยัน, CSRF ผิด 403, other student GET/POST 404, technician ไม่ผ่าน role STUDENT, anonymous/logout redirect, login ผ่าน password_verify
- Tests PASS: ยืนยันสำเร็จแล้ว status COMPLETED พร้อม confirmed_by/confirmed_at/history; ปุ่มหายหลังยืนยัน; POST ซ้ำ 409
- Tests PASS: PENDING/CANCELLED/COMPLETED, รูปหาย และช่างไม่มี active assignment ถูกปฏิเสธ
- Tests PASS: บังคับ history INSERT ล้มเหลวด้วย trigger เฉพาะฐานทดสอบ แล้ว status และ confirmed_at rollback ทั้งหมด
- Tests PASS: สอง PHP processes ยืนยันแข่งกันบนแถวเดียวกัน สำเร็จหนึ่ง/ปฏิเสธหนึ่ง และ history COMPLETED มีหนึ่งแถว
- Tests PASS: สร้าง ROOM/AREA แบบไม่แนบรูป, ปลอม priority HIGH แล้วยังเป็น MEDIUM/PENDING, แสดงรายละเอียด, ยกเลิก PENDING, ค้นหา/กรอง CANCELLED
- รันล่าสุดหลังเพิ่ม validation action: `C:/xampp/php/php.exe tests/phase43.php` ผ่าน 34 checks ในฐาน `fixit_test_ccbfb9c0ee71`; รอบ concurrency ก่อนหน้า `fixit_test_6775835d0a8e` ผ่านเช่นกัน; มีรอบก่อนหน้า `fixit_test_2ffff15d2b1f` (HTTP ไม่ผ่านเพราะ test session directory เขียนไม่ได้ แก้ runner ให้ใช้ tests/sessions แล้ว), `fixit_test_d33fe8e30888` (ผ่านก่อนเพิ่ม concurrency)
- ฐานทดสอบทั้งหมดเก็บไว้แยกจาก fixit ไม่ DROP อัตโนมัติ; รูป seed ชั่วคราวถูกลบโดย runner หลังทดสอบ; การรันใหม่สร้างฐานใหม่เสมอ
- Tests NOT RUN: ตรวจ visual/responsive/JavaScript confirm ใน Browser; regression สร้างคำร้องพร้อม multipart image จริง; UI Technician ส่งงาน end-to-end (ยังไม่มีใน scope)
- ข้อจำกัด: ลิงก์ภาพเป็น static path เช่นรูปปัญหาเดิม ไม่มี authenticated image endpoint; Phase Technician ต้อง lock repair_request ก่อนเปลี่ยน assignment/completion และตรวจ role/ownership/CSRF/upload server-side พร้อมเขียน history ส่งงานใน transaction
- งานถัดไปใน Phase 4.3: ทดสอบ Browser ตาม tests/README.md และ regression รูปอัปโหลด ก่อนปิดเป็น DONE — VERIFIED; หลังผ่านจึงจัด Checklist Phase 5 Admin

### 2026-11-13 — ตรวจ readiness เพื่อส่งต่อ Phase 5 (รอบล่าสุด)
- ขอบเขตรอบนี้: ตรวจโค้ด/ฐานข้อมูล รัน tests เดิม และแก้เอกสารเท่านั้น ไม่เขียนโค้ด Phase 5 และไม่แก้ PHP/SQL ของระบบ
- ข้อสรุป: Phase 4.3 ยังเป็น IMPLEMENTED — NEEDS VERIFICATION; Current Phase คง 4.3 ไม่ระบุ DONE — VERIFIED และยังไม่มีวันที่ปิด Phase
- ฟังก์ชันและไฟล์ implementation: ใช้รายการใน Change Log implementation ข้างต้น ได้แก่ timeline/ช่าง/หลักฐาน/confirmation/cancel ใน student/request-detail.php, helper includes/repair-completion.php และ token rotation ใน student/create-request.php
- สิ่งต่างจากบันทึกก่อนหน้า: ตอนนี้พบ database/fixit.sql แล้ว; ไฟล์นี้ยังไม่มี repair_completion ต้องใช้ migration แยก ไม่รัน SQL baseline ทับฐานจริงเพื่อทดสอบ
- ตรวจ SHOW CREATE TABLE จริงใน fixit: repair_request, repair_history, repair_assignment, repair_completion, user, room; completion PK/FK เป็น INT UNSIGNED และ InnoDB ตรงกับ migration; ENUM สถานะยังมีสี่ค่า ไม่มี WAITING_CONFIRMATION
- Database Changes รอบนี้: ไม่มี migration ใหม่/ไม่รัน migration กับ fixit; ตรวจว่าตารางจาก migrations/20261113_repair_completion.sql มีอยู่จริงแล้ว; รอยืนยันใช้ IN_PROGRESS + completion ที่ยังไม่ confirmed
- Tests PASS: รัน C:/xampp/php/php.exe tests/phase43.php ได้ 34 checks ในฐานใหม่ fixit_test_bc29d5661706 โดยไม่แก้ข้อมูล fixit; seed รูปชั่วคราวถูกลบ ฐานทดสอบเก็บไว้ตาม runner เดิม
- Tests PASS: ownership GET/POST, role, CSRF, confirmation พร้อม audit/status/history, duplicate/concurrent confirmation, invalid state/missing photo/unassigned submitter, rollback หลัง history error, login/logout และสร้าง ROOM/AREA แบบไม่มีรูป/ค้นหา/กรอง/ยกเลิก
- Tests PASS: php -l ครบ 16 ไฟล์ PHP และ git diff --check ก่อนแก้เอกสาร; ตรวจซ้ำหลังแก้เอกสารด้วย diff --check
- Tests FAIL: ไม่มี check ที่รายงาน FAIL ในการรันรอบนี้; ไม่ตีความว่า coverage ครบ
- Tests NOT RUN: multipart upload รูปปัญหาจริงและการเปิดภาพผ่าน HTTP, visual/responsive/JavaScript confirm ใน Browser, Timeline หลายเหตุการณ์ต่างเวลาและเวลาเท่ากันแบบ end-to-end, workflow ทุก Role จาก UI (Technician ยังไม่พัฒนา)
- คุณภาพหลักฐาน: helper rejected() ใน runner ถือว่าทุก Throwable เป็นการปฏิเสธ จึงควรเพิ่ม assertion ว่าเป็น state/ownership rejection ที่คาดและข้อมูลไม่เปลี่ยนในแต่ละกรณีก่อนปิด acceptance ไม่ใช้จำนวน PASS อย่างเดียว
- ปัญหาคงเหลือ: ภาพเป็น static URL ตามระบบเดิม; migration runner พบตารางเดิมแล้วหยุด ไม่ตรวจว่า schema เดิมครบเอง; assignment ไม่มี unique constraint บังคับ active หนึ่งแถว จึงต้องออกแบบ concurrency ใน Phase 5
- ไฟล์เอกสารที่แก้รอบนี้: AGENTS.md (สถานะเริ่มงาน/ตำแหน่ง SQL เท่านั้น), PROGRESS.md (ผลตรวจล่าสุด งานค้าง Dependencies และ Checklist Phase 5)

### ผลปิด Phase 4.3 — 13 พฤศจิกายน 2026

- [x] ตรวจหน้าจอและ regression รูปอัปโหลด — ผู้ใช้ยืนยันในบทสนทนา ไม่ใช่ผลที่ Agent รัน Browser เอง
- [x] Timeline หลายเหตุการณ์ต่างเวลา/เวลาเท่ากัน — Agent เพิ่มและรัน HTTP assertion ผ่าน
- [x] Rejection ตรวจชนิด/ข้อความ error ที่คาด และข้อมูล request/completion/history ไม่เปลี่ยน — Agent เพิ่มและรันผ่าน
- [x] ชุดทดสอบ Phase 4.3 รวม 35 checks ผ่านใน fixit_test_2d5ac0610682 และรันซ้ำเป็นส่วนหนึ่งของ Phase 5 ผ่าน
- [x] ปิด Phase 4.3 เป็น DONE — VERIFIED; Current Phase เปลี่ยนเป็น 5 ตามคำสั่งผู้ใช้
- ขอบเขตหลักฐาน UI/รูป: รับรองตามที่ผู้ใช้ยืนยัน ไม่มีรายละเอียดผลแยก MIME/ขนาดจากผู้ใช้ จึงไม่อ้างว่า Agent รันทุกรูปแบบเอง; workflow UI ของ Technician ยัง Integration Pending

## 8. Checklist ที่ปิดแล้ว — Phase 5 Admin (DONE — VERIFIED)

### Dependencies และข้อตกลงที่ใช้

| ส่วน | สิ่งที่มีจริง/ต้องใช้ | ผลต่อแผน |
|---|---|---|
| Authentication | requireRole('ADMIN'), session และ e() เดิม | ทุกหน้า/POST ของ Admin ต้องตรวจ role ฝั่ง server ใช้ POST + CSRF; ไม่มี Login/Register/User Management ใหม่ |
| รายการคำร้อง | repair_request + category/room/common_area + history | เริ่มจากรายการ/รายละเอียดรวมทุกเจ้าของ พร้อมค้นหา/กรอง; ห้ามแก้ owner filter ของหน้านักศึกษาเพื่อใช้ซ้ำ |
| Priority | ENUM LOW/MEDIUM/HIGH; history ไม่มีคอลัมน์ priority | ใช้ allowlist และ transaction; บันทึกค่าเก่า/ใหม่ใน history.note โดย old_status=new_status ไม่เพิ่ม ENUM |
| Assignment | repair_assignment มี is_active แต่ไม่มี unique active/request | lock repair_request ก่อน แล้วปิด active เดิม/เพิ่มใหม่/history ใน transaction; ทุก writer ต้องใช้ลำดับ lock เดียวกัน |
| Technician | มีเฉพาะ dashboard placeholder | Phase 5 ทำ assignment contract ให้พร้อม; ไม่เริ่มงาน/ส่งหลักฐานแทนช่าง และไม่พึ่ง Technician UI เพื่อทดสอบ Admin |
| Completion | canConfirmRepair ต้องใช้ช่างผู้ส่งที่ยังมี active assignment และ assigned_at <= submitted_at | ห้ามเปลี่ยนช่างหลังส่งหลักฐาน เพราะจะทำให้ยืนยันเดิมไม่ได้; เก็บ completion เดิมทั้งหมด |
| ห้องพัก | user.room_id และคำร้องเดิมอ้าง room_id | แก้เฉพาะ room_number/floor/room_status ตาม schema ห้ามลบห้อง/ย้ายผู้พัก/แก้ PK; ตรวจชื่อซ้ำและผลของสถานะ AVAILABLE เมื่อมีผู้พัก |

ลำดับดำเนินงาน: 5.1 รายการ/รายละเอียด → 5.2 Priority/Assignment → 5.3 ห้องพัก/Dashboard → 5.4 Security/Regression ก่อน Phase 6 Technician และ Phase 7 รายงานเต็มรูปแบบ

กติกาที่ใช้ใน implementation: ให้มอบหมาย/เปลี่ยนช่างเฉพาะ PENDING โดยไม่เปลี่ยน status เป็น IN_PROGRESS; หลังเริ่มงานหรือมี completion ให้แสดง read-only เพื่อไม่ทำลาย confirmation contract การเปลี่ยน Priority ให้ทำเฉพาะ PENDING/IN_PROGRESS ที่ยังไม่ส่ง completion ส่วน COMPLETED/CANCELLED เป็น read-only; หากต้องรองรับ reassign หลังเริ่มงานในอนาคตให้กำหนดกติกาแยกพร้อม tests ก่อนเปิดใช้งาน

### Checklist Phase 5

- [x] ยืนยัน Phase 4.3 ผ่านเกณฑ์ปิดและเปลี่ยน Current Phase อย่างมีหลักฐาน
- [x] ตรวจ schema/ข้อมูล active assignment ซ้ำและสถานะห้องที่ขัดกับผู้พัก โดยไม่แก้ข้อมูลจริงอัตโนมัติ
- [x] 5.1 แสดงคำร้องทุกเจ้าของ ค้นหาเลข/หัวข้อ กรองสถานะ/Priority/หมวดหมู่/ช่าง และ empty state
- [x] 5.1 แสดงรายละเอียด ผู้แจ้ง สถานที่ ประวัติ ช่างและหลักฐาน โดย reuse helper ที่เหมาะสม ไม่ include หน้า Student ที่มี side effects
- [x] 5.2 เปลี่ยน Priority ด้วย POST/CSRF/role/allowlist/state validation และ history แบบ transaction
- [x] 5.2 มอบหมายเฉพาะบัญชี TECHNICIAN ที่ active; ตรวจ request/technician ใหม่ใต้ lock และกันส่งซ้ำ/no-op
- [x] 5.2 เปลี่ยนช่าง PENDING โดยเก็บ assignment เก่า ปิด is_active เดิม ให้เหลือ active หนึ่งแถว และ history ครบ
- [x] 5.2 ปฏิเสธ assignment/priority ที่ไม่อยู่ในกติกา รวม terminal state/ส่ง completion แล้ว และแข่งกับ cancel/confirmation อย่างปลอดภัย
- [x] 5.3 ดู/แก้ข้อมูลห้องด้วย validation, CSRF และ uniqueness; รักษาความสัมพันธ์ผู้พัก/คำร้องเดิม
- [x] 5.3 Dashboard นับสถานะ/Priority/งานไม่มอบหมาย และรอยืนยันจากข้อมูลจริง ไม่ double-count เมื่อ join assignment
- [x] 5.4 ทดสอบ anonymous/STUDENT/TECHNICIAN เข้า Admin GET/POST ไม่ได้ และ invalid CSRF ไม่เปลี่ยนข้อมูล
- [x] 5.4 ทดสอบ Priority/assign/reassign/rollback/concurrency/history/no-op และห้องเลขซ้ำ/ข้อมูลไม่ถูกต้อง
- [x] 5.4 Regression Student แบบไม่มีรูป/ค้นหา/กรอง/ยกเลิก/Timeline/confirmation หลัง Admin assignment ผ่าน automated; รูปอัปโหลด baseline รับตามผู้ใช้ยืนยัน
- [x] บันทึก migration เฉพาะเมื่อพบว่าจำเป็น; ไม่เปลี่ยน ENUM/PK หรือเพิ่มตารางซ้ำโดยไม่มีเหตุผล
- [x] บันทึก PASS/FAIL/NOT RUN และ Technician Integration Pending ก่อนปิด Phase 5

- [x] ตรวจ UI Desktop: navigation/รายละเอียด/รูปปัญหา/ฟอร์ม Priority/Assignment/ห้องพัก; มือถือ 400px: ฟอร์มห้อง/ตัวกรอง/Empty State และปัดตารางคำร้องแนวนอน
- [x] Browser UI ฝั่ง Desktop รวมรายการค้าง pagination/ฟอร์ม error-success — ผู้ใช้ยืนยันว่าตรวจครบและผ่าน; Agent ไม่ได้รันส่วนนี้ซ้ำ
- Mobile: ไม่เป็นข้อกำหนดและไม่เป็นเกณฑ์ปิด Phase ตามคำสั่งผู้ใช้ล่าสุด
- [x] ปิด Phase 5 เป็น DONE — VERIFIED และเปลี่ยน Current Phase เป็น 6 เพื่อร่างแผน; ยังไม่เขียนโค้ด Phase 6

### แผนไฟล์และผลดำเนินงาน Phase 5

| ไฟล์ | การเปลี่ยนแปลงที่วางแผน |
|---|---|
| admin/dashboard.php | แก้ placeholder เป็นตัวเลขและ navigation จริง |
| admin/requests.php | สร้างหน้ารายการ ค้นหาและกรองทุกคำร้อง |
| admin/request-detail.php | สร้างรายละเอียดและ POST actions ของ Priority/Assignment |
| admin/rooms.php | สร้างรายการห้องและฟอร์มแก้ข้อมูลในหน้าเดียว |
| includes/repair-admin.php | เพิ่ม helper validation/CSRF/transaction สำหรับ Priority/Assignment/ห้อง |
| includes/admin-header.php | เพิ่มส่วนหัว/navigation ที่ใช้ร่วมกันเฉพาะ Admin พร้อม guard ป้องกันเปิด partial โดยตรง |
| includes/repair-completion.php | reuse โดยไม่แก้ production helper; Phase 4.3 regression ผ่าน |
| assets/css/style.css | เพิ่มเฉพาะ component ที่ Admin ต้องใช้ คงรูปแบบเดิม |
| tests/phase5.php, tests/router.php | เพิ่ม isolated Admin tests และ routes ทดสอบ |
| tests/README.md, PROGRESS.md | บันทึกวิธีทดสอบ ผลจริง และ checkpoint แต่ละช่วง |
| migrations/<date>_<purpose>.sql | สร้างเฉพาะเมื่อ audit พบความจำเป็น พร้อมแผนข้อมูลเดิม; ปัจจุบันยังไม่เสนอ DDL ใหม่ |

ใช้ includes/auth.php, config/database.php และ student pages เดิมเป็น dependencies ไม่วางแผนสร้างระบบ auth ใหม่; ยังไม่แก้ technician/dashboard.php หรือสร้าง Technician endpoints ใน Phase 5

### 2026-11-13 — ปิด Phase 4 และพัฒนา Phase 5 Admin
- Phase 4.3: DONE — VERIFIED โดยแยกหลักฐานผู้ใช้ยืนยัน UI/รูปอัปโหลดออกจาก Agent automated tests 35 checks ชัดเจน
- Phase 5: IMPLEMENTED — NEEDS VERIFICATION; ชุดทดสอบรวม 109 checks ผ่าน (Phase 4.3 จำนวน 35 + Admin จำนวน 74) ไม่เลื่อนไป Phase 6 เพราะยังไม่ได้ตรวจ UI Admin
- Audit DB จริง: SHOW CREATE TABLE ทั้ง request/history/assignment/completion/room/user; ไม่พบ active assignment ซ้ำ หรือห้อง AVAILABLE ที่มี active Student (ทั้งสอง query นับได้ 0)
- พัฒนา: admin/requests.php รายการทุกเจ้าของ + ค้นหาเลข/หัวข้อ + กรอง status/Priority/category/technician/unassigned/open/waiting + pagination 25 แถว; request-detail.php รายละเอียด/assignment history/Timeline/หลักฐาน/Priority/assign POST; rooms.php ค้นหาและแก้ข้อมูลห้อง; dashboard.php สรุปสถานะ/ความสำคัญ/รอมอบหมาย/รอยืนยัน/หมวดหมู่
- Helper: includes/repair-admin.php ตรวจ Role/CSRF/validation และ transaction; includes/admin-header.php ใช้ navigation ร่วม; CSS เพิ่มเฉพาะ .admin-page และ component ใหม่ไม่ redesign หน้า Student
- Business rules: เปลี่ยนช่างเฉพาะ PENDING ไม่มี completion; Priority แก้ PENDING/IN_PROGRESS ไม่มี completion; Admin ไม่เริ่ม/ปิดงานแทนช่างหรือนักศึกษา; no-op ไม่เพิ่ม history; assignment เก่าเก็บไว้โดย is_active=0; lock request ก่อน completion/assignment/history
- ห้อง: ไม่ลบห้อง ไม่ย้ายผู้พัก ไม่เปลี่ยน PK; ตรวจ room_number ซ้ำ/floor 0–255/status และห้าม AVAILABLE ถ้ามี active Student
- Database Changes: ไม่มี schema migration ใหม่ใน Phase 5 ใช้ 8 ตารางเดิมรวม repair_completion จาก migration Phase 4.3; ไม่แก้ข้อมูลจริง fixit ในการทดสอบ
- Tests PASS: 109 checks ใน tests/phase5.php รันกับฐานแยก fixit_test_95b28de9edfb (ผลล่าสุดอยู่ใน tests/phase5-last-run.log ซึ่งไม่ track ใน Git); ทุกฐานทดสอบคงอยู่ รูป temporary ถูกลบเฉพาะของ runner
- Tests PASS: Priority/audit/no-op; assign/reassign/inactive technician/wrong role/invalid id; terminal/completion rejection พร้อม snapshot ข้อมูลไม่เปลี่ยน; trigger-induced rollback ทั้ง Priority/Assignment; room uniqueness/validation/resident constraint/foreign keys
- Tests PASS: concurrent assign แบบช่างคนเดียวกันสำเร็จหนึ่งครั้ง history หนึ่งแถว; assign/cancel race คง CANCELLED; anonymous/STUDENT/TECHNICIAN เข้า Admin GET/POST ไม่ได้; Admin HTTP GET/POST, CSRF, XSS escaping, malformed filters, empty state, Dashboard count, open/waiting/technician filters
- Tests PASS: ปลอม POST เปลี่ยนช่างหลังมีหลักฐานไม่ได้ และ Admin ปลอม action confirm ปิดงานข้ามนักศึกษาไม่ได้; Admin assignment → จำลอง Technician เฉพาะฐานทดสอบ → Student เจ้าของยืนยันได้จริง
- Tests PASS: PHP lint ทั้ง 23 ไฟล์; git diff --check
- Tests FAIL: ไม่มีในรอบล่าสุด
- Tests NOT RUN: Browser visual/responsive ของ Admin, pagination หลายหน้าแบบ Browser, workflow Technician UI ทั้งระบบ (ยังไม่มี UI); Computer Use ตอบว่าไม่ได้รับอนุญาตให้ใช้ Chrome จึงหยุด UI automation และไม่อ้างว่าตรวจหน้าจอ Admin แล้ว
- Verification เพิ่มเติมที่ไม่ปิดกั้น feature: ยังไม่มี unique constraint บังคับ active assignment หนึ่งแถว ต้องให้ future Technician/Admin writers ใช้ request row lock เหมือนกัน; ภาพยังเป็น static URL ตามเดิม; ห้องไม่มี audit table เฉพาะ (scope ปัจจุบันไม่กำหนด)
- ข้อจำกัดทดสอบรูปในรอบนี้: Agent ไม่ได้ส่ง multipart upload ซ้ำ รับ baseline การตรวจรูปจากผู้ใช้; ไม่มีการแก้โค้ด upload หรือ CSS ของ Student
- อ่านวิธีทดสอบ Browser/คำสั่ง rerun ใน tests/README.md; งานถัดไปปิด UI verification Phase 5 แล้วจึงเตรียม Phase 6 Technician

### 2026-11-13 — Chrome UI verification และแก้ตารางมือถือ
- สถานะ: Phase 5 ยังคง IMPLEMENTED — NEEDS VERIFICATION; ไม่เลื่อน Phase และไม่แก้ Requirements ใน AGENTS.md
- Computer Use: เชื่อม Chrome แบบ native app ได้ในรอบนี้ แม้ browser access host ยัง unavailable; ตรวจแท็บ localhost/fixit ที่ผู้ใช้เปิดไว้ ไม่แก้ account permissions หรือ config ของ Chrome
- Browser PASS: เปิดรายละเอียดคำร้อง #1 ตรวจข้อมูล/รูปปัญหา/ฟอร์ม Priority-Assignment/Empty State หลักฐาน, เปิดรายการห้องและฟอร์มแก้ห้อง #2 โดยไม่ส่ง POST ข้อมูลจริง
- Browser PASS: จำลอง Responsive กว้าง 400px ใน Chrome DevTools ตรวจเมนู ฟอร์มห้อง ตัวกรองคำร้อง ค้นหาคำที่ไม่มีผล แล้วแสดง 0 คำร้องและ Empty State ถูกต้อง
- Browser FAIL ที่พบและแก้แล้ว: ตารางคำร้องบนมือถือบีบข้อความไทยจนแตกทีละตัว; เพิ่ม class admin-request-table/admin-room-table และ min-width 900px/640px ให้ตารางใช้ horizontal scroll ภายใน wrapper เดิม ไม่เปลี่ยนหน้า Student
- Browser PASS หลังแก้: hard reload CSS แล้วเห็นหัวข้อ/ข้อมูลอ่านเป็นคำ ปัดตารางจากซ้ายไปขวาจนเห็นคอลัมน์ช่างและวันที่ได้จริง; คืนขนาด Desktop และปิด DevTools แล้ว
- ไฟล์แก้รอบนี้: admin/requests.php, admin/rooms.php (class ของตาราง), assets/css/style.css (ความกว้างขั้นต่ำเฉพาะสองตาราง), PROGRESS.md และ tests/README.md (ผลตรวจ)
- Tests PASS หลังแก้: C:/xampp/php/php.exe tests/phase5.php รวม 109 checks ในฐาน fixit_test_afe62470578c; lint สอง PHP ที่แก้และ git diff --check ผ่าน
- Database Changes: ไม่มี schema/data migration; Browser ใช้ GET อย่างเดียว ไม่บันทึก Priority/assignment/ห้องจริง; ปิด temporary server loopback ที่เคยเตรียมทดสอบแล้ว
- Tests NOT RUN รอบนี้: มือถือหน้า Dashboard/รายละเอียดคำร้องครบ flow, การเลื่อนตารางห้องหลังแก้ class, Browser pagination หลายหน้า, Browser POST success/error ในฐานแยก และ Technician UI end-to-end
- งานถัดไป: ตรวจรายการ NOT RUN ของ UI เพื่อปิด Phase 5; ผล backend/HTTP POST ยังอ้างอิงชุดทดสอบ 109 checks ที่ผ่านจริง

### 2026-11-13 — ผู้ใช้รับรอง UI และปิด Phase 5
- สถานะ: DONE — VERIFIED; Current Phase เปลี่ยนเป็น 6 — Technician (PLANNED — NOT STARTED)
- หลักฐาน PASS (ผู้ใช้): ผู้ใช้ระบุว่าตรวจ Browser UI ทั้งหมดแล้วผ่าน รวมงาน UI ที่ยังค้างใน checklist; เป็นผล UAT ที่ผู้ใช้ยืนยัน ไม่ใช่ผล Agent รันใหม่
- หลักฐาน PASS (Agent ก่อนหน้า): automated 109 checks ใน fixit_test_afe62470578c; PHP lint และ diff --check; Chrome UI ที่ตรวจเองตาม Change Log รอบก่อน
- FAIL: ไม่มีรายการค้างที่รายงานในผลล่าสุด; ปัญหาตารางบีบข้อความที่พบก่อนหน้าแก้และตรวจซ้ำแล้ว
- NOT RUN รอบเอกสารนี้: ไม่รันทดสอบแอปซ้ำ/ไม่ทดสอบ Phase 6; Mobile ไม่ต้องทดสอบตามขอบเขตใหม่ ไม่ถือเป็น blocker หรือ PASS ที่แต่งขึ้น
- Database Changes: ไม่มี SQL/schema/data changes; Phase 5 ไม่มี migration ใหม่ ใช้ repair_completion จาก Phase 4.3 เดิม
- ฟังก์ชัน/ไฟล์ Phase 5: รายการ/ตัวกรอง/pagination ใน admin/requests.php, รายละเอียด/Priority/Assignment ใน admin/request-detail.php, ห้องใน admin/rooms.php, สรุปใน admin/dashboard.php; helper includes/repair-admin.php, includes/admin-header.php, CSS และ tests ตามหัวข้อ 8/Change Log เดิม
- ปัญหาคงเหลือที่ส่งต่อ: Technician UI ยัง placeholder จึงยังไม่ผ่านทุก Role จาก UI จริง; ภาพเป็น static URL และ active assignment ไม่มีกฎ unique ใน DB ต้องรักษา locking contract; ไม่ใช่การอ้างว่า Phase 6/7 ผ่านแล้ว
- ขอบเขตใหม่: Desktop เป็นหลัก ไม่ต้องทดสอบ Mobile/Responsive มือถือ ไม่ต้องรื้อ CSS Responsive เดิม
- รอบนี้แก้เอกสาร AGENTS.md, PROGRESS.md, tests/README.md เท่านั้น; ยังไม่เขียนโค้ด Phase 6

## 9. Checklist ที่ปิดแล้ว — Phase 6 Technician (DONE — VERIFIED)

### เป้าหมายและ Dependencies

ทำให้ช่างที่ได้รับมอบหมายดูงาน เริ่มงาน บันทึกความคืบหน้า และส่งรูปหลังซ่อมให้นักศึกษายืนยันผ่าน UI จริง โดยใช้ Login/Session/requireRole/e() เดิม เชื่อม assignment จาก Admin และ confirmation เดิมของ Student

| ส่วนที่มีอยู่ | สิ่งที่ Phase 6 ต้องใช้/รักษา |
|---|---|
| technician/dashboard.php | แทน placeholder ด้วยข้อมูลเฉพาะงานของช่างที่ login แล้ว |
| repair_assignment | อ่านเฉพาะ is_active=1 ของช่างปัจจุบัน; recheck ใต้ lock ทุก POST ป้องกันหน้าเก่าหลังเปลี่ยนช่าง |
| repair_request | เริ่มได้เฉพาะ PENDING → IN_PROGRESS; ช่างไม่เปลี่ยน Priority/เจ้าของ/สถานที่ และไม่ตั้ง COMPLETED |
| repair_history | เริ่มงานบันทึก PENDING → IN_PROGRESS; progress/ส่งหลักฐานบันทึก IN_PROGRESS → IN_PROGRESS พร้อมผู้ทำรายการ/หมายเหตุจริง |
| repair_completion | ใช้ตารางเดิมหนึ่งแถวต่อ request เก็บช่างผู้ส่ง ภาพ หมายเหตุ submitted_at; ห้ามเขียน confirmed_by/confirmed_at แทนนักศึกษา |
| includes/repair-completion.php | reuse การอ่าน/ตรวจภาพ; ส่งไฟล์ใน uploads/completions ให้ผ่าน MIME/extension/ขนาดและ image signature ตาม contract เดิม |
| includes/repair-admin.php | Admin เปลี่ยนช่างได้เฉพาะ PENDING ก่อนมี completion; Technician ต้องใช้ lock order เดียวกันและรักษา history |

### Workflow ที่เสนอ

1. ช่างดูรายการ/รายละเอียดเฉพาะ active assignment ของตน รวมสถานะ Priority สถานที่ รายละเอียด รูปปัญหา และ Timeline จากข้อมูลจริง
2. กดเริ่มงานด้วย POST + CSRF; lock repair_request ก่อน แล้วตรวจ role/บัญชี active/assignment/state ซ้ำ เปลี่ยนสถานะและเพิ่ม history ใน transaction เดียว
3. เมื่อ IN_PROGRESS และยังไม่ส่ง completion ให้บันทึกหมายเหตุความคืบหน้าได้โดยไม่เปลี่ยนสถานะ; ไม่ใช้บันทึกเดียวกันทับ history เดิม
4. ส่งงานต้องมีรูป JPG/PNG/WEBP จริงไม่เกิน 5 MB และหมายเหตุ optional; ตรวจ upload server-side, สุ่มชื่อไฟล์, เก็บใน uploads/completions และคง .htaccess ป้องกัน script execution
5. ใน transaction ตรวจ assignment/state/ไม่มี completion ซ้ำใต้ request lock จากนั้นเพิ่ม completion + history ส่งงาน คง request.status=IN_PROGRESS และ completed_at=NULL
6. หลังส่งแสดงรอนักศึกษายืนยันและข้อมูลอ่านอย่างเดียว ไม่เปิดแก้/ส่งหลักฐานซ้ำใน scope นี้; Student เจ้าของยืนยันผ่าน logic เดิมจึง COMPLETED
7. งาน CANCELLED/COMPLETED เป็นอ่านอย่างเดียวสำหรับช่างที่ยังมี active assignment; งานที่ถูกเปลี่ยนช่างแล้วเปิด/POST ไม่ได้ ไม่เพิ่มงาน Reject/Reopen/ส่งใหม่โดยไม่มี requirement

การจัดการไฟล์กับ transaction: หากย้ายไฟล์แล้ว DB/state validation ล้มเหลว ลบเฉพาะไฟล์ที่สร้างในคำขอครั้งนั้น ห้ามลบรูปเดิม; ตรวจ duplicate ก่อนเขียนและกัน concurrency ผ่าน request row lock + completion PK; ใช้ POST/Redirect/GET กัน refresh ส่งซ้ำ และแยก retry ข้อผิดพลาดออกจากการส่งงานใหม่

### Checklist และลำดับพัฒนา

- [x] 6.0 Inspect โค้ด/schema จริง/SQL baseline/migration และทบทวน contract assignment/completion; ไม่เพิ่ม schema หากตารางเดิมรองรับ
- [x] 6.1 Dashboard และรายการงานเฉพาะช่าง ค้นหา/กรองสถานะ/Priority/หมวดหมู่ แบ่งหน้าและ Empty State ตามจำเป็น
- [x] 6.1 รายละเอียดงาน/รูปปัญหา/Timeline/ข้อมูลมอบหมาย พร้อม server-side Role/Ownership ทั้ง GET และ POST
- [x] 6.2 เริ่มงาน PENDING → IN_PROGRESS และ history แบบ atomic; กันเริ่มซ้ำ/งานคนอื่น/งานยกเลิก/assignment หมดสิทธิ์
- [x] 6.2 บันทึก progress note เฉพาะ IN_PROGRESS ที่ยังไม่ส่ง completion พร้อม CSRF/validation/escape output
- [x] 6.3 Upload หลักฐานบังคับ ตรวจ MIME+extension+ขนาด+image signature+is_uploaded_file และ path ปลอดภัย ใช้ storage เดิม
- [x] 6.3 บันทึก completion + history ส่งงานแบบ transaction โดยไม่ปิดงานและไม่เติมข้อมูลยืนยันแทน Student
- [x] 6.3 กัน double submit/ส่งพร้อมกัน/หลักฐานซ้ำ และ cleanup ไฟล์ใหม่เมื่อ validation/transaction ล้มเหลว
- [x] 6.4 ทดสอบ unauthorized roles/ช่างอื่น/ช่างเดิมหลัง reassign/CSRF และ input ผิด พร้อมตรวจข้อมูลไม่เปลี่ยน
- [x] 6.4 ทดสอบเริ่มซ้ำ/หมายเหตุ/อัปโหลดถูก-ผิด-ไม่มีรูป-เกินขนาด/state ไม่ถูกต้อง/terminal state พร้อม audit
- [x] 6.4 ทดสอบ rollback และ race: start vs cancel/reassign, submit พร้อมกัน, submit vs note; active assignment/completion/history ต้องสอดคล้อง
- [x] 6.4 Regression Phase 4–5 และ workflow ผ่าน HTTP จริง: Student สร้าง → Admin มอบหมาย → Technician เริ่ม/บันทึก/ส่งรูป → Student ดูรูปและยืนยัน → ทุก Role เห็น COMPLETED
- [x] 6.4 ตรวจ Browser UI บน Desktop: ผู้ใช้ยืนยันว่าตรวจเรียบร้อยแล้ว; ไม่ทดสอบ Mobile
- [x] อัปเดต PROGRESS.md พร้อม PASS/FAIL/NOT RUN และไฟล์/migration/ข้อจำกัดจริง ก่อนปิด Phase 6; Phase 7 ทำรายงานและ Integration เพิ่มเติม

- [x] Desktop Chrome flow หลัก: Dashboard/list/detail/start/progress/upload/success/รอยืนยันอ่านอย่างเดียว/Timeline
- [x] Desktop UI ที่เหลือ: ผู้ใช้ยืนยันการตรวจแล้ว แยกจากผล HTTP ของ Agent
- [x] ปิด Phase 6 และเปลี่ยน Current Phase เป็น 7 เพื่อวางแผน ยังไม่เริ่มโค้ด Phase 7

### ไฟล์ Phase 6 และแผนที่ใช้

| ไฟล์ | แผน |
|---|---|
| technician/dashboard.php | แก้ placeholder เป็นสรุปงานเฉพาะช่างและ navigation |
| technician/my-jobs.php | เพิ่มรายการ ค้นหา/กรอง/แบ่งหน้า งานของตน |
| technician/job-detail.php | เพิ่มรายละเอียดและ POST actions เริ่มงาน/progress/ส่งหลักฐานในหน้าเดียว |
| includes/repair-technician.php | เพิ่ม helpers validation/assignment ownership/transaction และจัดการ upload หากช่วยลดความซ้ำ |
| includes/technician-header.php | เพิ่มส่วนหัวร่วมเฉพาะเมื่อหลายหน้าจำเป็นต้องใช้ ไม่สร้าง auth ใหม่ |
| includes/repair-completion.php | reuse เดิม; แก้เฉพาะหากต้องแชร์ upload validation พร้อม regression Student confirmation |
| uploads/completions/.htaccess | ตรวจและคงการป้องกันไฟล์ executable; ไม่เปลี่ยน policy โดยไม่จำเป็น |
| assets/css/style.css | เพิ่ม component ที่จำเป็นเฉพาะ Technician โดยเน้น Desktop ใช้รูปแบบเดิม |
| tests/phase6.php, tests/router.php | เพิ่ม isolated integration/security/upload/concurrency tests และ routes |
| tests/README.md, PROGRESS.md | ขั้นตอนทดสอบ Desktop/seed/cleanup และหลักฐานผลจริง |
| migrations/<date>_<purpose>.sql | เพิ่มเฉพาะเมื่อ audit พบความจำเป็น ไม่เปลี่ยน PK/ENUM ไม่ลบข้อมูลจริง |

### เกณฑ์ปิด Phase 6

Checklist งานที่จำเป็นต้องผ่านจริงบน Desktop; มีหลักฐานว่า Technician ไม่เห็น/แก้งานของผู้อื่น ไม่ปิดงานข้าม Student และส่งหลักฐานแล้ว Student ยืนยันได้แบบ atomic พร้อม regression Admin/Student ผ่าน หากยังมีส่วนสำคัญ NOT RUN ให้ระบุ IMPLEMENTED — NEEDS VERIFICATION ห้ามอ้าง DONE — VERIFIED; Mobile ไม่อยู่ในเกณฑ์นี้

### 2026-11-13 — Phase 6 implementation และ verification

- สถานะ: IMPLEMENTED — NEEDS VERIFICATION; ยังไม่เริ่ม Phase 7
- Audit: ตรวจ schema จริง request/assignment/history/completion, auth, Admin transaction, Student confirmation และ .htaccess; ใช้ INT UNSIGNED/InnoDB และสี่สถานะเดิม
- ไฟล์เพิ่ม: technician/my-jobs.php (ownership/search/filters/pagination/empty state), technician/job-detail.php (รายละเอียด/assignment/รูป/Timeline/POST actions), includes/repair-technician.php (CSRF/ownership/upload/transaction), includes/technician-header.php (navigation/guard)
- ไฟล์แก้: technician/dashboard.php แทน placeholder ด้วยจำนวนงานเฉพาะช่าง; reuse CSS/components เดิมและ ID/text/status/Priority helpers จาก repair-admin.php โดยไม่เรียก Admin mutations ในหน้า Technician ไม่สร้าง e()/auth ซ้ำ
- Security: requireRole ทุกหน้าและ owner GET/POST; transaction ล็อก request ก่อน ตรวจ active user และ active assignment เพียงหนึ่งแถวตรงผู้ทำรายการซ้ำ; CSRF + single-use action token ต่อคำร้องกัน replay progress form, PRG หลังสำเร็จ
- Workflow: start PENDING → IN_PROGRESS; progress เพิ่ม history; submit บังคับรูป เพิ่ม completion+history คง IN_PROGRESS ไม่แตะ completed_at/confirmed fields; หลังส่ง/terminal state เป็น read-only
- Upload: is_uploaded_file/error/MIME/extension/getimagesize/ขนาดจริง <=5 MB; ชื่อสุ่ม 32 hex ใน uploads/completions; rollback ลบเฉพาะรูปใหม่ในคำขอนั้น ไม่ลบหลักฐานเก่า; PHP CLI upload/post limit ปัจจุบัน 40M
- Database Changes: ไม่มี DDL/migration ใหม่ ไม่เปลี่ยน ENUM/PK ไม่ seed หรือแก้ข้อมูลจริง fixit
- Tests เพิ่ม: tests/phase6.php, tests/technician-worker.php, tests/upload-fixtures.php; router เพิ่ม Technician routes/ภาพสำหรับ preview; .gitignore เพิ่ม phase6-last-run.log
- PASS: tests/phase6.php ล่าสุด 189 checks (regression เดิม 109 + Technician 80) ใน fixit_test_c68547590cb8
- PASS: Role/ownership/stale assignment/CSRF/form replay/state/note validation/XSS/ห้ามช่างปิดงานเอง; history บันทึกผู้ทำรายการถูกต้อง
- PASS: multipart PNG/JPEG/WEBP ผ่าน HTTP จริง; ไม่มีรูป/ไฟล์ปลอม/script extension/MIME ไม่ตรง/เกินขนาด ถูกปฏิเสธโดย DB/ไฟล์ไม่เปลี่ยน
- PASS: trigger-induced rollback ของ start/progress/submit; submit rollback ลบรูปใหม่ ไม่มีข้อมูลค้างครึ่งทาง
- PASS: start/start, start/cancel, start/reassign ผ่านสอง processes; submit/submit และ submit/progress ผ่านสอง HTTP servers และสอง sessions แข่ง request lock ได้ completion/รูป/history ส่งงานเพียงหนึ่งครั้ง
- PASS: pagination 30 งานแบ่ง 25+5 คงตัวกรอง, empty state และ malformed input ไม่ crash
- PASS: workflow ทุก Role ผ่าน endpoints จริง Student สร้าง → Admin มอบหมาย → Technician เริ่ม/บันทึก/อัปโหลด → Student ดูรูปและยืนยัน; ทุก Role อ่าน COMPLETED มี history หกเหตุการณ์
- FAIL ระหว่างพัฒนาที่แก้แล้ว: test assertion คาด query encoding %20 แต่ http_build_query ใช้ + ถูกต้อง; แก้ assertion และรันใหม่ผ่าน ไม่ใช่บั๊กตัวกรอง
- Desktop Chrome PASS: desktop_tech/งาน #33 ในฐานแยก fixit_test_0e0e0bb1783f แสดง Dashboard/list/detail, กด start/progress/เลือกไฟล์/upload ผ่านจริง มี success และสถานะรอยืนยัน พร้อม Timeline และ read-only หลังส่ง
- NOT RUN: Desktop Browser filters/pagination/error/unauthorized states ให้ครบ และทุก Role ผ่าน UI ทั้งชุด; HTTP กรณีเหล่านี้ผ่านแล้ว; ไม่ต้องทดสอบ Mobile
- Preview server loopback ปิดแล้ว; รูป fixture/รูป upload ของ preview ลบเฉพาะที่สร้างเอง ฐานทดสอบคงอยู่เพื่อดู audit แต่รูปชั่วคราวไม่คงอยู่
- ข้อจำกัด: ภาพยังเป็น static URL; ไม่มี Reject/Reopen/แก้หลักฐานหลังส่ง; assignment หลาย active แถวถูกปฏิเสธไม่แก้ข้อมูลจริงอัตโนมัติ
- งานถัดไป: ตรวจ Desktop UI รายการ NOT RUN แล้วบันทึกหลักฐานก่อนปิด Phase 6

### 2026-11-13 — ปิด Phase 6 และวางแผน Phase 7

- Phase 6: DONE — VERIFIED โดยผู้ใช้ยืนยันว่าตรวจเรียบร้อยแล้ว รวม UI ที่ค้าง; ไม่อ้างว่า Agent รัน UI ส่วนดังกล่าวเอง
- หลักฐานก่อนหน้า: automated 189 checks ใน fixit_test_c68547590cb8, PHP lint 30 ไฟล์ และ Desktop Chrome flow หลักตามบันทึก implementation
- PASS: รับผล UI/UAT ตามคำยืนยันของผู้ใช้; FAIL: ไม่มีรายการค้างที่รายงานล่าสุด; NOT RUN รอบนี้: ไม่รันทดสอบแอปซ้ำ/ไม่ทดสอบ Phase 7 เพราะแก้เฉพาะเอกสาร
- Current Phase: 7 — Reports & Integration, PLANNED — NOT STARTED
- การแบ่งงานถาวร: ผู้ใช้ตรวจ Desktop UI เอง Agent เตรียม Checklist และรัน backend/HTTP/DB/security/integration tests; Mobile ไม่ต้องทดสอบ
- Database Changes: ไม่มี migration/schema/data changes ในรอบนี้
- ข้อจำกัดส่งต่อ: static image URLs, ไม่มี unique active assignment ใน DB, ไม่มี Reject/Reopen หรือแก้หลักฐานหลังส่ง; ไม่เพิ่มฟังก์ชันเหล่านี้เงียบ ๆ ใน Phase 7
- ไฟล์เอกสารที่แก้: AGENTS.md, PROGRESS.md, tests/README.md; ยังไม่เขียนโค้ด Reports หรือ tests Phase 7

## 10. Phase 7 — Reports & Integration (DONE — VERIFIED)

### เป้าหมายและขอบเขต

ให้ Admin ดูรายงานจากข้อมูลจริงตามช่วงวันที่แจ้ง สถานะ หมวดหมู่ และช่าง พร้อมตรวจความสอดคล้องของ workflow ทุก Role ต่อจากระบบที่พัฒนาแล้ว ใช้ Native PHP/PDO/CSS เดิม ไม่สร้าง Dashboard ซ้ำทั้งระบบ ไม่เพิ่ม User Management, Reject/Reopen หรือระบบสถิติขนาดใหญ่

รายงานหลักที่เสนอ: ภาพรวมจำนวนคำร้อง, สรุปตามสถานะ/ความสำคัญ/หมวดหมู่, งานที่รับมอบหมายและงานส่งซ่อมของช่าง และรายการคำร้องประกอบที่กรองได้ หน้า reports เป็น read-only ใช้ GET และ requireRole('ADMIN'); export CSV/PDF/Excel และกราฟเพิ่มเติมยังไม่อยู่ใน scope เว้นแต่ผู้ใช้ขอ

### นิยามข้อมูลที่ต้องตกลงใน implementation

| ประเด็น | แนวทางที่เสนอ |
|---|---|
| ช่วงเวลา | ใช้ repair_request.created_at เป็นฐานเดียวกันในรายงานหลัก แสดงชัดว่าเป็นวันที่แจ้ง; วันสิ้นสุดรวมทั้งวันด้วยขอบเขต < วันถัดไป ตรวจวันที่จริง/เริ่มไม่เกินสิ้นสุด และ timezone ของ PHP/MySQL ก่อนเขียน query |
| จำนวนรวม | นับคำร้องไม่ซ้ำจาก request_id ไม่ให้ join assignment/history ทำให้จำนวนพอง; ผลรวมสถานะและหมวดหมู่ต้องตรงกับยอดรวมในตัวกรองเดียวกัน |
| รอนักศึกษายืนยัน | เป็นส่วนหนึ่งของ IN_PROGRESS ที่มี completion ยังไม่ยืนยัน ไม่เพิ่ม status และไม่บวกซ้ำเข้า total |
| รายงานช่าง | แยกงานที่ได้รับมอบหมายปัจจุบันจาก active assignment กับงานที่ส่งหลักฐานจริงจาก completion.submitted_by_user_id ไม่เหมารวมว่าผู้รับงานคนปัจจุบันเป็นผู้ซ่อมทุกครั้ง |
| Reassignment/ข้อมูลผิดปกติ | เก็บประวัติเดิม ไม่ join ทุก assignment มานับซ้ำ; ตรวจหลาย active ต่อคำร้องและแจ้งความผิดปกติ ไม่แก้ข้อมูลจริงหรือเลือกช่างสมมุติเงียบ ๆ |
| COMPLETED | ใช้สถานะปิดงานและข้อมูล Student confirmation ที่มีจริง; หากพบ COMPLETED เดิมแต่ไม่มี completion ให้รายงานความต่าง ไม่สร้างข้อมูลยืนยันย้อนหลัง |
| ระยะเวลาซ่อม | ยังไม่เพิ่ม KPI ระยะเวลาซ่อมหรือคะแนนช่างโดยอนุมาน created_at เป็นเวลาเริ่มซ่อม; หากจำเป็นต้องกำหนดนิยามและข้อมูลต้นทางก่อน |
| Empty State | ช่วงที่ไม่มีข้อมูลต้องแสดง 0/ไม่พบข้อมูล ไม่แต่งกราฟ/เหตุการณ์ และไม่หารด้วยศูนย์ |

### Dependencies และแนวทางเปลี่ยนแปลงให้น้อยที่สุด

- Reuse auth/role/e(), helpers และ repair_request/category/assignment/completion/history เดิม ตรวจ schema และข้อมูลจริงอีกครั้งก่อนเริ่ม
- ต่อเมนู Reports จาก Admin header/Dashboard เดิม ไม่เปลี่ยน owner scope ของ Student/Technician
- ใช้ aggregate/subquery หรือ EXISTS ที่เหมาะสม แยก query สรุปกับรายการประกอบ; prepared statements สำหรับ filters และ validate/allowlist ทุกค่า
- คาดว่าไม่ต้องเปลี่ยน schema; เพิ่ม index/migration เฉพาะเมื่อ EXPLAIN/หลักฐานจริงแสดงความจำเป็น ไม่ปรับ PK/ENUM และไม่ลบข้อมูล
- ผล automated tests เดิม 189 checks เป็น baseline ให้รันซ้ำในรอบ implementation พร้อมเพิ่ม tests รายงาน ไม่ถือว่า Phase 7 ผ่านจากผลเก่า

### Checklist และลำดับดำเนินงาน

- [x] 7.0 ตรวจ schema/query/เวลาและ data anomalies จริงแบบ read-only พร้อมทบทวนนิยามรายงานข้างต้น
- [x] 7.1 เพิ่มหน้า Reports เฉพาะ Admin พร้อมตัวกรองวันที่แจ้ง/สถานะ/หมวดหมู่/ช่าง/ความสำคัญ และ validate วันที่ผิด/ค่าว่าง/ขอบเขต
- [x] 7.1 สรุปยอดรวมตามสถานะ/Priority/หมวดหมู่ แสดง waiting เป็น subset ของ IN_PROGRESS
- [x] 7.1 รายงานช่างแยก active assignment กับ submitted completion และคง attribution เมื่อเปลี่ยนช่าง
- [x] 7.1 รายการคำร้องประกอบจาก filters เดียวกับ summary พร้อม pagination/ลิงก์รายละเอียด/Empty State
- [x] 7.1 เพิ่ม navigation ใน Admin เดิม และใช้ components เดิมเน้น Desktop ไม่ redesign ทั้งระบบ
- [x] 7.2 ทดสอบ aggregate กับชุดข้อมูลที่รู้ยอดแน่นอน รวมหลาย history/reassign/รอยืนยัน/cancel/ไม่มีข้อมูล ไม่เกิดการนับซ้ำ
- [x] 7.2 ทดสอบวันต้น-วันท้าย/ข้ามเดือน/ปี/วันที่ผิด/เวลาเท่ากัน และตรวจ totals ระหว่าง summary กับรายการประกอบ
- [x] 7.2 ทดสอบรายงานเป็น read-only: anonymous/STUDENT/TECHNICIAN เข้าไม่ได้, filters ผิดไม่ crash/ไม่รั่ว SQL และ escape output
- [x] 7.2 Regression ชุดเดิมและ HTTP workflow ทุก Role พร้อม rollback/concurrency/upload/ownership/state validation ตามความเสี่ยงที่เปลี่ยนจริง
- [x] 7.2 เพิ่มรายงานตรวจความสอดคล้องใน tests: completion/ผู้ยืนยัน/เวลาปิด/history/assignment ไม่ขัดกัน โดยไม่ซ่อมข้อมูลจริงอัตโนมัติ
- [x] 7.3 Agent เตรียม Desktop UI checklist และข้อมูลตัวอย่าง/ยอดคาดหวังให้ผู้ใช้ตรวจเอง ไม่เปิด Browser อัตโนมัติ
- [x] 7.3 ผู้ใช้ยืนยัน “UI Phase 7 ผ่านแล้ว” — USER VERIFIED แยกจาก automated PASS
- [x] บันทึกไฟล์/migration/PASS/FAIL/NOT RUN/ข้อจำกัดจริง และปิด Phase 7 เป็น DONE — VERIFIED

### ไฟล์ Phase 7 และแผนที่ใช้

| ไฟล์ | แผน |
|---|---|
| admin/reports.php | สร้างรายงาน/filters/summary/รายการประกอบในหน้าเดียว |
| includes/repair-reports.php | สร้าง helpers สำหรับนิยาม filter/aggregate เมื่อช่วยให้ queries และ tests ใช้ตรงกัน |
| includes/admin-header.php | เพิ่มลิงก์ Reports |
| admin/dashboard.php | เพิ่มทางเข้ารายงาน/ส่งต่อ filters; คง dashboard เดิม |
| assets/css/style.css | เพิ่มเฉพาะ component รายงาน Desktop ที่จำเป็น |
| tests/phase7.php, tests/router.php | เพิ่ม fixtures ที่รู้ยอด, report/security/integration tests และ route ทดสอบ |
| tests/README.md, PROGRESS.md | เพิ่มวิธี rerun/expected totals/Checklist Desktop ที่ผู้ใช้เป็นผู้ตรวจ |
| migrations/<date>_<purpose>.sql | เพิ่มเฉพาะเมื่อมีหลักฐานจำเป็น ปัจจุบันยังไม่เสนอ DDL ใหม่ |

### เกณฑ์ปิด Phase 7

รายงานนับถูกตามนิยามเดียวกัน ไม่ซ้ำจาก joins มีสิทธิ์ Admin และ workflow เดิมไม่เสีย; Agent มีผล backend/HTTP/DB tests จริง และผู้ใช้รับรอง Desktop UI แล้ว จึงปิดเป็น DONE — VERIFIED พร้อมหลักฐาน จากนั้นค่อยวางแผน Phase 8 Desktop UI/UX Refinement ตามข้อเสนอแนะผู้ใช้ ไม่ทดสอบ Mobile

### 2026-11-13 — Phase 7 implementation และ backend verification

- สถานะ: IMPLEMENTED — NEEDS VERIFICATION; รอผู้ใช้ตรวจ Desktop UI ไม่เปิด Browser และไม่เริ่ม Phase 8
- Audit DB จริง: repair_request/assignment/completion ยังเป็น INT UNSIGNED/InnoDB และสถานะสี่ค่าเดิม; ไม่มี duplicate active assignment หรือ COMPLETED ที่ไม่มี confirmation ในฐานจริง ณ เวลา inspect
- พบ timezone PHP=Europe/Berlin แต่ MySQL SYSTEM=เวลาไทย (+07:00); รายงานใช้ DateTimeZone Asia/Bangkok และ SET SESSION time_zone='+07:00' เฉพาะ connection ที่อ่านรายงาน ไม่แก้ timezone global/config ของระบบเดิม
- ไฟล์เพิ่ม: admin/reports.php (Admin GET-only filters/summary/รายการประกอบ/pagination/แจ้งข้อมูลผิดปกติ), includes/repair-reports.php (strict validation/ขอบเขตวันไทย/aggregate และ consistent read snapshot)
- ไฟล์แก้: includes/admin-header.php และ admin/dashboard.php เพิ่มทางเข้ารายงาน; tests/router.php เพิ่ม route; ไม่ต้องเพิ่ม CSS เพราะ reuse components เดิมได้
- รายงาน: ช่วงวันที่แจ้งรวมวันสิ้นสุด, สถานะ/Priority/category/technician filters, ยอดรวม/สถานะ/หมวดหมู่/ความสำคัญ, waiting subset ของ IN_PROGRESS, unassigned เฉพาะ PENDING; รายการ 25 แถวต่อหน้า
- รายงานช่าง: current assignment ใช้ COUNT DISTINCT request ส่วน submitted/confirmed นับตามผู้ส่ง completion จริง; เลือกช่างแล้ว cohort รวมคำร้องที่ปัจจุบันรับมอบหมายหรือเคยส่งหลักฐานโดยช่างนั้น ตารางช่างแสดงเฉพาะช่างที่เลือก พร้อมคำอธิบายไม่ให้นำคอลัมน์ที่ซ้อนกันมาบวก
- ทุก query สรุป/รายละเอียดอยู่ใน REPEATABLE READ / READ ONLY transaction เดียว ไม่ fan-out join history/assignment เพื่อคำนวณ total; ไม่แก้ข้อมูลแม้ตรวจพบความผิดปกติ
- แจ้งความผิดปกติ: assignment active ซ้ำ, COMPLETED แต่ยืนยันไม่ครบ/ไม่ใช่ owner, confirmed fields/status ไม่ตรงกัน, วันเวลาผิดลำดับ และผู้ส่งหลักฐาน/assignment ไม่สอดคล้อง; ไม่เพิ่มการยืนยันย้อนหลังหรือซ่อมข้อมูลจริงอัตโนมัติ
- Database Changes: ไม่มี DDL/migration/index ใหม่ ไม่เปลี่ยน ENUM/PK และไม่มี write กับข้อมูลจริง fixit
- Tests เพิ่ม: tests/phase7.php ต่อ regression Phase 4.3–6; รันล่าสุดผ่าน 247 checks ใน fixit_test_d0aab8062472 (checks ใน race บางเส้นทางขึ้นกับลำดับผู้ชนะ จึงรายงานจำนวนตามรอบจริง)
- PASS: fixture ยอด 5 คำร้อง แบ่งสถานะ 1/2/1/1, Priority 2/2/1, หมวด 2/3; totals ตรงรายละเอียดแม้มี history มาก/reassign/duplicate active; waiting=1 และไม่บวกซ้ำ
- PASS: attribution ช่างผู้ส่งจริงแยกจาก assignee ปัจจุบัน, ช่างที่เลือก/ตัวกรองผสม, leap day/midnight/23:59:59/ข้ามเดือนและปี, timezone ก่อนหน้าเป็น UTC ก็ได้ผลวันไทยถูกต้อง
- PASS: invalid date/range/enum/array/ID/injection-like filter ถูกปฏิเสธ, Empty State เป็นศูนย์, pagination 30 แถวแบ่ง 25+5 คง filters และ totals ไม่เปลี่ยน/ไม่ซ้ำเมื่อเวลาเท่ากัน
- PASS: HTTP Admin GET=200, anonymous/STUDENT/TECHNICIAN GET/POST ไม่ผ่าน role, Admin POST=405, malformed filters=400 ไม่รั่ว SQL, escape หัวข้อ/หมวดหมู่, navigation และ snapshot ก่อน/หลังยืนยันว่าไม่มีการแก้ข้อมูล
- PASS: consistency ของ workflow จริงจาก Phase 6 ยังมี completion/owner/เวลาปิด/history หนึ่งครั้งและเวลายืนยันไม่ก่อนส่ง; fixtures ข้อมูลเก่าหรือเวลาผิดถูกตรวจพบโดยไม่ backfill
- PASS: PHP lint ทั้ง 33 ไฟล์ และ git diff --check
- FAIL รอบล่าสุด: ไม่มี
- NOT RUN: UI/visual/Desktop Browser — เป็นหน้าที่ผู้ใช้ตามคำสั่ง; Mobile ไม่อยู่ใน scope; ไม่ทดสอบ load/performance ข้อมูลขนาดใหญ่หรือ export ที่ยังไม่อยู่ใน scope
- งานส่งต่อ: tests/phase7-ui-checklist.md มีขั้นตอนและตัวเลข fixture อ้างอิงให้ผู้ใช้ตรวจ; tests/README.md มีคำสั่ง rerun/นิยามตัวกรอง/วิธี reset ฐานแยก
- ข้อจำกัด: รายงานนับ submission จาก record ไม่ยืนยันไฟล์รูปย้อนหลังทุกไฟล์; ข้อมูล active ซ้ำทำให้คำร้องเดียวอยู่ในยอดหลายช่างได้ จึงแจ้งเตือน ไม่อ้างว่ายอดรายช่างรวมต้องเท่ากับ total; ไม่มี KPI ระยะเวลาซ่อม/export/กราฟเพิ่มเติม

### 2026-11-13 — ปิด Phase 7 ตามผล UI ของผู้ใช้

- ผู้ใช้ยืนยันโดยตรงว่า “UI Phase 7 ผ่านแล้ว” จึงบันทึก Desktop UI เป็น PASS — USER VERIFIED; ไม่ได้อ้างว่า Agent เปิด Browser และไม่ได้รับผลแยกรายข้อ
- ปิด Phase 7 เป็น DONE — VERIFIED โดยใช้ผล automated 247 checks, PHP lint 33 ไฟล์ และ diff check ที่รันก่อนหน้า ร่วมกับผล UI ของผู้ใช้; ไม่มี FAIL ที่รายงานค้าง
- รอบรับรองนี้แก้เฉพาะ AGENTS.md, PROGRESS.md, tests/README.md และ tests/phase7-ui-checklist.md; ไม่เปลี่ยน application code, Database หรือ Migration และไม่รัน automated tests ซ้ำ
- Mobile ไม่อยู่ใน scope; load/performance ข้อมูลขนาดใหญ่และ export ยังไม่ได้ทดสอบและไม่ได้เพิ่มเป็น requirement ข้อจำกัดข้อมูลเก่า/รูปย้อนหลังตามบันทึก implementation ยังคงเดิม
- เลื่อน Current Phase เป็น 8 — Desktop UI/UX Refinement, NOT STARTED เพื่อเตรียมวางแผน ยังไม่เริ่มเขียนโค้ด

## 11. Current Phase 8 — Desktop UI/UX Refinement (NOT STARTED)

- [ ] สำรวจ Desktop UI และ CSS/components ที่มีอยู่ พร้อมรวบรวมข้อเสนอแนะหรือจุดที่ต้องปรับจากผู้ใช้
- [ ] ร่างขอบเขตและไฟล์ที่ต้องแก้ตามปัญหาจริง โดยคง workflow, security และ Requirements เดิม ไม่ redesign ทั้งระบบหรือเพิ่ม Mobile scope
- [ ] กำหนด Acceptance Criteria และ regression ที่เหมาะกับไฟล์ที่จะเปลี่ยน พร้อม Checklist Desktop สำหรับผู้ใช้
- [ ] ดำเนิน implementation ตามแผนเมื่อได้รับคำสั่งเริ่ม Phase 8
- [ ] บันทึกผล backend/HTTP/DB ที่เกี่ยวข้องและผล UI จากผู้ใช้แยกกัน ก่อนพิจารณาปิด Phase
