<?php
require_once 'config/db.php';

$zones = $conn->query("
    SELECT z.*, 
    (SELECT COUNT(*) FROM tracking_updates t 
     WHERE t.lat BETWEEN z.lat_min AND z.lat_max 
     AND t.lng BETWEEN z.lng_min AND z.lng_max) as hit_count
    FROM coverage_gap_zones z
");

$abnormal = $conn->query("
    SELECT tu.*, s.origin, s.destination 
    FROM tracking_updates tu 
    JOIN shipments s ON tu.shipment_id = s.id 
    WHERE tu.connection_status = 'abnormal_gap'
    ORDER BY tu.timestamp DESC
");

$heat_result = $conn->query("
    SELECT lat, lng, connection_status FROM tracking_updates 
    WHERE connection_status IN ('gap_zone', 'abnormal_gap')
");
$heat_points = [];
while ($h = $heat_result->fetch_assoc()) {
    $weight = $h['connection_status'] === 'abnormal_gap' ? 1.0 : 0.6;
    $heat_points[] = [(float)$h['lat'], (float)$h['lng'], $weight];
}
$heat_json = json_encode($heat_points);
$heat_count = count($heat_points);

$center_lat = 23.8859; $center_lng = 45.0792;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>GapTrack — التقرير التحليلي</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    #heatmap {
      height: 450px; border-radius: 14px; margin-top: 12px;
      border: 1px solid rgba(59,130,246,0.15);
    }
  </style>
</head>
<body>

<nav class="topnav">
  <div class="brand">🚚 GapTrack</div>
  <div class="links">
    <a href="index.php">عن المشروع</a>
    <a href="overview.php">النظرة العامة</a>
    <a href="dashboard.php">تتبع الشحنة</a>
    <a href="reports.php" class="active">التقرير التحليلي</a>
    <a href="add_shipment.php">+ شحنة جديدة</a>
    <a href="zones.php">إدارة المناطق</a>
  </div>
</nav>

<div class="page-wrap">
  <h1 class="page-title">التقرير التحليلي لمناطق ضعف التغطية</h1>
  <p class="page-sub">أكثر المناطق تكرارًا لدخول الشحنات فيها — يفيد في تحديد أولويات تحسين التغطية</p>

  <div class="card" style="margin-bottom:20px;">
    <h2 style="font-size:16px; color:#60a5fa; margin-bottom:6px;">🔥 الخريطة الحرارية لمناطق الانقطاع</h2>
    <p style="font-size:13px; color:#94a3b8; margin-bottom:4px;">
      عدد النقاط المكتشفة لعرضها بالخريطة: <b style="color:#e2e8f0;"><?= $heat_count ?></b>
    </p>
    <div id="heatmap"></div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <h2 style="font-size:16px; color:#60a5fa; margin-bottom:10px;">🗺️ مناطق ضعف التغطية المسجلة</h2>
    <table class="data-table">
      <tr>
        <th>اسم المنطقة</th><th>نطاق خط العرض</th><th>نطاق خط الطول</th>
        <th>مدة الانقطاع المتوقعة</th><th>عدد مرات الدخول</th>
      </tr>
      <?php while ($z = $zones->fetch_assoc()): ?>
      <tr>
        <td><?= $z['zone_name'] ?></td>
        <td><?= $z['lat_min'] ?> — <?= $z['lat_max'] ?></td>
        <td><?= $z['lng_min'] ?> — <?= $z['lng_max'] ?></td>
        <td><?= $z['expected_gap_minutes'] ?> دقيقة</td>
        <td>
          <span class="badge <?= $z['hit_count'] > 0 ? 'badge-info' : 'badge-success' ?>">
            <?= $z['hit_count'] ?> مرة
          </span>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>

  <div class="card">
    <h2 style="font-size:16px; color:#60a5fa; margin-bottom:10px;">🔴 سجل الانقطاعات غير الطبيعية</h2>
    <table class="data-table">
      <tr>
        <th>الشحنة</th><th>الموقع (خط عرض / طول)</th><th>الوقت</th>
      </tr>
      <?php if ($abnormal->num_rows === 0): ?>
        <tr><td colspan="3" style="color:#94a3b8; text-align:center; padding:20px;">لا توجد انقطاعات غير طبيعية مسجلة حاليًا</td></tr>
      <?php else: while ($a = $abnormal->fetch_assoc()): ?>
      <tr>
        <td><?= $a['origin'] ?> ← <?= $a['destination'] ?></td>
        <td><?= $a['lat'] ?>, <?= $a['lng'] ?></td>
        <td><?= $a['timestamp'] ?></td>
      </tr>
      <?php endwhile; endif; ?>
    </table>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
  const heatPoints = <?= $heat_json ?>;

  const map = L.map('heatmap').setView([<?= $center_lat ?>, <?= $center_lng ?>], 6);

  L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; Stadia Maps &copy; OpenStreetMap contributors',
    maxZoom: 20
  }).addTo(map);

  if (heatPoints.length > 0 && typeof L.heatLayer === 'function') {
    const heat = L.heatLayer(heatPoints, {
      radius: 50, blur: 30, maxZoom: 10, max: 1.0,
      gradient: { 0.2: '#3b82f6', 0.4: '#22c55e', 0.6: '#facc15', 0.8: '#f97316', 1.0: '#ef4444' }
    }).addTo(map);

    const bounds = L.latLngBounds(heatPoints.map(p => [p[0], p[1]]));
    map.fitBounds(bounds, { padding: [80, 80], maxZoom: 7 });
  } else if (heatPoints.length === 0) {
    L.popup()
      .setLatLng([<?= $center_lat ?>, <?= $center_lng ?>])
      .setContent('لا توجد بيانات انقطاع كافية بعد لعرضها بالخريطة الحرارية')
      .openOn(map);
  }
</script>
</body>
</html>