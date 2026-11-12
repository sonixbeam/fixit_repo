<?php

// ใช้ทั้งตอนแสดงปุ่มและตรวจซ้ำภายใน transaction
function completionPhotoUrl(?string $path): ?string
{
    if (!$path || !preg_match('~^uploads/completions/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~i', $path)) {
        return null;
    }
    $file = __DIR__ . '/../' . $path;
    if (!is_file($file) || filesize($file) > 5 * 1024 * 1024) {
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
    $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    if ($mime !== $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] || !@getimagesize($file)) {
        return null;
    }
    return '/fixit/' . $path;
}

function repairCompletion(PDO $pdo, int $requestId, bool $lock = false): ?array
{
    $stmt = $pdo->prepare("SELECT c.*, u.username AS submitted_by_name,
        u.role AS submitter_role,
        EXISTS(SELECT 1 FROM repair_assignment a
            WHERE a.request_id = c.request_id AND a.is_active = 1
              AND a.technician_user_id = c.submitted_by_user_id
              AND a.assigned_at <= c.submitted_at) AS assigned_submitter
        FROM repair_completion c JOIN `user` u ON u.user_id = c.submitted_by_user_id
        WHERE c.request_id = ?" . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([$requestId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function canConfirmRepair(array $request, ?array $completion): bool
{
    return $request['status'] === 'IN_PROGRESS' && $completion
        && $completion['confirmed_at'] === null && $completion['confirmed_by_user_id'] === null
        && $completion['submitter_role'] === 'TECHNICIAN' && (bool) $completion['assigned_submitter']
        && !empty($completion['submitted_at'])
        && completionPhotoUrl($completion['completion_photo_path']) !== null;
}

// ผู้เรียกต้องตรวจ role/CSRF ก่อนเรียก; ตรวจ ownership และ state ซ้ำใต้ row lock
function confirmRepair(PDO $pdo, int $requestId, int $studentId): void
{
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM repair_request WHERE request_id = ? AND user_id = ? FOR UPDATE');
        $stmt->execute([$requestId, $studentId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request || !canConfirmRepair($request, repairCompletion($pdo, $requestId, true))) {
            throw new RuntimeException('Repair is not ready for confirmation');
        }
        $pdo->prepare('UPDATE repair_completion SET confirmed_at = NOW(), confirmed_by_user_id = ? WHERE request_id = ?')
            ->execute([$studentId, $requestId]);
        $pdo->prepare("UPDATE repair_request SET status = 'COMPLETED', completed_at = NOW() WHERE request_id = ?")
            ->execute([$requestId]);
        $pdo->prepare("INSERT INTO repair_history (request_id, changed_by_user_id, old_status, new_status, note)
            VALUES (?, ?, 'IN_PROGRESS', 'COMPLETED', 'นักศึกษายืนยันงานซ่อมแล้ว')")
            ->execute([$requestId, $studentId]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}
