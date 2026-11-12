# ทดสอบ Phase 4.3

> ขอบเขตปัจจุบัน: เน้น Desktop เป็นหลัก ไม่ต้องทดสอบ Mobile/Responsive มือถือ และไม่ใช้ผล Mobile เป็นเงื่อนไขปิด Phase; เก็บบันทึกการตรวจมือถือในอดีตไว้เป็นประวัติเท่านั้น

## Phase 6 Technician

รัน `C:/xampp/php/php.exe tests/phase6.php` จาก project root: รวม regression Phase 4.3/5 แล้วทดสอบ Technician ต่อ รวม 189 checks ใน revision นี้ ใช้ฐาน fixit_test_<random> ใหม่ ไม่ copy ข้อมูลจริง ต้องมี PHP PDO MySQL, cURL, proc_open และสิทธิ์ CREATE DATABASE/TRIGGER ไม่ต้องมี GD/Pillow เพราะมี tiny JPEG/WEBP fixtures ใน tests/upload-fixtures.php

Runner เปิด PHP server บน loopback สอง port สำหรับ concurrency และปิดใน finally; ใช้ sessions ใน tests/sessions ลบเฉพาะรูป/ไฟล์ temporary ที่สร้างเอง ฐานทดสอบเก็บไว้ ไม่ DROP อัตโนมัติ Reset ด้วยการรันใหม่ ไม่มี migration ใหม่สำหรับ Phase 6 ไม่ต้องแก้ config/database.php เพื่อใช้งานจริง

ตรวจ Desktop ที่ `http://localhost/fixit/login.php`:

1. Admin มอบหมาย PENDING ให้บัญชี TECHNICIAN ที่ active แล้ว login เป็นช่าง ตรวจ Dashboard/งานของฉัน/รายละเอียด ต้องไม่เห็นงานคนอื่น
2. เริ่มงาน ตรวจ IN_PROGRESS และ Timeline; บันทึกความคืบหน้าต้องมีเวลา/ผู้ทำรายการ/ข้อความจริง
3. ส่งรูป JPG/PNG/WEBP <=5 MB ต้องแสดงรอนักศึกษายืนยันและไม่มีฟอร์มแก้ไขงานอีก ช่างปิด COMPLETED เองไม่ได้
4. Student เจ้าของดูรูปหลังซ่อมและยืนยัน แล้ว Technician/Admin ต้องเห็นเสร็จสิ้น
5. ใช้ฐานทดสอบแยกสำหรับสิทธิ์ผิด/CSRF/ยกเลิก/reassign/ไฟล์ผิดหรือเกินขนาด/ส่งซ้ำ ตรวจ error และข้อมูลไม่เปลี่ยน ไม่ดัดแปลงคำร้องจริงเพื่อทดลอง
6. ตรวจ search/status/Priority/category/Empty State/pagination เกิน 25 งาน และ Desktop layout ไม่ต้องทดสอบ Mobile

ผล Browser Desktop ที่ Agent ตรวจแยกอยู่ใน PROGRESS.md ข้อความ Browser NOT RUN ใน stdout หมายถึง runner ไม่ได้ควบคุม Browser ไม่ใช่การปฏิเสธผลตรวจ UI ที่บันทึกแยกไว้; ยังเหลือ UI filters/pagination/error/ทุก Role ให้ครบก่อนปิด Phase 6

รันจาก `C:\xampp\htdocs\fixit`:

```powershell
C:/xampp/php/php.exe tests/phase43.php
```

Runner ใช้ credentials จาก config เดิม สร้างฐาน `fixit_test_<random>` ใหม่จาก schema จริง ไม่มีการ copy ข้อมูลผู้ใช้/คำร้องจริง ต้องมีสิทธิ์ CREATE DATABASE และ TRIGGER เปิด PHP server เฉพาะ loopback บน port ว่างและหยุดใน finally ใช้ sessions ใน tests/sessions ซึ่ง Apache ถูกห้ามเข้าถึง ไม่เปลี่ยน session configuration ของแอปจริง

ทุกครั้ง seed ห้อง/หมวด/พื้นที่, student สองคน, technician, admin และคำร้องจำลอง พร้อมรูป PNG ชั่วคราว จากนั้นทดสอบ helper/transaction, HTTP endpoints จริง และยืนยันพร้อมกันสอง process ดูผล PASS/FAIL ใน console ฐานทดสอบคงอยู่เพื่อดูหลักฐาน ห้ามใช้ router/worker กับฐาน fixit; runner จำกัดชื่อฐานทดสอบ รูปชั่วคราวลบหลังจบ จึงไม่ควรใช้ฐานเหล่านี้ตรวจรูปย้อนหลัง

Reset: รันคำสั่งเดิมเพื่อสร้างฐานทดสอบใหม่ ไม่ต้องล้างฐานเก่า หากต้องเก็บกวาด ให้ตรวจชื่อฐานใน MySQL Workbench ว่าเป็นฐานทดสอบจากผล console ก่อนจัดการเอง ไม่มีคำสั่งลบฐานจริงใน runner

Migration สำหรับระบบจริง (รันแล้วกับ fixit เมื่อ 13 พฤศจิกายน 2026):

```powershell
C:/xampp/php/php.exe migrations/apply-phase43.php
```

หรือเลือก schema ใน Workbench แล้วรัน `migrations/20261113_repair_completion.sql` หลังตรวจ SHOW CREATE TABLE ว่า FK เป็น INT UNSIGNED และ InnoDB หากมีตารางทดลองอยู่แล้วต้องตรวจ schema ก่อน ไม่ใช้ IF NOT EXISTS เพื่ออ้างว่าตารางเดิมถูกต้อง

ตรวจด้วย Browser ที่ `http://localhost/fixit/login.php`:

1. Login ด้วย Student ที่มีอยู่ เปิดรายการของตนและรายละเอียด ตรวจ Timeline วันเวลา/ผู้ทำรายการ/หมายเหตุเทียบ repair_history และช่างเทียบ active assignment ตรวจบน Desktop
2. สร้างคำร้องห้อง/ส่วนกลาง ทั้งไม่แนบรูปและแนบ JPG/PNG/WEBP ตรวจรูปปัญหาแยกจากหลักฐานหลังซ่อม ค้นหา กรอง และยกเลิก PENDING
3. การตรวจยืนยันด้วยข้อมูลจำลองให้ใช้ฐานทดสอบแยกเท่านั้น: ต้องมี IN_PROGRESS, active assignment ไป TECHNICIAN, completion จากช่างคนเดียวกัน พร้อมไฟล์จริงใน uploads/completions และ submitted_at หลัง assigned_at; บันทึก history ส่งงานด้วย IN_PROGRESS → IN_PROGRESS
4. ตรวจรอยืนยัน รูป/หมายเหตุ/เวลา กดยืนยันและ refresh ต้อง COMPLETED ปุ่มหาย history หนึ่งรายการ ลองย้อนหน้าและส่งซ้ำต้องไม่เพิ่มประวัติ
5. Login Student อีกคนต้องเปิดรายละเอียดหรือ POST งานของคนแรกไม่ได้; PENDING/CANCELLED/ไม่มีรูปต้องยืนยันไม่ได้

ยังไม่มี Technician UI สำหรับส่งหลักฐาน จึงยังไม่ถือว่าผ่าน workflow ทุก Role แบบ end-to-end


# Phase 5 Admin

```powershell
C:/xampp/php/php.exe tests/phase5.php
```

คำสั่งนี้รัน regression Phase 4.3 ก่อน แล้วทดสอบ Admin ต่อในฐานแยกเดียวกัน รวม 109 checks ใน revision ปัจจุบัน ไม่ copy บัญชีจริงและไม่เปลี่ยนฐาน fixit ใช้ temporary PHP server บน loopback และหยุดใน finally เช่นเดิม ไม่มี migration ใหม่สำหรับ Phase 5

เปิด `http://localhost/fixit/login.php` ด้วยบัญชี ADMIN เดิม แล้วตรวจ:

1. Dashboard แสดงจำนวนจริง ลิงก์สถานะ/ความสำคัญ/รอมอบหมาย/รอยืนยันไปยังตัวกรองที่ตรงกัน
2. คำร้องทั้งหมด: ค้นหา กรองทุกช่อง ล้างตัวกรอง ดู empty state และแบ่งหน้าเมื่อมีเกิน 25 คำร้อง ตรวจบน Desktop
3. รายละเอียด: เปลี่ยน Priority มอบหมาย/เปลี่ยนช่างใน PENDING ตรวจ assignment เดิมยังอยู่และ Timeline เพิ่มถูกผู้ทำรายการ; ส่งค่าเดิมซ้ำต้องไม่เพิ่ม history
4. IN_PROGRESS เปลี่ยนช่างไม่ได้; คำร้องที่มีหลักฐานหรือ COMPLETED/CANCELLED ต้องอ่านอย่างเดียว Admin ไม่มีปุ่มยืนยันแทน Student
5. ห้องพัก: ค้นหา แก้เลขห้อง/ชั้น/สถานะ ทดสอบเลขซ้ำและห้ามเปลี่ยนห้องที่มีผู้พักเป็น AVAILABLE; ตรวจกรณี error/success
6. Login STUDENT/TECHNICIAN ต้องเข้า Admin ไม่ได้ และ Student ยังคงเห็นเฉพาะคำร้องตนเอง/ยกเลิก/ยืนยันได้ตามเดิม

UI/รูปอัปโหลด Phase 4 ผ่านตามผู้ใช้ยืนยันเมื่อ 13 พฤศจิกายน 2026; ผลข้อความ Browser visual QA NOT RUN ใน runner หมายถึง Agent ไม่ได้รัน browser โดยอัตโนมัติ ไม่ใช่การยกเลิกผล UAT ของผู้ใช้ สำหรับ Admin ยังไม่มีผล UAT: Computer Use ไม่ได้รับอนุญาตให้ใช้ Chrome ในรอบพัฒนา จึงคง NEEDS VERIFICATION เฉพาะ UI และยังมี Technician UI Integration Pending


อัปเดต UI verification 13 พฤศจิกายน 2026: ใช้ Chrome ตรวจ Desktop รายละเอียด/ฟอร์มห้อง และ Responsive 400px ของฟอร์มห้อง/รายการคำร้อง/Empty State แล้ว พบตารางมือถือบีบข้อความและแก้เป็น horizontal scroll (ตารางคำร้อง 900px, ห้อง 640px) ตรวจปัดคอลัมน์คำร้องซ้ำผ่าน ควร hard reload หาก CSS เก่าค้างแคช ยังเหลือตรวจ pagination หลายหน้า, ผล POST success/error ผ่าน Browser ในฐานแยก, ตารางห้องหลังแก้และหน้ามือถืออื่นให้ครบ จึงยังไม่ปิด Phase 5 ผลก่อนหน้านี้ที่ Computer Use ติดสิทธิ์เป็นประวัติของรอบก่อน ไม่ใช่สถานะการเข้าถึง Chrome ล่าสุด


อัปเดตการรับรองล่าสุด 13 พฤศจิกายน 2026: ผู้ใช้ยืนยัน Browser UI Phase 5 ผ่านครบแล้ว จึงปิด Phase 5 เป็น DONE — VERIFIED โดยใช้ผล UAT ผู้ใช้ร่วมกับ automated 109 checks ที่รันก่อนหน้า ไม่ได้อ้างว่า Agent รัน Browser ส่วนที่ค้างเอง Current Phase เป็น 6 Technician ซึ่งยังอยู่ขั้นร่างแผนใน PROGRESS.md; ไม่เริ่มเขียนโค้ดในรอบนี้ และไม่ต้องทดสอบ Mobile ตามขอบเขตโครงการที่เน้น Desktop
