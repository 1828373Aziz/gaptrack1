<?php
require_once 'config/db.php';

// جلب كل مناطق ضعف التغطية
$zones_result = $conn->query("SELECT * FROM coverage_gap_zones");
$zones = [];
while ($z = $zones_result->fetch_assoc()) { $zones[] = $z; }

// معالجة كل شحنة على حدة
$shipments = $conn->query("SELECT id FROM shipments");
$processed = 0;
$alerts_created = 0;

while ($ship = $shipments->fetch_assoc()) {
    $sid = $ship['id'];
    $points = $conn->query("SELECT * FROM tracking_updates WHERE shipment_id = $sid ORDER BY timestamp ASC");

    $prev_time = null;
    while ($p = $points->fetch_assoc()) {
        $lat = $p['lat']; $lng = $p['lng'];
        $time = strtotime($p['timestamp']);

        $inZone = null;
        foreach ($zones as $z) {
            if ($lat >= $z['lat_min'] && $lat <= $z['lat_max'] && $lng >= $z['lng_min'] && $lng <= $z['lng_max']) {
                $inZone = $z; break;
            }
        }

        $newStatus = 'connected';
        $alertType = null;
        $alertMsg = null;

        if ($inZone) {
            $newStatus = 'gap_zone';
            $alertType = 'gap_zone_entry';
            $alertMsg = "الشحنة رقم {$sid} دخلت منطقة \"{$inZone['zone_name']}\" — الوصول المتوقع لعودة الإشارة خلال {$inZone['expected_gap_minutes']} دقيقة تقريبًا.";
        }

        if ($prev_time !== null) {
            $gapMinutes = ($time - $prev_time) / 60;
            if ($gapMinutes > 90 && !$inZone) {
                $newStatus = 'abnormal_gap';
                $alertType = 'abnormal_gap';
                $alertMsg = "تنبيه: الشحنة رقم {$sid} — انقطاع غير متوقع لمدة " . round($gapMinutes) . " دقيقة خارج أي منطقة معروفة.";
            }
        }
        $prev_time = $time;

        if ($newStatus !== $p['connection_status']) {
            $conn->query("UPDATE tracking_updates SET connection_status = '$newStatus' WHERE id = {$p['id']}");
            $processed++;
        }

        if ($alertType) {
            $exists = $conn->query("SELECT id FROM alerts WHERE shipment_id = $sid AND alert_type = '$alertType' AND message = '" . $conn->real_escape_string($alertMsg) . "'");
            if ($exists->num_rows === 0) {
                $stmt = $conn->prepare("INSERT INTO alerts (shipment_id, alert_type, message) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $sid, $alertType, $alertMsg);
                $stmt->execute();
                $alerts_created++;
            }
        }
    }
}

// إعادة التوجيه لو تم استدعاء الملف من add_shipment.php
if (isset($_GET['redirect'])) {
    header("Location: " . $_GET['redirect']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title>معالجة الكشف</title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body style="padding:40px; text-align:center;">
    <div class="card" style="max-width:500px; margin:0 auto;">
        <h2 style="color:#1e3a8a;