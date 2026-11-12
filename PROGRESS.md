# PROGRESS.md — FixIT ระบบแจ้งซ่อมหอพัก

> บันทึกความคืบหน้าและแผนส่งต่องานสำหรับ Codex / ผู้พัฒนา
>
> **อัปเดตฐานข้อมูลเริ่มต้นของเอกสาร:** 13 พฤศจิกายน 2026 (จากบทสนทนาการพัฒนาเดิม ไม่ได้ตรวจ Workspace หรือฐานข้อมูลจริง)
>
> **กติกา:** อ่าน `AGENTS.md` เพื่อรู้ Requirements และข้อกำหนดถาวร อ่าน `PROGRESS.md` เพื่อทราบสถานะงานปัจจุบันและขั้นตอนถัดไป หากสองไฟล์ขัดกันเรื่องความคืบหน้า ให้ยึดหลักฐานจากไฟล์/ฐานข้อมูล/ผลทดสอบจริง แล้วปรับ `PROGRESS.md` ให้ถูกต้อง; หากขัดกันเรื่อง Requirement ห้ามเปลี่ยนเองโดยพลการ

## 1. สถานะล่าสุด (Current Checkpoint)

- **Current Phase:** `4.3 — ติดตามความคืบหน้าและยืนยันงานซ่อมของนักศึกษา`
- **Current State:** `IMPLEMENTED — NEEDS VERIFICATION (รอตรวจหน้าจอและ regression รูปอัปโหลด)`
- **Last Implemented Phase:** `4.2 — My Repair Requests`
- **Current Goal:** พัฒนา Timeline, แสดงหลักฐานหลังซ่อม และให้เจ้าของคำร้องยืนยันงานซ่อมได้อย่างปลอดภัย
- **Important:** พัฒนาและทดสอบ HTTP/Transaction แล้ว ยังไม่ปิด Phase เพราะยังไม่ได้ตรวจ UI ด้วย Browser และ regression รูปอัปโหลดจริง; Technician integration ยังรอ Phase 6
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
| **4.3** | **Progress Timeline + Completion Evidence + Student Confirmation** | **IMPLEMENTED — NEEDS VERIFICATION** | **Implement และทดสอบ Acceptance Criteria** |
| 5 | Admin: Dashboard, กำหนด Priority, มอบหมายช่าง, ห้องพัก | NOT STARTED / ยังไม่ยืนยันจาก Workspace | พิจารณาขั้นมอบหมายงานเป็นอันดับแรก |
| 6 | Technician: งานที่ได้รับมอบหมาย, เริ่มงาน, รูปหลังซ่อม, ส่งยืนยัน | NOT STARTED / ยังไม่ยืนยันจาก Workspace | ต้องเชื่อมข้อมูลกับ Phase 4.3 |
| 7 | Reports และ Integration/System Testing | NOT STARTED | ทดสอบ Workflow ทุก Role |
| 8 | UI/UX/Responsive Refinement | NOT STARTED | ปรับ UI ทั่วระบบภายหลัง |

> Phase 5–6 สามารถสลับลำดับหรือแบ่งงานเป็นช่วงสั้น ๆ ตาม Dependencies จริง โดยบันทึกเหตุผลที่นี่ก่อนปรับ Roadmap; ห้ามขยาย Scope เงียบ ๆ

## 3. งานที่ต้องทำตอนนี้ — Phase 4.3

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
- [x] Timeline แสดงเหตุการณ์จริง ไม่แต่งเวลาหรือเหตุการณ์
- [x] คำร้อง `PENDING` หรือคำร้องไม่มีรูปหลังซ่อมยืนยันไม่ได้
- [x] งานที่ช่างส่งพร้อมรูปถูกต้อง เจ้าของคำร้องยืนยันได้
- [x] หลังยืนยัน สถานะเป็น `COMPLETED` พร้อมบันทึกผู้ยืนยัน/เวลา/History
- [x] ยืนยันซ้ำหรือยิงคำขอพร้อมกันไม่บันทึกซ้ำ
- [x] `CANCELLED` หรือสถานะไม่ถูกต้อง ยืนยันไม่ได้
- [x] กรณี Transaction ล้มเหลว ไม่มีข้อมูลค้างครึ่งทาง
- [ ] Regression Phase 4.1–4.2: สร้าง/ดู/ค้นหา/กรอง/ยกเลิก `PENDING` ยังใช้ได้
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
| `Fatal error: Cannot redeclare e()` | เคยพบใน `my-requests.php` และ `auth.php`; ไม่ยืนยันสภาพล่าสุด | ตรวจการประกาศ helper และเรียกจากจุดเดียว |
| MySQL Error 3780 ของ `fk_completion_request` | เคยเกิดระหว่างทดลอง Phase 4.3 | ตรวจ `SHOW CREATE TABLE` และชนิดคอลัมน์ FK ให้ตรง |
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
