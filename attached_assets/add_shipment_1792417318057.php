<?php
require_once 'config/db.php';

// إحداثيات مدن سعودية جاهزة
$cities = [
    'الرياض'          => [24.7136, 46.6753],
    'جدة'             => [21.4858, 39.1925],
    'الدمام'          => [26.4207, 50.0888],
    'مكة المكرمة'     => [21.3891, 39.8579],
    'المدينة المنورة' => [24.5247, 39.5692],
    'الطائف'          => [21.2703, 40.4158],
    'تبوك'            => [28.3838, 36.5550],
    'أبها'            => [18.2164, 42.5053],
    'نجران'           => [17.4933, 44.1277],
    'حائل'            => [27.5114, 41.6900],
    'بريدة'           => [26.3260, 43.9750],
    'عنيزة'           => [26.0843, 43.9936],
];

$message = '';

function generateShipment($conn, $cities, $origin, $destination, $type, $points_count) {
    $stmt = $conn->prepare("INSERT INTO shipments (origin, destination, type, status) VALUES (?, ?, ?, 'in_transit')");
    $stmt->bind_param("sss", $origin, $destination, $type);
    $stmt->execute();
    $shipment_id = $conn->insert_id;

    [$lat1, $lng1] = $cities[$origin];
    [$lat2, $lng2] = $cities[$destination];

    $start_time = time();
    for ($i = 0; $i < $points_count; $i++) {
        $fraction = $i / ($points_count - 1);
        $lat = $lat1 + ($lat2 - $lat1) * $fraction;
        $lng = $lng1 + ($lng2 - $lng1) * $fraction;

        if ($i > 0 && $i < $points_count - 1) {
            $lat += (mt_rand(-30, 30) / 1000);
            $lng += (mt_rand(-30, 30) / 1000);
        }

        $point_time = date('Y-m-d H:i:s', $start_time + ($i * 5400));

        $stmt2 = $conn->prepare("INSERT INTO tracking_updates (shipment_id, lat, lng, timestamp, connection_status) VALUES (?, ?, ?, ?, 'connected')");
        $stmt2->bind_param("idds", $shipment_id, $lat, $lng, $point_time);
        $stmt2->execute();
    }
    return $shipment_id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'stress_test') {
        $cityNames = array_keys($cities);
        $lastId = 1;
        for ($n = 0; $n < 5; $n++) {
            $origin = $cityNames[array_rand($cityNames)];
            do { $destination = $cityNames[array_rand($cityNames)]; } while ($destination === $origin);
            $type = (mt_rand(0, 1) === 0) ? 'land' : 'sea';
            $lastId = generateShipment($conn, $cities, $origin, $destination, $type, mt_rand(4, 8));
        }
        header("Location: process_detection.php?redirect=overview.php");
        exit;
    }

    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $type = $_POST['type'];
    $points_count = max(3, min(10, (int)$_POST['points_count']));

    if ($origin === $destination) {
        $message = 'error:نقطة الانطلاق والوجهة لا يمكن أن تكونا نفس المدينة';
    } else {
        $shipment_id = generateShipment($conn, $cities, $origin, $destination, $type, $points_count);
        header("Location: process_detection.php?redirect=dashboard.php?id=$shipment_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>GapTrack — إضافة شحنة</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .form-card { max-width: 520px; margin: 30px auto; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display:block; font-size:13px; color:#94a3b8; margin-bottom:6px; }
    .form-group select, .form-group input {
      width:100%; padding:10px 12px; font-size:14px;
    }
    .error-msg {
      background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.3);
      padding:10px 14px; border-radius:10px; margin-bottom:16px; font-size:13.5px;
    }
    .stress-card {
      max-width: 520px; margin: 0 auto 20px; text-align: center;
      background: linear-gradient(135deg, rgba(239,68,68,0.08), rgba(59,130,246,0.08));
      border: 1px solid rgba(239,68,68,0.2);
    }
    .stress-btn {
      background: linear-gradient(135deg, #ef4444, #f97316); color: white; border: none;
      padding: 11px 22px; border-radius: 30px; font-size: 14px; cursor: pointer;
      box-shadow: 0 0 18px rgba(239,68,68,0.4);
      transition: transform 0.15s;
    }
    .stress-btn:hover { transform: translateY(-2px); }
  </style>
</head>
<body>

<nav class="topnav">
  <div class="brand">🚚 GapTrack</div>
  <div class="links">
    <a href="index.php">عن المشروع</a>
    <a href="overview.php">النظرة العامة</a>
    <a href="dashboard.php">تتبع الشحنة</a>
    <a href="reports.php">التقرير التحليلي</a>
    <a href="add_shipment.php" class="active">+ شحنة جديدة</a>
    <a href="zones.php">إدارة المناطق</a>
  </div>
</nav>

<div class="page-wrap">

  <div class="card stress-card">
    <h2 style="font-size:16px; color:#f87171; margin-bottom:6px;">⚡ محاكاة عاصفة شحنات</h2>
    <p style="font-size:13px; color:#94a3b8; margin-bottom:14px;">
      يضيف 5 شحنات بمسارات عشوائية دفعة وحدة لاختبار النظام تحت ضغط
    </p>
    <form method="POST">
      <input type="hidden" name="action" value="stress_test">
      <button type="submit" class="stress-btn">⚡ تشغيل محاكاة العاصفة</button>
    </form>
  </div>

  <div class="card form-card">
    <h2 style="font-size:17px; color:#60a5fa; margin-bottom:16px;">➕ إضافة شحنة جديدة</h2>

    <?php if (str_starts_with($message, 'error:')): ?>
      <div class="error-msg"><?= substr($message, 6) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>مدينة الانطلاق</label>
        <select name="origin" required>
          <?php foreach ($cities as $name => $coords): ?>
            <option value="<?= $name ?>"><?= $name ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>مدينة الوجهة</label>
        <select name="destination" required>
          <?php foreach ($cities as $name => $coords): ?>
            <option value="<?= $name ?>" <?= $name === 'جدة' ? 'selected' : '' ?>><?= $name ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>نوع النقل</label>
        <select name="type">
          <option value="land">بري</option>
          <option value="sea">بحري</option>
        </select>
      </div>

      <div class="form-group">
        <label>عدد نقاط التتبع المحاكاة (3-10)</label>
        <input type="number" name="points_count" value="5" min="3" max="10">
      </div>

      <button type="submit" class="submit-btn">إضافة الشحنة وبدء التتبع →</button>
    </form>
  </div>
</div>

</body>
</html>