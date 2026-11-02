<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../app/helpers.php';

$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'dashboard';
$allowedPages = ['dashboard', 'shipments', 'shipment', 'reports', 'zones', 'about'];
if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'not-found';
}

try {
    $data = load_data();
} catch (Throwable $exception) {
    http_response_code(503);
    $storageErrorMessage = $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'تعذر تحميل بيانات GapTrack. تحقق من إعداد قاعدة البيانات ثم حاول مرة أخرى.';
    error_log('GapTrack data load failed: ' . $exception->getMessage());
    require __DIR__ . '/../views/storage-error.php';
    exit;
}
$storageMode = storage_mode();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $errorMessage = 'انتهت صلاحية النموذج. حدّث الصفحة وحاول مرة أخرى.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'create_shipment') {
            $origin = trim((string)($_POST['origin'] ?? ''));
            $destination = trim((string)($_POST['destination'] ?? ''));
            $transportType = (string)($_POST['transport_type'] ?? 'land');
            $pointsCount = filter_var($_POST['points_count'] ?? null, FILTER_VALIDATE_INT);
            $cities = route_cities();
            if ($origin === $destination) {
                $errorMessage = 'اختر مدينتين مختلفتين للانطلاق والوجهة.';
            } elseif (!isset($cities[$origin], $cities[$destination])) {
                $errorMessage = 'اختر مدينتي الانطلاق والوجهة من القائمة.';
            } elseif (!in_array($transportType, ['land', 'sea'], true)) {
                $errorMessage = 'نوع النقل المحدد غير صالح.';
            } elseif ($pointsCount === false || $pointsCount < 3 || $pointsCount > 10) {
                $errorMessage = 'عدد نقاط التتبع يجب أن يكون بين 3 و10.';
            } else {
                $id = (int)$data['next_shipment_id'];
                $start = $cities[$origin];
                $end = $cities[$destination];
                $points = [];
                $now = time();
                $nextPointId = 1;
                foreach ($data['shipments'] as $existingShipment) {
                    foreach ($existingShipment['points'] as $existingPoint) {
                        $nextPointId = max($nextPointId, (int)$existingPoint['id'] + 1);
                    }
                }
                for ($index = 0; $index < $pointsCount; $index++) {
                    $fraction = $index / ($pointsCount - 1);
                    $jitter = ($index > 0 && $index < $pointsCount - 1) ? sin($index * 4.3) * 0.014 : 0;
                    $points[] = [
                        'id' => $nextPointId + $index,
                        'lat' => $start[0] + (($end[0] - $start[0]) * $fraction) + $jitter,
                        'lng' => $start[1] + (($end[1] - $start[1]) * $fraction) - $jitter,
                        'time' => date(DATE_ATOM, $now - (($pointsCount - $index - 1) * 3600)),
                    ];
                }
                $shipment = [
                    'id' => $id,
                    'tracking_code' => 'GT-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8)),
                    'origin' => $origin,
                    'destination' => $destination,
                    'type' => $transportType,
                    'status' => 'in_transit',
                    'created_at' => date(DATE_ATOM, $now),
                    'points' => $points,
                ];
                if ($storageMode === 'mysql') {
                    $id = create_mysql_shipment($shipment, $data['zones']);
                } else {
                    $data['shipments'][] = $shipment;
                    $data['next_shipment_id'] = $id + 1;
                    save_data($data);
                }
                header('Location: ' . page_url('shipment', ['id' => $id, 'created' => '1']));
                exit;
            }
            $page = 'shipments';
        } elseif ($action === 'create_zone') {
            $name = trim((string)($_POST['name'] ?? ''));
            $latMin = filter_var($_POST['lat_min'] ?? null, FILTER_VALIDATE_FLOAT);
            $latMax = filter_var($_POST['lat_max'] ?? null, FILTER_VALIDATE_FLOAT);
            $lngMin = filter_var($_POST['lng_min'] ?? null, FILTER_VALIDATE_FLOAT);
            $lngMax = filter_var($_POST['lng_max'] ?? null, FILTER_VALIDATE_FLOAT);
            $expectedGap = filter_var($_POST['expected_gap_minutes'] ?? null, FILTER_VALIDATE_INT);
            if (mb_strlen($name) < 2) {
                $errorMessage = 'اكتب اسمًا للمنطقة (حرفان على الأقل).';
            } elseif ($latMin === false || $latMax === false || $lngMin === false || $lngMax === false
                || $latMin < -90 || $latMax > 90 || $lngMin < -180 || $lngMax > 180
                || $latMin >= $latMax || $lngMin >= $lngMax) {
                $errorMessage = 'تحقق من الإحداثيات؛ يجب أن يكون الحد الأدنى أقل من الحد الأقصى.';
            } elseif ($expectedGap === false || $expectedGap < 1 || $expectedGap > 1440) {
                $errorMessage = 'مدة الانقطاع المتوقعة يجب أن تكون بين دقيقة واحدة و24 ساعة.';
            } else {
                $id = (int)$data['next_zone_id'];
                $zone = [
                    'id' => $id,
                    'name' => $name,
                    'lat_min' => (float)$latMin,
                    'lat_max' => (float)$latMax,
                    'lng_min' => (float)$lngMin,
                    'lng_max' => (float)$lngMax,
                    'expected_gap_minutes' => (int)$expectedGap,
                ];
                if ($storageMode === 'mysql') {
                    $id = create_mysql_zone($zone);
                } else {
                    $zone['id'] = $id;
                    $data['zones'][] = $zone;
                    $data['next_zone_id'] = $id + 1;
                    save_data($data);
                }
                header('Location: ' . page_url('zones', ['saved' => '1']));
                exit;
            }
            $page = 'zones';
        } elseif ($action === 'delete_zone') {
            $id = filter_var($_POST['zone_id'] ?? null, FILTER_VALIDATE_INT);
            $zoneExists = false;
            foreach ($data['zones'] as $zone) {
                if ((int)$zone['id'] === $id) {
                    $zoneExists = true;
                    break;
                }
            }
            if ($id === false || !$zoneExists) {
                $errorMessage = 'المنطقة المطلوبة غير موجودة.';
            } else {
                if ($storageMode === 'mysql') {
                    if (!delete_mysql_zone((int)$id)) {
                        $errorMessage = 'المنطقة المطلوبة لم تعد موجودة.';
                        $page = 'zones';
                    }
                } else {
                    $data['zones'] = array_values(array_filter(
                        $data['zones'],
                        static fn(array $zone): bool => (int)$zone['id'] !== (int)$id,
                    ));
                    save_data($data);
                }
                if ($errorMessage !== '') {
                    $page = 'zones';
                } else {
                    header('Location: ' . page_url('zones', ['deleted' => '1']));
                    exit;
                }
            }
            $page = 'zones';
        }
    }
    } catch (Throwable $exception) {
        error_log('GapTrack request failed: ' . $exception->getMessage());
        http_response_code(503);
        $errorMessage = $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'تعذر حفظ التغيير. تحقق من اتصال قاعدة البيانات وحاول مرة أخرى.';
        if (isset($action) && $action === 'create_shipment') {
            $page = 'shipments';
        } elseif (isset($action) && in_array($action, ['create_zone', 'delete_zone'], true)) {
            $page = 'zones';
        }
    }
}

$pageTitles = [
    'dashboard' => 'نظرة عامة',
    'shipments' => 'الشحنات',
    'shipment' => 'تفاصيل الرحلة',
    'reports' => 'تحليل التغطية',
    'zones' => 'مناطق التغطية',
    'about' => 'عن GapTrack',
    'not-found' => 'الصفحة غير موجودة',
];
$csrfToken = save_csrf_token();
$viewFile = __DIR__ . '/../views/' . $page . '.php';
require __DIR__ . '/../views/header.php';
if (is_file($viewFile)) {
    require $viewFile;
} else {
    require __DIR__ . '/../views/not-found.php';
}
require __DIR__ . '/../views/footer.php';
