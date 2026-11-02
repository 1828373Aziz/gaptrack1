<?php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$shipment = $id === false || $id === null ? null : find_shipment($data, (int)$id);
if ($shipment === null):
?>
  <section class="panel empty-panel"><h1>لم نعثر على هذه الشحنة</h1><p>تحقق من رقم الشحنة أو ارجع إلى قائمة الرحلات.</p><a class="button button-primary" href="<?= e(page_url('shipments')) ?>">العودة إلى الشحنات</a></section>
<?php
else:
  $points = classify_points($shipment, $data['zones']);
  $health = shipment_health($shipment, $data['zones']);
  $predictive = predictive_alert($shipment, $data['zones']);
  $storedAlerts = array_values(array_filter(
      $data['alerts'] ?? [],
      static fn(array $alert): bool => (int)$alert['shipment_id'] === (int)$shipment['id'],
  ));
  if ($storedAlerts) {
      $alerts = array_map(static function (array $alert): array {
          $classification = match ($alert['alert_type']) {
              'gap_zone_entry' => 'known_gap',
              'abnormal_gap' => 'abnormal_gap',
              default => 'connected',
          };
          return [
              'classification' => $classification,
              'label' => match ($alert['alert_type']) {
                  'gap_zone_entry' => 'فجوة متوقعة',
                  'abnormal_gap' => 'انقطاع مقلق',
                  'delivered' => 'تم التسليم',
                  default => 'تنبيه مسجل',
              },
              'note' => $alert['message'],
              'time' => $alert['created_at'],
          ];
      }, $storedAlerts);
  } else {
      $alerts = array_values(array_filter($points, static fn(array $point): bool => in_array($point['classification'], ['known_gap', 'abnormal_gap'], true)));
  }
  if ($predictive !== null) {
      array_unshift($alerts, [
          'classification' => 'predictive',
          'note' => 'تقترب الشحنة من منطقة «' . $predictive['zone_name'] . '» على بعد ' . $predictive['distance_km'] . ' كم تقريبًا.',
          'time' => $predictive['timestamp'],
      ]);
  }
  $mapPoints = array_map(static function (array $point): array {
      $xy = geo_to_svg((float)$point['lat'], (float)$point['lng']);
      return [
          'x' => $xy['x'],
          'y' => $xy['y'],
          'lat' => (float)$point['lat'],
          'lng' => (float)$point['lng'],
          'time' => $point['time'],
          'classification' => $point['classification'],
          'label' => classification_label($point['classification']),
          'note' => $point['note'],
      ];
  }, $points);
  $linePoints = implode(' ', array_map(static fn(array $point): string => $point['x'] . ',' . $point['y'], $mapPoints));
  $routeData = json_encode($mapPoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
  $lastPoint = end($points);
?>
  <div class="detail-back"><a class="back-link" href="<?= e(page_url('shipments')) ?>">→ العودة إلى الشحنات</a><span class="tracking-code mono"><?= e($shipment['tracking_code']) ?></span></div>
  <section class="page-heading">
    <div><p class="eyebrow">ملف الرحلة · <?= $shipment['type'] === 'sea' ? 'نقل بحري تجريبي' : 'نقل بري' ?></p><h1><?= e($shipment['origin']) ?> إلى <?= e($shipment['destination']) ?></h1><p>آخر تحديث: <?= $lastPoint ? e(format_time($lastPoint['time'])) : 'لا توجد نقاط' ?></p></div>
    <span class="status-pill <?= e(classification_class($health)) ?>"><?= e(classification_label($health)) ?></span>
  </section>

  <?php if ($predictive !== null): ?>
    <section class="notice notice-predictive"><span class="notice-symbol">↗</span><div><b>تنبيه استباقي: اقتراب من منطقة تغطية ضعيفة</b><p>تبعد <?= number_format($predictive['distance_km'], 0, '.', '٬') ?> كم عن <?= e($predictive['zone_name']) ?>، والوصول التقديري خلال <?= number_format($predictive['eta_minutes'], 0, '.', '٬') ?> دقيقة. هذا التقدير تقريبي ويستخدم سرعة افتراضية.</p></div></section>
  <?php endif; ?>

  <div class="detail-grid">
    <section class="panel map-panel">
      <div class="panel-heading"><div><p class="eyebrow">الموقع والمسار</p><h2>رحلة الشحنة</h2></div><span class="updated-tag"><i></i><?= $lastPoint ? 'آخر إشارة ' . e(format_time($lastPoint['time'], 'H:i')) : 'بانتظار أول إشارة' ?></span></div>
      <div class="map-visual">
        <svg class="route-map-svg" viewBox="0 0 960 600" role="img" aria-label="مسار تجريبي للشحنة من <?= e($shipment['origin']) ?> إلى <?= e($shipment['destination']) ?>" data-route-map>
          <rect width="960" height="600" rx="12" fill="#eef0e7"></rect>
          <?php for ($i = 0; $i < 5; $i++): ?><line x1="<?= 42 + ($i * 217.5) ?>" y1="30" x2="<?= 42 + ($i * 217.5) ?>" y2="550" class="map-grid-line"></line><line x1="42" y1="<?= 30 + ($i * 130) ?>" x2="912" y2="<?= 30 + ($i * 130) ?>" class="map-grid-line"></line><?php endfor; ?>
          <polygon points="<?= e(saudi_outline_points()) ?>" fill="#e3e9dc" stroke="#b8c4b4" stroke-width="2" stroke-linejoin="round"></polygon>
          <text x="110" y="390" class="map-sea-label" transform="rotate(-72 110 390)">البحر الأحمر</text><text x="840" y="370" class="map-sea-label" transform="rotate(70 840 370)">الخليج العربي</text>
          <?php
          $cityLabels = [
              ['الرياض', 24.7136, 46.6753], ['جدة', 21.4858, 39.1925], ['الدمام', 26.4207, 50.0888],
              ['حائل', 27.5114, 41.6900], ['تبوك', 28.3838, 36.5550],
          ];
          foreach ($cityLabels as [$cityName, $cityLat, $cityLng]):
              $cityPoint = geo_to_svg((float)$cityLat, (float)$cityLng);
          ?>
            <g class="map-city"><circle cx="<?= $cityPoint['x'] ?>" cy="<?= $cityPoint['y'] ?>" r="2.5"></circle><text x="<?= $cityPoint['x'] + 7 ?>" y="<?= $cityPoint['y'] - 6 ?>"><?= e($cityName) ?></text></g>
          <?php endforeach; ?>
          <?php if (count($mapPoints) > 1): ?><polyline points="<?= e($linePoints) ?>" fill="none" stroke="#4a8063" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="7 6"></polyline><?php endif; ?>
          <?php foreach ($mapPoints as $index => $point): ?>
            <g class="route-marker <?= e($point['classification']) ?> <?= $index === 0 ? 'is-active' : '' ?>" data-route-marker="<?= $index ?>" role="img" aria-label="<?= e('نقطة ' . ($index + 1) . ' — ' . $point['label']) ?>">
              <circle class="marker-halo" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="<?= $index === 0 ? 13 : 10 ?>"></circle>
              <circle class="marker-core" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="5"></circle>
              <title><?= e('نقطة ' . ($index + 1) . ' — ' . $point['label']) ?></title>
            </g>
          <?php endforeach; ?>
        </svg>
        <div class="map-legend"><span><i class="success"></i> متصل</span><span><i class="warning"></i> فجوة متوقعة</span><span><i class="danger"></i> انقطاع مقلق</span></div>
      </div>
      <script type="application/json" id="route-data"><?= $routeData ?: '[]' ?></script>
      <div class="playback">
        <button class="button button-secondary" type="button" data-play-route data-testid="button-playback">تشغيل المسار <span>▶</span></button>
        <input type="range" min="0" max="<?= max(0, count($mapPoints) - 1) ?>" value="0" step="1" data-route-slider aria-label="موضع تشغيل المسار" data-testid="slider-route-playback" <?= count($mapPoints) < 2 ? 'disabled' : '' ?>>
        <span class="playback-count" data-route-count>١ / <?= e((string)count($mapPoints)) ?></span>
      </div>
      <div class="active-location" data-route-current>
        <?php if ($mapPoints): ?><span class="online-dot"></span><span><?= e($mapPoints[0]['note'] ?: 'نقطة مسجلة على المسار') ?></span><code><?= number_format($mapPoints[0]['lat'], 4) ?>، <?= number_format($mapPoints[0]['lng'], 4) ?></code><time><?= e(format_time($mapPoints[0]['time'], 'j/n · H:i')) ?></time><?php else: ?>لا توجد نقاط تتبع مسجلة.<?php endif; ?>
      </div>
    </section>

    <section class="panel alert-panel">
      <div class="panel-heading"><div><p class="eyebrow">قراءة الإشارة</p><h2>التنبيهات</h2></div><span class="count-tag"><?= number_format(count($alerts), 0, '.', '٬') ?></span></div>
      <?php if ($alerts): ?>
        <div class="alert-list">
          <?php foreach ($alerts as $alert): $kind = $alert['classification']; $kindClass = $kind === 'predictive' ? 'predictive' : classification_class($kind); $alertTitle = $alert['label'] ?? ($kind === 'predictive' ? 'تنبيه استباقي' : classification_label($kind)); ?>
            <article class="alert-item <?= e($kindClass) ?>"><span class="alert-symbol"><?= $kind === 'predictive' ? '↗' : ($kind === 'abnormal_gap' ? '!' : '▥') ?></span><div><b><?= e($alertTitle) ?></b><p><?= e($alert['note']) ?></p><time><?= e(format_time($alert['time'], 'j/n/Y · H:i')) ?></time></div></article>
          <?php endforeach; ?>
        </div>
      <?php else: ?><div class="empty-state"><b>لا توجد تنبيهات</b><p>حالة الإشارة مستقرة حتى الآن.</p></div><?php endif; ?>
    </section>
  </div>

  <section class="panel points-panel">
      <div class="panel-heading"><div><p class="eyebrow">سجل المواقع</p><h2>نقاط التتبع <span class="count-tag"><?= number_format(count($points), 0, '.', '٬') ?></span></h2></div><span class="panel-description">التصنيف يعتمد على مناطق التغطية المسجلة وفاصل زمني افتراضي قدره ٩٠ دقيقة.</span></div>
    <?php if ($points): ?>
      <div class="table-scroll"><table class="data-table"><thead><tr><th>التصنيف</th><th>الموقع</th><th>الملاحظة</th><th>التوقيت</th><th></th></tr></thead><tbody>
        <?php foreach ($points as $index => $point): ?>
          <tr><td><span class="status-pill <?= e(classification_class($point['classification'])) ?>"><?= e(classification_label($point['classification'])) ?></span></td><td class="mono"><?= number_format((float)$point['lat'], 4) ?>، <?= number_format((float)$point['lng'], 4) ?></td><td><?= e($point['note'] ?? '—') ?></td><td><?= e(format_time($point['time'], 'j/n/Y · H:i')) ?></td><td><button class="row-open" type="button" data-focus-point="<?= $index ?>">عرض على المسار ←</button></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><div class="empty-state"><b>لا توجد نقاط تتبع بعد</b></div><?php endif; ?>
  </section>
<?php endif; ?>
