<?php
$zones = $data['zones'];
$zoneStats = [];
$incidents = [];
$knownCount = 0;
foreach ($zones as $zone) {
    $zoneStats[(int)$zone['id']] = ['zone' => $zone, 'hits' => 0];
}
foreach ($data['shipments'] as $shipment) {
    foreach (classify_points($shipment, $zones) as $point) {
        if ($point['classification'] === 'known_gap') {
            $knownCount++;
            foreach ($zones as $zone) {
                if (point_in_zone((float)$point['lat'], (float)$point['lng'], $zone)) {
                    $zoneStats[(int)$zone['id']]['hits']++;
                    break;
                }
            }
        } elseif ($point['classification'] === 'abnormal_gap') {
            $incidents[] = [
                'shipment' => $shipment,
                'point' => $point,
            ];
        }
    }
}
usort($incidents, static fn(array $a, array $b): int => strtotime($b['point']['time']) <=> strtotime($a['point']['time']));
uasort($zoneStats, static fn(array $a, array $b): int => $b['hits'] <=> $a['hits']);
$affectedCount = $knownCount + count($incidents);
$cityLabels = [
    ['تبوك', 28.3838, 36.5550], ['حائل', 27.5114, 41.6900], ['الرياض', 24.7136, 46.6753],
    ['جدة', 21.4858, 39.1925], ['الدمام', 26.4207, 50.0888], ['نجران', 17.4933, 44.1277],
];
?>
<section class="page-heading"><div><p class="eyebrow">رؤية تشغيلية</p><h1>تحليل التغطية</h1><p>اعرف أين تتكرر فجوات الإشارة، وما الذي يستحق المتابعة.</p></div><span class="report-period">◷ منذ بدء التتبع</span></section>

<section class="report-kpis">
  <article class="report-kpi kpi-ink"><span class="kpi-label">النقاط المتأثرة</span><b><?= number_format($affectedCount, 0, '.', '٬') ?></b><small>نقاط فقدت الاتصال أثناء الرحلة</small></article>
  <article class="report-kpi"><span class="kpi-label">فجوات متوقعة</span><b><?= number_format($knownCount, 0, '.', '٬') ?></b><small>ضمن مناطق مسجلة</small><div class="kpi-bar"><i style="width:<?= $affectedCount ? round(($knownCount / $affectedCount) * 100) : 0 ?>%"></i></div></article>
  <article class="report-kpi kpi-warning"><span class="kpi-label">انقطاعات مقلقة</span><b><?= number_format(count($incidents), 0, '.', '٬') ?></b><small>تحتاج إلى مراجعة تشغيلية</small><div class="kpi-bar"><i style="width:<?= $affectedCount ? round((count($incidents) / $affectedCount) * 100) : 0 ?>%"></i></div></article>
</section>

<section class="panel heatmap-panel" data-testid="coverage-heatmap">
  <div class="panel-heading"><div><p class="eyebrow">التوزيع الجغرافي</p><h2>خريطة حرارية لمناطق الانقطاع</h2></div><span class="table-note">◎ المملكة العربية السعودية</span></div>
  <p class="panel-description">تركيز اللون يوضح تكرار نقاط ضعف الإشارة المسجلة، وتظهر الانقطاعات غير المتوقعة باللون الأحمر.</p>
  <?php if ($affectedCount): ?>
    <div class="heatmap-canvas">
      <svg class="coverage-map-svg" viewBox="0 0 960 600" role="img" aria-label="خريطة حرارية توضيحية لمناطق الانقطاع">
        <defs>
          <radialGradient id="heat-known"><stop offset="0%" stop-color="#d3a340" stop-opacity=".82"></stop><stop offset="45%" stop-color="#e4bd68" stop-opacity=".48"></stop><stop offset="100%" stop-color="#efcf87" stop-opacity="0"></stop></radialGradient>
          <radialGradient id="heat-abnormal"><stop offset="0%" stop-color="#bd5148" stop-opacity=".85"></stop><stop offset="45%" stop-color="#d77961" stop-opacity=".52"></stop><stop offset="100%" stop-color="#e8a18d" stop-opacity="0"></stop></radialGradient>
        </defs>
        <rect width="960" height="600" rx="12" fill="#eef0e7"></rect>
        <?php for ($i = 0; $i < 5; $i++): ?><line x1="<?= 42 + ($i * 217.5) ?>" y1="30" x2="<?= 42 + ($i * 217.5) ?>" y2="550" class="map-grid-line"></line><line x1="42" y1="<?= 30 + ($i * 130) ?>" x2="912" y2="<?= 30 + ($i * 130) ?>" class="map-grid-line"></line><?php endfor; ?>
        <polygon points="<?= e(saudi_outline_points()) ?>" fill="#e3e9dc" stroke="#b8c4b4" stroke-width="2" stroke-linejoin="round"></polygon>
        <?php foreach ($zoneStats as $stat): if ($stat['hits'] < 1) continue; $zone = $stat['zone']; $center = geo_to_svg(((float)$zone['lat_min'] + (float)$zone['lat_max']) / 2, ((float)$zone['lng_min'] + (float)$zone['lng_max']) / 2); $radius = min(112, 58 + ($stat['hits'] * 12)); ?>
          <g><title><?= e($zone['name'] . ': ' . $stat['hits'] . ' نقطة متأثرة') ?></title><circle cx="<?= $center['x'] ?>" cy="<?= $center['y'] ?>" r="<?= $radius ?>" fill="url(#heat-known)"></circle><circle cx="<?= $center['x'] ?>" cy="<?= $center['y'] ?>" r="5" fill="#bd8c2e" stroke="#fffaf0" stroke-width="2"></circle></g>
        <?php endforeach; ?>
        <?php foreach ($incidents as $incident): $coordinate = geo_to_svg((float)$incident['point']['lat'], (float)$incident['point']['lng']); ?>
          <g><title><?= e($incident['shipment']['tracking_code'] . ': انقطاع ' . $incident['point']['gap_minutes'] . ' دقيقة') ?></title><circle cx="<?= $coordinate['x'] ?>" cy="<?= $coordinate['y'] ?>" r="55" fill="url(#heat-abnormal)"></circle><circle cx="<?= $coordinate['x'] ?>" cy="<?= $coordinate['y'] ?>" r="5" fill="#b74840" stroke="#fffaf0" stroke-width="2"></circle></g>
        <?php endforeach; ?>
        <?php foreach ($cityLabels as [$city, $lat, $lng]): $coordinate = geo_to_svg((float)$lat, (float)$lng); ?>
          <g class="map-city"><circle cx="<?= $coordinate['x'] ?>" cy="<?= $coordinate['y'] ?>" r="2.5"></circle><text x="<?= $coordinate['x'] + 7 ?>" y="<?= $coordinate['y'] - 6 ?>"><?= e($city) ?></text></g>
        <?php endforeach; ?>
      </svg>
    </div>
    <div class="heatmap-legend"><span><i class="warning"></i> منطقة ضعف معروفة</span><span><i class="danger"></i> انقطاع غير متوقع</span><small><?= $storageMode === 'mysql' ? 'مبني على سجلات قاعدة MySQL' : 'البيانات الحالية تجريبية' ?></small></div>
  <?php else: ?><div class="empty-state"><b>لا توجد نقاط انقطاع بعد</b><p>ستظهر الخريطة بعد تسجيل نقاط تتبع متأثرة.</p></div><?php endif; ?>
</section>

<div class="dashboard-grid report-lower-grid">
  <section class="panel table-panel">
    <div class="panel-heading"><div><p class="eyebrow">تأثير المناطق</p><h2>أكثر المناطق تكرارًا</h2></div><a class="text-link" href="<?= e(page_url('zones')) ?>">إدارة المناطق ←</a></div>
    <?php if ($zoneStats): ?><div class="zone-stat-list">
      <?php $maxHits = max(1, max(array_column($zoneStats, 'hits'))); foreach ($zoneStats as $stat): $zone = $stat['zone']; ?>
        <div class="zone-stat"><div><b><?= e($zone['name']) ?></b><span><?= number_format($stat['hits'], 0, '.', '٬') ?> نقطة · مدة متوقعة <?= number_format((int)$zone['expected_gap_minutes'], 0, '.', '٬') ?> دقيقة</span></div><div class="zone-stat-bar"><i style="width:<?= max(4, (int)round(($stat['hits'] / $maxHits) * 100)) ?>%"></i></div></div>
      <?php endforeach; ?>
    </div><?php else: ?><div class="empty-state"><b>لا توجد مناطق مسجلة</b><p>أضف منطقة تغطية لبدء التحليل.</p></div><?php endif; ?>
  </section>
  <section class="panel table-panel">
    <div class="panel-heading"><div><p class="eyebrow">أثر الرحلات</p><h2>أحدث الانقطاعات المقلقة</h2></div><span class="count-tag"><?= number_format(count($incidents), 0, '.', '٬') ?></span></div>
    <?php if ($incidents): ?>
      <div class="incident-list"><?php foreach (array_slice($incidents, 0, 6) as $incident): ?><a class="incident-row" href="<?= e(page_url('shipment', ['id' => $incident['shipment']['id']])) ?>"><span class="event-dot danger"></span><span><b class="mono"><?= e($incident['shipment']['tracking_code']) ?></b><small><?= e($incident['shipment']['origin']) ?> ← <?= e($incident['shipment']['destination']) ?> · <?= number_format($incident['point']['gap_minutes'], 0, '.', '٬') ?> دقيقة</small></span><time><?= e(format_time($incident['point']['time'], 'j/n · H:i')) ?></time></a><?php endforeach; ?></div>
    <?php else: ?><div class="empty-state"><b>لا توجد انقطاعات مقلقة</b><p>ستظهر السجلات هنا عند رصد فجوات طويلة خارج المناطق المسجلة.</p></div><?php endif; ?>
  </section>
</div>
