<?php
$shipments = $data['shipments'];
$zones = $data['zones'];
$knownCount = 0;
$abnormalCount = 0;
$activity = [];
foreach ($shipments as $shipment) {
    foreach (classify_points($shipment, $zones) as $point) {
        if ($point['classification'] === 'known_gap') {
            $knownCount++;
            $activity[] = [
                'type' => 'known_gap',
                'title' => 'دخول منطقة ضعف تغطية',
                'description' => $point['note'],
                'shipment' => $shipment,
                'point' => $point,
            ];
        } elseif ($point['classification'] === 'abnormal_gap') {
            $abnormalCount++;
            $activity[] = [
                'type' => 'abnormal_gap',
                'title' => 'انقطاع غير متوقع',
                'description' => $point['note'],
                'shipment' => $shipment,
                'point' => $point,
            ];
        }
    }
}
usort($activity, static fn(array $a, array $b): int => strtotime($b['point']['time']) <=> strtotime($a['point']['time']));
$recentShipments = $shipments;
usort($recentShipments, static fn(array $a, array $b): int => strtotime($b['created_at']) <=> strtotime($a['created_at']));
?>
<section class="page-heading">
  <div><p class="eyebrow">مركز المتابعة · اليوم</p><h1>صباح الخير، فريق العمليات</h1><p>صورة واضحة لحالة الإشارة على طرق المملكة.</p></div>
  <a class="button button-primary" href="<?= e(page_url('shipments')) ?>#new-shipment"><span class="button-symbol">＋</span> إضافة شحنة</a>
</section>

<section class="metrics-grid" aria-label="ملخص النظام">
  <article class="metric-card"><span class="metric-icon green">◇</span><div><small>إجمالي الشحنات</small><strong><?= number_format(count($shipments), 0, '.', '٬') ?></strong><span class="metric-note">ضمن نطاق المتابعة</span></div></article>
  <article class="metric-card"><span class="metric-icon teal">➤</span><div><small>في الطريق</small><strong><?= number_format(count(array_filter($shipments, static fn(array $shipment): bool => $shipment['status'] === 'in_transit')), 0, '.', '٬') ?></strong><span class="metric-note">رحلات نشطة الآن</span></div></article>
  <article class="metric-card"><span class="metric-icon amber">▥</span><div><small>فجوات متوقعة</small><strong><?= number_format($knownCount, 0, '.', '٬') ?></strong><span class="metric-note">ضمن مناطق مسجلة</span></div></article>
  <article class="metric-card"><span class="metric-icon red">△</span><div><small>انقطاعات مقلقة</small><strong><?= number_format($abnormalCount, 0, '.', '٬') ?></strong><span class="metric-note">تحتاج إلى مراجعة</span></div></article>
</section>

<section class="insight-banner">
  <span class="insight-icon">✓</span>
  <div class="insight-copy"><b>الإشارة قد تنقطع أحيانًا؛ ليس كل انقطاع مشكلة.</b><p>يفرق GapTrack بين فجوة تغطية معروفة وبين انقطاع طويل خارج المناطق المسجلة.</p></div>
  <div class="insight-stats"><b><?= number_format(count($zones), 0, '.', '٬') ?></b><small>منطقة مسجلة</small></div>
  <a href="<?= e(page_url('about')) ?>" class="text-link">كيف يعمل؟ <span>←</span></a>
</section>

<div class="dashboard-grid">
  <section class="panel activity-panel">
    <div class="panel-heading"><div><p class="eyebrow">آخر المستجدات</p><h2>نشاط الرحلات</h2></div><a class="text-link" href="<?= e(page_url('reports')) ?>">عرض التحليل <span>←</span></a></div>
    <?php if ($activity): ?>
      <div class="activity-list">
        <?php foreach (array_slice($activity, 0, 5) as $event): ?>
          <a class="activity-row" href="<?= e(page_url('shipment', ['id' => $event['shipment']['id']])) ?>">
            <span class="event-dot <?= e(classification_class($event['type'])) ?>"></span>
            <span class="activity-main"><b><?= e($event['title']) ?></b><small><?= e($event['description']) ?></small></span>
            <span class="activity-code"><?= e($event['shipment']['tracking_code']) ?></span>
            <time><?= e(format_time($event['point']['time'], 'j/n · H:i')) ?></time>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><b>لا توجد تنبيهات حاليًا</b><p>ستظهر هنا الأحداث عند تسجيل نقاط تتبع جديدة.</p></div>
    <?php endif; ?>
  </section>

  <section class="panel fleet-panel">
    <div class="panel-heading"><div><p class="eyebrow">لقطة الأسطول</p><h2>حالة الشحنات</h2></div><a class="text-link" href="<?= e(page_url('shipments')) ?>">كل الشحنات <span>←</span></a></div>
    <div class="fleet-list">
      <?php foreach (array_slice($recentShipments, 0, 4) as $shipment): $health = shipment_health($shipment, $zones); ?>
        <a class="fleet-row" href="<?= e(page_url('shipment', ['id' => $shipment['id']])) ?>">
          <span class="fleet-icon">◇</span>
          <span class="fleet-route"><b><?= e($shipment['origin']) ?> <span>←</span> <?= e($shipment['destination']) ?></b><small class="mono"><?= e($shipment['tracking_code']) ?></small></span>
          <span class="status-pill <?= e(classification_class($health)) ?>"><?= e(classification_label($health)) ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$recentShipments): ?><div class="empty-state"><b>لا توجد شحنات بعد</b><p>أضف شحنة جديدة لبدء عرض المسارات.</p><a class="text-link" href="<?= e(page_url('shipments')) ?>">إضافة شحنة ←</a></div><?php endif; ?>
    </div>
    <div class="fleet-footer"><span class="online-dot"></span> <?= $storageMode === 'mysql' ? 'سجلات محفوظة في قاعدة MySQL المحلية' : 'البيانات الحالية للعرض التجريبي فقط' ?></div>
  </section>
</div>
