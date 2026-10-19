<?php
require_once 'config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $zone_name = $_POST['zone_name'];
    $lat_min = $_POST['lat_min'];
    $lat_max = $_POST['lat_max'];
    $lng_min = $_POST['lng_min'];
    $lng_max = $_POST['lng_max'];
    $expected_gap = $_POST['expected_gap_minutes'];

    $stmt = $conn->prepare("INSERT INTO coverage_gap_zones (zone_name, lat_min, lat_max, lng_min, lng_max, expected_gap_minutes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddddi", $zone_name, $lat_min, $lat_max, $lng_min, $lng_max, $expected_gap);
    $stmt->execute();
    $message = 'success:تمت إضافة المنطقة بنجاح';
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM coverage_gap_zones WHERE id = $id");
    $message = 'success:تم حذف المنطقة بنجاح';
}

$zones = $conn->query("SELECT * FROM coverage_gap_zones ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>GapTrack — إدارة المناطق</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .form-card { max-width: 600px; margin-bottom: 24px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display:block; font-size:13px; color:#94a3b8; margin-bottom:6px; }
    .form-group input { width:100%; padding:10px 12px; font-size:14px; }
    .success-msg {
      background: rgba(34,197,94,0.1); color: #4ade80; border: 1px solid rgba(34,197,94,0.3);
      padding:10px 14px; border-radius:10px; margin-bottom:16px; font-size:13.5px;
    }
    .delete-link { color:#f87171; text-decoration:none; font-size:13px; }
    .delete-link:hover { text-decoration:underline; }
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
    <a href="add_shipment.php">+ شحنة جديدة</a>
    <a href="zones.php" class="active">إدارة المناطق</a>
  </div>
</nav>

<div class="page-wrap">
  <h1 class="page-title">إدارة مناطق ضعف التغطية</h1>
  <p class="page-sub">أضف أو احذف مناطق ضعف التغطية المعروفة اللي يعتمد عليها النظام بالكشف</p>

  <?php if (str_starts_with($message, 'success:')): ?>
    <div class="success-msg"><?= substr($message, 8) ?></div>
  <?php endif; ?>

  <div class="card form-card">
    <h2 style="font-size:16px; color:#60a5fa; margin-bottom:14px;">➕ إضافة منطقة جديدة</h2>
    <form method="POST">
      <input type="hidden" name="action" value="add">

      <div class="form-group">
        <label>اسم المنطقة</label>
        <input type="text" name="zone_name" placeholder="مثال: صحراء الدهناء" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>خط العرض — الحد الأدنى (lat_min)</label>
          <input type="number" step="0.000001" name="lat_min" placeholder="مثال: 24.000000" required>
        </div>
        <div class="form-group">
          <label>خط العرض — الحد الأقصى (lat_max)</label>
          <input type="number" step="0.000001" name="lat_max" placeholder="مثال: 26.000000" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>خط الطول — الحد الأدنى (lng_min)</label>
          <input type="number" step="0.000001" name="lng_min" placeholder="مثال: 44.000000" required>
        </div>
        <div class="form-group">
          <label>خط الطول — الحد الأقصى (lng_max)</label>
          <input type="number" step="0.000001" name="lng_max" placeholder="مثال: 47.000000" required>
        </div>
      </div>

      <div class="form-group">
        <label>مدة الانقطاع المتوقعة (بالدقائق)</label>
        <input type="number" name="expected_gap_minutes" value="30" required>
      </div>

      <button type="submit" class="submit-btn">إضافة المنطقة</button>
    </form>
  </div>

  <div class="card">
    <h2 style="font-size:16px; color:#60a5fa; margin-bottom:10px;">🗺️ المناطق الحالية</h2>
    <table class="data-table">
      <tr>
        <th>الاسم</th><th>نطاق خط العرض</th><th>نطاق خط الطول</th>
        <th>مدة الانقطاع</th><th></th>
      </tr>
      <?php while ($z = $zones->fetch_assoc()): ?>
      <tr>
        <td><?= $z['zone_name'] ?></td>
        <td><?= $z['lat_min'] ?> — <?= $z['lat_max'] ?></td>
        <td><?= $z['lng_min'] ?> — <?= $z['lng_max'] ?></td>
        <td><?= $z['expected_gap_minutes'] ?> دقيقة</td>
        <td>
          <a href="zones.php?delete=<?= $z['id'] ?>" class="delete-link"
             onclick="return confirm('متأكد تبي تحذف هذي المنطقة؟')">حذف 🗑️</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>

</body>
</html>