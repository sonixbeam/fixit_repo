<?php
// reuse helpers ID/text/status/Priority เดิม ซึ่งไม่มี side effects หรือการข้าม role
require_once __DIR__ . '/repair-admin.php';

function technicianCsrfToken(): string
{
    if (empty($_SESSION['csrf_technician'])) {
        $_SESSION['csrf_technician'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_technician'];
}

function technicianOwnsRequest(PDO $pdo, int $requestId, int $technicianId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM repair_assignment WHERE request_id=? AND technician_user_id=? AND is_active=1 LIMIT 1');
    $stmt->execute([$requestId,$technicianId]);
    return (bool)$stmt->fetchColumn();
}

function technicianUpload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
        || !is_string($file['name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
        throw new DomainException('ต้องแนบรูปหลักฐานหลังซ่อมที่อัปโหลดสำเร็จ');
    }
    $size = filesize($file['tmp_name']);
    if ($size===false || $size===0 || $size>5*1024*1024) {
        throw new DomainException('รูปหลักฐานต้องมีขนาดไม่เกิน 5 MB');
    }
    $types = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    $extension = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$extension]) || $mime!==$types[$extension] || !@getimagesize($file['tmp_name'])) {
        throw new DomainException('รองรับเฉพาะรูป JPG, PNG และ WEBP ที่ถูกต้อง');
    }
    $path = 'uploads/completions/'.bin2hex(random_bytes(16)).'.'.($extension==='jpeg'?'jpg':$extension);
    if (!move_uploaded_file($file['tmp_name'],__DIR__.'/../'.$path)) {
        throw new RuntimeException('Unable to store completion photo');
    }
    return $path;
}

function technicianChangeRequest(PDO $pdo, int $requestId, int $technicianId, string $action, $note = '', ?array $file = null): void
{
    $createdPhoto = null;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM repair_request WHERE request_id=? FOR UPDATE');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        $stmt = $pdo->prepare("SELECT user_id FROM `user` WHERE user_id=? AND role='TECHNICIAN' AND is_active=1 FOR SHARE");
        $stmt->execute([$technicianId]);
        if (!$request || !$stmt->fetchColumn()) { throw new DomainException('ไม่พบงานหรือไม่มีสิทธิ์ทำรายการ'); }
        $stmt = $pdo->prepare('SELECT technician_user_id FROM repair_assignment WHERE request_id=? AND is_active=1 ORDER BY assignment_id FOR UPDATE');
        $stmt->execute([$requestId]);
        $assigned = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($assigned)!==1 || (int)$assigned[0]!==$technicianId) {
            throw new DomainException('งานนี้ไม่ได้มอบหมายให้คุณหรือข้อมูลการมอบหมายไม่ถูกต้อง');
        }
        $stmt = $pdo->prepare('SELECT request_id FROM repair_completion WHERE request_id=? FOR UPDATE');
        $stmt->execute([$requestId]);
        if ($stmt->fetchColumn() || !in_array($request['status'],['PENDING','IN_PROGRESS'],true)) {
            throw new DomainException('งานนี้ส่งหลักฐานแล้วหรือสถานะไม่อนุญาตให้แก้ไข');
        }
        if (!is_string($note) || mb_strlen(trim($note),'UTF-8')>2000) {
            throw new DomainException('หมายเหตุต้องมีความยาวไม่เกิน 2000 ตัวอักษร');
        }
        $note = trim($note);
        $newStatus = $request['status'];
        if ($action==='start') {
            if ($request['status']!=='PENDING') { throw new DomainException('เริ่มงานได้เฉพาะงานที่ยังรอดำเนินการ'); }
            $newStatus = 'IN_PROGRESS';
            $pdo->prepare("UPDATE repair_request SET status='IN_PROGRESS' WHERE request_id=?")->execute([$requestId]);
            $historyNote = 'ช่างเริ่มดำเนินการซ่อม';
        } elseif ($action==='progress') {
            if ($request['status']!=='IN_PROGRESS' || $note==='') { throw new DomainException('ต้องเริ่มงานแล้วและกรอกความคืบหน้าก่อนบันทึก'); }
            $historyNote = 'ความคืบหน้า: '.$note;
        } elseif ($action==='submit') {
            if ($request['status']!=='IN_PROGRESS') { throw new DomainException('ต้องเริ่มงานก่อนส่งหลักฐาน'); }
            $createdPhoto = technicianUpload($file ?? []);
            $pdo->prepare('INSERT INTO repair_completion (request_id,submitted_by_user_id,completion_photo_path,note) VALUES (?,?,?,?)')
                ->execute([$requestId,$technicianId,$createdPhoto,$note!==''?$note:null]);
            $historyNote = 'ช่างส่งรูปหลักฐานหลังซ่อม รอนักศึกษายืนยัน'.($note!==''?': '.$note:'');
        } else {
            throw new DomainException('การทำรายการไม่ถูกต้อง');
        }
        $pdo->prepare('INSERT INTO repair_history (request_id,changed_by_user_id,old_status,new_status,note) VALUES (?,?,?,?,?)')
            ->execute([$requestId,$technicianId,$request['status'],$newStatus,$historyNote]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        // เฉพาะไฟล์ชื่อสุ่มที่สร้างใน transaction ครั้งนี้ ไม่ลบรูปเก่า
        if ($createdPhoto!==null && is_file(__DIR__.'/../'.$createdPhoto)) { unlink(__DIR__.'/../'.$createdPhoto); }
        throw $ex;
    }
}
