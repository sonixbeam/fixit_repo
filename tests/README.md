# ทดสอบ Phase 4.3

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

1. Login ด้วย Student ที่มีอยู่ เปิดรายการของตนและรายละเอียด ตรวจ Timeline วันเวลา/ผู้ทำรายการ/หมายเหตุเทียบ repair_history และช่างเทียบ active assignment ตรวจจอมือถือด้วย
2. สร้างคำร้องห้อง/ส่วนกลาง ทั้งไม่แนบรูปและแนบ JPG/PNG/WEBP ตรวจรูปปัญหาแยกจากหลักฐานหลังซ่อม ค้นหา กรอง และยกเลิก PENDING
3. การตรวจยืนยันด้วยข้อมูลจำลองให้ใช้ฐานทดสอบแยกเท่านั้น: ต้องมี IN_PROGRESS, active assignment ไป TECHNICIAN, completion จากช่างคนเดียวกัน พร้อมไฟล์จริงใน uploads/completions และ submitted_at หลัง assigned_at; บันทึก history ส่งงานด้วย IN_PROGRESS → IN_PROGRESS
4. ตรวจรอยืนยัน รูป/หมายเหตุ/เวลา กดยืนยันและ refresh ต้อง COMPLETED ปุ่มหาย history หนึ่งรายการ ลองย้อนหน้าและส่งซ้ำต้องไม่เพิ่มประวัติ
5. Login Student อีกคนต้องเปิดรายละเอียดหรือ POST งานของคนแรกไม่ได้; PENDING/CANCELLED/ไม่มีรูปต้องยืนยันไม่ได้

ยังไม่มี Technician UI สำหรับส่งหลักฐาน จึงยังไม่ถือว่าผ่าน workflow ทุก Role แบบ end-to-end
