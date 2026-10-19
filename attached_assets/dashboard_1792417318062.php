<?php
require_once 'config/db.php';

$shipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$zones_result = $conn->query("SELECT * FROM coverage_gap_zones");
$zones = [];
while ($z = $zones_result->fetch_assoc()) { $zones[] = $z; }

$sql = "SELECT * FROM tracking_updates WHERE shipment_id = $shipment_id ORDER BY timestamp ASC";
$result = $conn->query($sql);

$points = [];
$prev_time = null;
$alerts = [];

function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

function zoneCenter($z) {
    return [
        ($z['lat_min'] + $z['lat_max']) / 2,
        ($z['lng_min'] + $z['lng_max']) / 2
    ];
}

while ($row = $result->fetch_assoc()) {
    $lat = $row['lat'];
    $lng = $row['lng'];
    $time = strtotime($row['timestamp']);

    $inZone = null;
    foreach ($zones as $z) {
        if ($lat >= $z['lat_min'] && $lat <= $z['lat_max'] && $lng >= $z['lng_min'] && $lng <= $z['lng_max']) {
            $inZone = $z;
            break;
        }
    }

    $status = 'connected';
    $statusLabel = 'متصل';
    $statusColor = '#22c55e';

    if ($inZone) {
        $status = 'gap_zone';
        $statusLabel = 'منطقة ضعف تغطية معروفة: ' . $inZone['zone_name'];
        $statusColor = '#f59e0b';

        $alerts[] = [
            'type' => 'gap_zone_entry',
            'message' => "الشحنة دخلت منطقة \"{$inZone['zone_name']}\" — آخر موقع معروف مسجل، الوصول المتوقع لعودة الإشارة خلال {$inZone['expected_gap_minutes']} دقيقة تقريبًا.",
        ];
    }

    if ($prev_time !== null) {
        $gapMinutes = ($time - $prev_time) / 60;
        if ($gapMinutes > 90 && !$inZone) {
            $status = 'abnormal_gap';
            $statusLabel = 'انقطاع غير طبيعي!';
            $statusColor = '#ef4444';

            $alerts[] = [
                'type' => 'abnormal_gap',
                'message' => "تنبيه: انقطاع غير متوقع لمدة " . round($gapMinutes) . " دقيقة خارج أي منطقة ضعف تغطية معروفة. يُنصح بالمتابعة الفورية.",
            ];
        }
    }
    $prev_time = $time;

    $points[] = [
        'lat' => $lat, 'lng' => $lng,
        'status' => $status, 'label' => $statusLabel, 'color' => $statusColor,
        'time' => $row['timestamp'],
    ];
}

$predictive_alert = null;
if (!empty($points)) {
    $last = end($points);
    if ($last['status'] === 'connected') {
        $nearestDist = null;
        $nearestZone = null;
        foreach ($zones as $z) {
            [$zlat, $zlng] = zoneCenter($z);
            $dist = haversine($last['lat'], $last['lng'], $zlat, $zlng);
            if ($nearestDist === null || $dist < $nearestDist) {
                $nearestDist = $dist;
                $nearestZone = $z;
            }
        }
        if ($nearestDist !== null && $nearestDist < 150) {
            $estMinutes = round(($nearestDist / 70) * 60);
            $predictive_alert = "توقع استباقي: الشحنة تقترب من منطقة \"{$nearestZone['zone_name']}\" — على بعد " . round($nearestDist) . " كم تقريبًا (الوصول المتوقع خلال {$estMinutes} دقيقة). يُنصح بالتجهيز لاحتمال ضعف الإشارة.";
        }
    }
}

$hasAbnormal = false;
$hasGapZone = false;
foreach ($points as $p) {
    if ($p['status'] === 'abnormal_gap') $hasAbnormal = true;
    if ($p['status'] === 'gap_zone') $hasGapZone = true;
}
if ($hasAbnormal) {
    $badge = ['label' => 'رحلة عالية الخطورة', 'icon' => '⚠️', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.12)'];
} elseif ($hasGapZone) {
    $badge = ['label' => 'مرت بمنطقة ضعف تغطية', 'icon' => '🟡', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)'];
} else {
    $badge = ['label' => 'رحلة سليمة بالكامل', 'icon' => '✅', 'color' => '#4ade80', 'bg' => 'rgba(34,197,94,0.12)'];
}

$points_json = json_encode($points);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>GapTrack — لوحة التحكم</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
  #map {
    height: 520px; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.35);
    border: 1px solid rgba(59,130,246,0.15);
  }
  .dash-container {
    display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap;
  }
  .sidebar { flex: 1; min-width: 300px; }

  .achieve-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 30px; font-size: 13.5px; font-weight: bold;
    margin-bottom: 16px; border: 1px solid;
  }

  .status-row {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13.5px;
    color: #e2e8f0;
  }
  .status-row:last-child { border-bottom: none; }
  .dot { width: 11px; height: 11px; border-radius: 50%; flex-shrink: 0; }
  .alert-box {
    border-radius: 10px; padding: 12px 14px; margin-bottom: 10px;
    font-size: 13.5px; line-height: 1.6; border-right: 4px solid;
  }
  .alert-warning { background: rgba(245,158,11,0.1); border-color: #f59e0b; color: #fbbf24; }
  .alert-danger { background: rgba(239,68,68,0.1); border-color: #ef4444; color: #f87171; }
  .alert-predictive { background: rgba(59,130,246,0.1); border-color: #3b82f6; color: #93c5fd; }
  .no-alerts { color: #94a3b8; font-size: 13.5px; }
  .legend { display: flex; gap: 16px; margin-top: 10px; font-size: 12.5px; flex-wrap: wrap; color: #94a3b8; }
  .legend span { display: flex; align-items: center; gap: 6px; }
  .sim-card { text-align: center; margin-top:14px; }
  .sim-status { margin-right: 12px; font-size: 13px; color: #94a3b8; }
</style>
</head>
<body>

<nav class="topnav">
  <div class="brand">🚚 GapTrack</div>
  <div class="links">
    <a href="index.php">عن المشروع</a>
    <a href="overview.php">النظرة العامة</a>
    <a href="dashboard.php" class="active">تتبع الشحنة</a>
    <a href="reports.php">التقرير التحليلي</a>
    <a href="add_shipment.php">+ شحنة جديدة</a>
    <a href="zones.php">إدارة المناطق</a>
  </div>
</nav>

<div class="page-wrap">
  <h1 class="page-title">تتبع الشحنة رقم #<?= $shipment_id ?></h1>
  <p class="page-sub">تتبع لحظي + كشف ذكي لمناطق ضعف التغطية والانقطاعات غير الطبيعية</p>

  <div class="achieve-badge" style="background: <?= $badge['bg'] ?>; color: <?= $badge['color'] ?>; border-color: <?= $badge['color'] ?>;">
    <?= $badge['icon'] ?> <?= $badge['label'] ?>
  </div>

  <div class="dash-container">
    <div style="flex:2; min-width:320px;">
      <div id="map"></div>
      <div class="card sim-card">
        <button id="simBtn" class="sim-btn">▶️ تشغيل محاكاة حية للشحنة</button>
        <span id="simStatus" class="sim-status"></span>
      </div>
    </div>

    <div class="sidebar">
      <div class="card">
        <h2 style="font-size:15px; margin-bottom:10px; color:#60a5fa;">⚠️ التنبيهات الحالية</h2>

        <?php if ($predictive_alert): ?>
          <div class="alert-box alert-predictive">
            🔮 <?= $predictive_alert ?>
          </div>
        <?php endif; ?>

        <?php if (empty($alerts) && !$predictive_alert): ?>
          <p class="no-alerts">لا توجد تنبيهات حاليًا — الشحنة متصلة بشكل طبيعي.</p>
        <?php else: foreach ($alerts as $a): ?>
          <div class="alert-box <?= $a['type'] === 'abnormal_gap' ? 'alert-danger' : 'alert-warning' ?>">
            <?= $a['message'] ?>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="card">
        <h2 style="font-size:15px; margin-bottom:10px; color:#60a5fa;">📍 سجل حالة النقاط</h2>
        <?php foreach ($points as $i => $p): ?>
          <div class="status-row">
            <span class="dot" style="background: <?= $p['color'] ?>"></span>
            نقطة <?= $i + 1 ?> — <?= $p['label'] ?> <span style="color:#64748b">(<?= $p['time'] ?>)</span>
          </div>
        <?php endforeach; ?>
        <div class="legend">
          <span><span class="dot" style="background:#22c55e"></span> متصل</span>
          <span><span class="dot" style="background:#f59e0b"></span> ضعف تغطية معروف</span>
          <span><span class="dot" style="background:#ef4444"></span> انقطاع غير طبيعي</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  const points = <?= $points_json ?>;
  const map = L.map('map').setView([points[0].lat, points[0].lng], 6);

  L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; Stadia Maps &copy; OpenStreetMap contributors',
    maxZoom: 20
  }).addTo(map);

  const allLatlngs = points.map(p => [p.lat, p.lng]);
  L.polyline(allLatlngs, { color: '#3b82f6', weight: 3, dashArray: '6,6', opacity: 0.5 }).addTo(map);
  map.fitBounds(allLatlngs, { padding: [30, 30] });

  const truckIcon = L.divIcon({ html: '🚚', className: '', iconSize: [30, 30] });
  let truckMarker = null;
  let animatedLine = null;
  let simIndex = 0;
  let simInterval = null;

  document.getElementById('simBtn').addEventListener('click', function() {
    if (simInterval) clearInterval(simInterval);
    if (truckMarker) map.removeLayer(truckMarker);
    if (animatedLine) map.removeLayer(animatedLine);
    simIndex = 0;

    const traveledPoints = [];
    animatedLine = L.polyline([], { color: '#3b82f6', weight: 4 }).addTo(map);
    truckMarker = L.marker([points[0].lat, points[0].lng], { icon: truckIcon }).addTo(map);

    document.getElementById('simStatus').textContent = 'المحاكاة تعمل...';

    simInterval = setInterval(() => {
      if (simIndex >= points.length) {
        clearInterval(simInterval);
        document.getElementById('simStatus').textContent = '✅ اكتملت الرحلة';
        return;
      }
      const p = points[simIndex];
      traveledPoints.push([p.lat, p.lng]);
      animatedLine.setLatLngs(traveledPoints);
      truckMarker.setLatLng([p.lat, p.lng]);

      L.circleMarker([p.lat, p.lng], {
        radius: 8, fillColor: p.color, color: '#0a0e1a', weight: 2, fillOpacity: 1
      }).addTo(map).bindPopup(`<b>نقطة ${simIndex + 1}</b><br>${p.label}<br><small>${p.time}</small>`);

      document.getElementById('simStatus').textContent = `نقطة ${simIndex + 1} من ${points.length} — ${p.label}`;
      simIndex++;
    }, 1500);
  });
</script>
</body>
</html>