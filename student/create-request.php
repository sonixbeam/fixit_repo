<?php
require_once __DIR__ . '/../includes/auth.php';

$user = requireRole('STUDENT');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ดึงห้องพักของนักศึกษาจากฐานข้อมูล
$stmt = $pdo->prepare(
    "SELECT u.room_id, r.room_number
     FROM `user` u
     JOIN room r ON u.room_id = r.room_id
     WHERE u.user_id = :user_id
       AND u.role = 'STUDENT'"
);
$stmt->execute(['user_id' => $user['user_id']]);
$studentRoom = $stmt->fetch();

// ดึงหมวดหมู่ที่เปิดใช้งาน
$categories = $pdo->query(
    "SELECT category_id, category_name
     FROM repair_category
     WHERE is_active = 1
     ORDER BY category_id"
)->fetchAll();

// ดึงพื้นที่ส่วนกลางที่เปิดใช้งาน
$areas = $pdo->query(
    "SELECT area_id, area_name
     FROM common_area
     WHERE is_active = 1
     ORDER BY area_id"
)->fetchAll();

$error = '';
$successId = filter_input(
    INPUT_GET,
    'success',
    FILTER_VALIDATE_INT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationType = $_POST['location_type'] ?? '';
    $areaId = (int) ($_POST['area_id'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // นักศึกษาไม่สามารถกำหนด Priority เองได้ จึงให้ defualt เป็น MEDIUM
    $priority = 'MEDIUM';

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'คำขอไม่ถูกต้อง กรุณาโหลดหน้าใหม่';
    } elseif (!in_array(
        $locationType,
        ['ROOM', 'AREA'],
        true
    )) {
        $error = 'กรุณาเลือกสถานที่แจ้งซ่อม';
    } elseif ($locationType === 'ROOM' && !$studentRoom) {
        $error = 'บัญชีนี้ไม่มีข้อมูลห้องพัก';
    } elseif ($title === '' || $description === '') {
        $error = 'กรุณากรอกหัวข้อและรายละเอียด';
    } elseif (
    mb_strlen($title, 'UTF-8') > 150 ||
    mb_strlen($description, 'UTF-8') > 5000
    ) {
        $error = 'หัวข้อหรือรายละเอียดมีความยาวเกินกำหนด';
    }

    // ตรวจสอบหมวดหมู่จากฐานข้อมูลจริง
    if ($error === '') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM repair_category
             WHERE category_id = ?
               AND is_active = 1"
        );
        $stmt->execute([$categoryId]);

        if (!$stmt->fetchColumn()) {
            $error = 'หมวดหมู่การซ่อมไม่ถูกต้อง';
        }
    }

    // ตรวจสอบพื้นที่ส่วนกลาง
    if ($error === '' && $locationType === 'AREA') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM common_area
             WHERE area_id = ?
               AND is_active = 1"
        );
        $stmt->execute([$areaId]);

        if (!$stmt->fetchColumn()) {
            $error = 'พื้นที่ส่วนกลางไม่ถูกต้อง';
        }
    }

    // ตรวจสอบไฟล์ก่อนบันทึก
    $uploadedFile = $_FILES['image'] ?? null;
    $hasImage = $uploadedFile !== null
        && $uploadedFile['error'] !== UPLOAD_ERR_NO_FILE;

    $extension = '';

    if ($error === '' && $hasImage) {
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $error = 'อัปโหลดรูปภาพไม่สำเร็จ';
        } elseif ($uploadedFile['size'] > 5 * 1024 * 1024) {
            $error = 'รูปภาพต้องมีขนาดไม่เกิน 5 MB';
        } elseif (!is_uploaded_file(
            $uploadedFile['tmp_name']
        )) {
            $error = 'ไฟล์อัปโหลดไม่ถูกต้อง';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))
                ->file($uploadedFile['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedTypes[$mime])) {
                $error = 'รองรับเฉพาะ JPG, PNG และ WEBP';
            } else {
                $extension = $allowedTypes[$mime];
            }
        }
    }

    if ($error === '') {
        $roomId = $locationType === 'ROOM'
            ? (int) $studentRoom['room_id']
            : null;

        $selectedAreaId = $locationType === 'AREA'
            ? $areaId
            : null;

        $imagePath = null;
        $absolutePath = null;

        try {
            // รูปภาพมีขนาดเล็ก ตรวจสอบก่อนย้ายไฟล์
            if ($hasImage) {
                $uploadDir = __DIR__
                    . '/../uploads/repairs/';

                if (!is_dir($uploadDir)) {
                    throw new RuntimeException(
                        'ไม่พบโฟลเดอร์สำหรับเก็บรูปภาพ'
                    );
                }

                $filename = bin2hex(random_bytes(16))
                    . '.' . $extension;

                $absolutePath = $uploadDir . $filename;
                $imagePath = 'uploads/repairs/' . $filename;

                if (!move_uploaded_file(
                    $uploadedFile['tmp_name'],
                    $absolutePath
                )) {
                    throw new RuntimeException(
                        'ไม่สามารถบันทึกรูปภาพได้'
                    );
                }
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO repair_request
                 (
                    user_id, room_id, area_id,
                    category_id, title, description,
                    priority, status, image_path
                 )
                 VALUES
                 (
                    :user_id, :room_id, :area_id,
                    :category_id, :title, :description,
                    :priority, 'PENDING', :image_path
                 )"
            );

            $stmt->execute([
                'user_id' => $user['user_id'],
                'room_id' => $roomId,
                'area_id' => $selectedAreaId,
                'category_id' => $categoryId,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'image_path' => $imagePath
            ]);

            $requestId = (int) $pdo->lastInsertId();

            // บันทึกประวัติการสร้างคำขอ
            $stmt = $pdo->prepare(
                "INSERT INTO repair_history
                 (
                    request_id, changed_by_user_id,
                    old_status, new_status, note
                 )
                 VALUES
                 (
                    :request_id, :user_id,
                    NULL, 'PENDING', :note
                 )"
            );

            $stmt->execute([
                'request_id' => $requestId,
                'user_id' => $user['user_id'],
                'note' => 'นักศึกษาสร้างรายการแจ้งซ่อม'
            ]);

            $pdo->commit();

            // เปลี่ยน Token หลังส่งคำขอสำเร็จ
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );

            header(
                'Location: create-request.php?success='
                . $requestId
            );
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // ลบภาพถ้าการบันทึกฐานข้อมูลไม่สำเร็จ
            if (
                $absolutePath !== null &&
                is_file($absolutePath)
            ) {
                unlink($absolutePath);
            }

            error_log($e->getMessage());
            $error = 'บันทึกคำขอไม่สำเร็จ กรุณาลองใหม่';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อมใหม่ - FixIT</title>
    <link rel="stylesheet"
          href="../assets/css/style.css">
</head>
<body>

<header class="navbar">
    <h2>FixIT | Student</h2>
    <a href="dashboard.php">กลับหน้าหลัก</a>
</header>

<main class="container">
    <h1>แจ้งซ่อมใหม่</h1>
    <p class="subtitle">
        กรอกข้อมูลปัญหาที่ต้องการแจ้งซ่อม
    </p>

    <?php if ($successId): ?>
        <section class="panel">
            <h3>ส่งคำขอเรียบร้อยแล้ว</h3>
            <p>
                เลขที่คำขอ:
                #<?= e($successId) ?>
            </p>
            <a href="dashboard.php">กลับหน้าหลัก</a>
        </section>
    <?php else: ?>

    <section class="panel">

        <?php if ($error !== ''): ?>
            <p style="color: #dc2626;">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST"
              enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <h3>1. สถานที่แจ้งซ่อม</h3>

            <label>
                <input
                    type="radio"
                    name="location_type"
                    value="ROOM"
                    checked
                >
                ห้องพักของฉัน
                <?php if ($studentRoom): ?>
                    (<?= e($studentRoom['room_number']) ?>)
                <?php endif; ?>
            </label>

            <br><br>

            <label>
                <input
                    type="radio"
                    name="location_type"
                    value="AREA"
                >
                พื้นที่ส่วนกลาง
            </label>

            <div id="area-section"
                 style="display: none; margin-top: 12px;">

                <label for="area_id">
                    เลือกพื้นที่ส่วนกลาง
                </label>

                <select name="area_id" id="area_id">
                    <option value="">-- เลือกพื้นที่ --</option>

                    <?php foreach ($areas as $area): ?>
                        <option
                            value="<?= e($area['area_id']) ?>"
                        >
                            <?= e($area['area_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3>2. ข้อมูลปัญหา</h3>

            <label for="category_id">
                หมวดหมู่การซ่อม
            </label>
            <br>

            <select
                name="category_id"
                id="category_id"
                required
            >
                <option value="">-- เลือกหมวดหมู่ --</option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= e($category['category_id']) ?>"
                    >
                        <?= e($category['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <label for="title">หัวข้อปัญหา</label>
            <br>
            <input
                type="text"
                name="title"
                id="title"
                maxlength="150"
                required
                style="width: 100%; padding: 10px;"
            >

            <br><br>

            <label for="description">
                รายละเอียดปัญหา
            </label>
            <br>
            <textarea
                name="description"
                id="description"
                rows="5"
                maxlength="5000"
                required
                style="width: 100%; padding: 10px;"
            ></textarea>

            <h3>3. รูปภาพประกอบ (ไม่บังคับ)</h3>

            <input
                type="file"
                name="image"
                accept="image/jpeg,image/png,image/webp"
            >

            <p class="subtitle">
                รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 5 MB
            </p>

            <br>

            <button type="submit"
                    style="padding: 12px 24px;">
                ส่งคำขอแจ้งซ่อม
            </button>

        </form>
    </section>

    <?php endif; ?>
</main>

<script>
const locationRadios = document.querySelectorAll(
    'input[name="location_type"]'
);

const areaSection = document.getElementById(
    'area-section'
);

const areaSelect = document.getElementById(
    'area_id'
);

if (areaSection && areaSelect) {
    function updateLocation() {
        const selected = document.querySelector(
            'input[name="location_type"]:checked'
        );

        if (!selected) return;

        const isCommonArea = selected.value === 'AREA';

        areaSection.style.display = isCommonArea
            ? 'block'
            : 'none';

        areaSelect.required = isCommonArea;
    }

    locationRadios.forEach(radio => {
        radio.addEventListener('change', updateLocation);
    });

    updateLocation();
}
</script>

</body>
</html>