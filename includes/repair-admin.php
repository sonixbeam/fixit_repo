<?php

require_once __DIR__ . '/repair-completion.php';

function adminStatusLabels(): array
{
    return ['PENDING'=>'รอดำเนินการ', 'IN_PROGRESS'=>'กำลังดำเนินการ', 'COMPLETED'=>'เสร็จสิ้น', 'CANCELLED'=>'ยกเลิกแล้ว'];
}

function adminPriorityLabels(): array
{
    return ['LOW'=>'ต่ำ', 'MEDIUM'=>'ปานกลาง', 'HIGH'=>'สูง'];
}

function adminText(array $input, string $key, int $length = 100): string
{
    return is_string($input[$key] ?? null) ? mb_substr(trim($input[$key]), 0, $length, 'UTF-8') : '';
}

function adminPositiveId($value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    return $id === false ? null : $id;
}

function adminCsrfToken(): string
{
    if (empty($_SESSION['csrf_admin'])) {
        $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_admin'];
}

function adminVerifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals(adminCsrfToken(), $token)) {
        http_response_code(403);
        exit('คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่');
    }
}

function adminRequireActor(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("SELECT user_id FROM `user` WHERE user_id=? AND role='ADMIN' AND is_active=1 FOR SHARE");
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        throw new DomainException('ไม่มีสิทธิ์ทำรายการ');
    }
}

// ทุก writer ล็อก repair_request ก่อน assignment/completion/history เหมือน confirmation
function adminChangeRequest(PDO $pdo, int $requestId, int $adminId, string $action, array $values): bool
{
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM repair_request WHERE request_id=? FOR UPDATE');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        if (!$request) {
            throw new DomainException('ไม่พบคำร้อง');
        }
        adminRequireActor($pdo, $adminId);
        $stmt = $pdo->prepare('SELECT request_id FROM repair_completion WHERE request_id=? FOR UPDATE');
        $stmt->execute([$requestId]);
        if ($stmt->fetchColumn() || !in_array($request['status'], ['PENDING','IN_PROGRESS'], true)) {
            throw new DomainException('สถานะปัจจุบันไม่อนุญาตให้แก้ไขคำร้องนี้');
        }
        if ($action === 'priority') {
            $priority = $values['priority'] ?? null;
            if (!is_string($priority) || !isset(adminPriorityLabels()[$priority])) {
                throw new DomainException('ระดับความสำคัญไม่ถูกต้อง');
            }
            if ($priority === $request['priority']) {
                $pdo->commit();
                return false;
            }
            $pdo->prepare('UPDATE repair_request SET priority=? WHERE request_id=?')->execute([$priority,$requestId]);
            $note = 'ผู้ดูแลเปลี่ยนความสำคัญ ' . $request['priority'] . ' → ' . $priority;
        } elseif ($action === 'assign') {
            if ($request['status'] !== 'PENDING') {
                throw new DomainException('มอบหมายหรือเปลี่ยนช่างได้เฉพาะก่อนเริ่มงาน');
            }
            $technicianId = adminPositiveId($values['technician_user_id'] ?? null);
            $noteInput = $values['note'] ?? '';
            if (!$technicianId || !is_string($noteInput) || mb_strlen($noteInput,'UTF-8') > 2000) {
                throw new DomainException('ข้อมูลช่างหรือหมายเหตุไม่ถูกต้อง');
            }
            $stmt = $pdo->prepare("SELECT username FROM `user` WHERE user_id=? AND role='TECHNICIAN' AND is_active=1 FOR SHARE");
            $stmt->execute([$technicianId]);
            $name = $stmt->fetchColumn();
            if ($name === false) {
                throw new DomainException('เลือกได้เฉพาะช่างที่เปิดใช้งาน');
            }
            $stmt = $pdo->prepare('SELECT * FROM repair_assignment WHERE request_id=? AND is_active=1 ORDER BY assignment_id FOR UPDATE');
            $stmt->execute([$requestId]);
            $active = $stmt->fetchAll();
            if (count($active) === 1 && (int)$active[0]['technician_user_id'] === $technicianId) {
                $pdo->commit();
                return false;
            }
            // ไม่ลบ assignment เก่า เก็บประวัติไว้แม้เปลี่ยนช่าง
            $pdo->prepare('UPDATE repair_assignment SET is_active=0 WHERE request_id=? AND is_active=1')->execute([$requestId]);
            $pdo->prepare('INSERT INTO repair_assignment (request_id,technician_user_id,assigned_by_user_id,note) VALUES (?,?,?,?)')
                ->execute([$requestId,$technicianId,$adminId,trim($noteInput) ?: null]);
            $note = 'ผู้ดูแล' . ($active ? 'เปลี่ยนช่างเป็น ' : 'มอบหมายช่าง ') . $name . ' (#' . $technicianId . ')';
            if (trim($noteInput) !== '') {
                $note .= ': ' . trim($noteInput);
            }
        } else {
            throw new DomainException('การทำรายการไม่ถูกต้อง');
        }
        $pdo->prepare('INSERT INTO repair_history (request_id,changed_by_user_id,old_status,new_status,note) VALUES (?,?,?,?,?)')
            ->execute([$requestId,$adminId,$request['status'],$request['status'],$note]);
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $ex;
    }
}

function adminUpdateRoom(PDO $pdo, int $roomId, int $adminId, array $values): void
{
    $number = $values['room_number'] ?? null;
    $floor = filter_var($values['floor'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>255]]);
    $status = $values['room_status'] ?? null;
    if (!is_string($number) || trim($number)==='' || mb_strlen(trim($number),'UTF-8')>20 || $floor===false
        || !in_array($status,['AVAILABLE','OCCUPIED','MAINTENANCE'],true)) {
        throw new DomainException('ข้อมูลห้องพักไม่ถูกต้อง');
    }
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT room_id FROM room WHERE room_id=? FOR UPDATE');
        $stmt->execute([$roomId]);
        if (!$stmt->fetchColumn()) { throw new DomainException('ไม่พบห้องพัก'); }
        adminRequireActor($pdo,$adminId);
        $stmt = $pdo->prepare("SELECT user_id FROM `user` WHERE room_id=? AND role='STUDENT' AND is_active=1 FOR SHARE");
        $stmt->execute([$roomId]);
        if ($stmt->fetchColumn() && $status==='AVAILABLE') {
            throw new DomainException('ห้องที่มีผู้พักอยู่ไม่สามารถเปลี่ยนเป็นห้องว่างได้');
        }
        $pdo->prepare('UPDATE room SET room_number=?,floor=?,room_status=? WHERE room_id=?')->execute([trim($number),$floor,$status,$roomId]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ($ex instanceof PDOException && ($ex->errorInfo[1] ?? null)===1062) {
            throw new DomainException('เลขห้องนี้มีอยู่แล้ว');
        }
        throw $ex;
    }
}
