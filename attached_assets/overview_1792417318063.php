<?php
require_once 'config/db.php';

$total_shipments = $conn->query("SELECT COUNT(*) c FROM shipments")->fetch_assoc()['c'];
$total_zones = $conn->query("SELECT COUNT(*) c FROM coverage_gap_zones")->fetch_assoc()['c'];
$abnormal_count = $conn->query("SELECT COUNT(*) c FROM tracking_updates WHERE connection_status='abnormal_gap'")->fetch_assoc()['c'];
$gap_count = $conn->query("SELECT COUNT(*) c FROM tracking_updates WHERE connection_status='gap_zone'")->fetch_assoc()['c'];

$shipments = $conn->query("SELECT * FROM shipments ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>GapTrack — النظرة العامة</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="topnav">
  <div class="brand">🚚 GapTrack</div>
  <div class="links">
    <a href="index.php">عن المشروع</a>
    <a href="overview.php" class="active">النظرة العامة</a>
    <a href="dashboard.php">تتبع الشحنة</a>
    <a href="reports.php">التقرير التحليلي</a>
    <a href="add_shipment.php">+ شحنة جديدة</a>
    <a href="zones.php">إدارة المناطق</a>
  </div>
</nav>

<div class="page-wrap">
  <h1 class="page-title">نظرة عامة على النظام</h1>
  <p class="page-sub">ملخص لحالة الشحنات والتنبيهات ومناطق ضعف التغطية</p>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="num"><?= $total_shipments ?></div>
      <div class="label">إجمالي الشحنات المسجلة</div>
    </div>
    <div class="stat-card warning">
      <div class="num"><?= $gap_count ?></div>
      <div class="label">دخول مناطق ضعف تغطية معروفة</div>
    </div>
    <div class="stat-card danger">
      <div class="num"><?= $abnormal_count ?></div>
      <div class="label">انقطاعات غير طبيعية مكتشفة</div>
    </div>
    <div class="stat-card success">
      <div class="num"><?= $total_zones ?></div>
      <div class="label">مناطق ضعف تغطية مسجلة بالنظام</div>
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px; color:#60a5fa; margin-bottom:6px;">📦 الشحنات</h2>
    <table class="data-table">
      <tr>
        <th>المعرف</th><th>الانطلاق</th><th>الوجهة</th><th>النوع</th><th>الحالة</th><th></th>
      </tr>
      <?php while ($row = $shipments->fetch_assoc()): ?>
      <tr>
        <td>#<?= $row['id'] ?></td>
        <td><?= $row['origin'] ?></td>
        <td><?= $row['destination'] ?></td>
        <td><span class="badge badge-info"><?= $row['type'] === 'land' ? 'بري' : 'بحري' ?></span></td>
        <td><span class="badge badge-success"><?= $row['status'] ?></span></td>
        <td><a href="dashboard.php?id=<?= $row['id'] ?>" class="btn-link">تتبع →</a></td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>

</body>
</html>