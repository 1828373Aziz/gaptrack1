<?php
$shipments = $data['shipments'];
$zones = $data['zones'];
usort($shipments, static fn(array $a, array $b): int => strtotime($b['created_at']) <=> strtotime($a['created_at']));
$cities = route_cities();
?>
<section class="page-heading">
  <div><p class="eyebrow">سجل الرحلات</p><h1>الشحنات</h1><p>تابع مسارات الشحنات وحالة الإشارة لكل رحلة.</p></div>
  <a class="button button-primary" href="#new-shipment"><span class="button-symbol">＋</span> شحنة جديدة</a>
</section>

<section class="panel table-panel">
  <div class="panel-heading"><div><p class="eyebrow">قائمة المتابعة</p><h2>جميع الشحنات <span class="count-tag"><?= number_format(count($shipments), 0, '.', '٬') ?></span></h2></div>
    <div class="table-tools"><label class="search-box"><span>⌕</span><input type="search" placeholder="ابحث برقم التتبع أو المدينة" data-shipment-search data-testid="input-shipment-search"></label>
      <select class="filter-select" aria-label="تصفية حالة الإشارة" data-shipment-filter data-testid="select-shipment-filter"><option value="all">كل الحالات</option><option value="connected">متصل</option><option value="known_gap">فجوة متوقعة</option><option value="abnormal_gap">انقطاع مقلق</option></select>
    </div>
  </div>
  <?php if ($shipments): ?>
    <div class="table-scroll"><table class="data-table" data-testid="table-shipments">
      <thead><tr><th>رقم التتبع</th><th>المسار</th><th>نوع النقل</th><th>حالة الإشارة</th><th>آخر تحديث</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($shipments as $shipment): $health = shipment_health($shipment, $zones); $points = classify_points($shipment, $zones); $last = end($points); ?>
        <tr data-shipment-row data-health="<?= e($health) ?>" data-search="<?= e(mb_strtolower($shipment['tracking_code'] . ' ' . $shipment['origin'] . ' ' . $shipment['destination'])) ?>">
          <td><a class="tracking-code mono" href="<?= e(page_url('shipment', ['id' => $shipment['id']])) ?>"><?= e($shipment['tracking_code']) ?></a></td>
          <td><span class="route-cell"><?= e($shipment['origin']) ?> <span>←</span> <?= e($shipment['destination']) ?></span></td>
          <td><span class="type-tag"><?= $shipment['type'] === 'sea' ? 'بحري' : 'بري' ?></span></td>
          <td><span class="status-pill <?= e(classification_class($health)) ?>"><?= e(classification_label($health)) ?></span></td>
          <td class="muted-cell"><?= $last ? e(format_time($last['time'], 'j/n/Y · H:i')) : '—' ?></td>
          <td><a class="row-open" href="<?= e(page_url('shipment', ['id' => $shipment['id']])) ?>">التفاصيل <span>←</span></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="empty-state filter-empty" data-filter-empty hidden><b>لا توجد نتائج مطابقة</b><p>جرّب تغيير عبارة البحث أو حالة الإشارة.</p></div>
  <?php else: ?>
    <div class="empty-state"><b>لا توجد شحنات مسجلة</b><p>أضف أول شحنة لتبدأ تجربة التتبع.</p></div>
  <?php endif; ?>
</section>

<section class="panel create-panel" id="new-shipment">
  <div class="panel-heading"><div><p class="eyebrow">محاكاة تعليمية</p><h2>إنشاء شحنة تجريبية</h2></div><span class="type-tag">3–10 نقاط تتبع</span></div>
  <p class="panel-description">يُنشئ النظام نقاطًا توضيحية بين المدينتين. هذه المحاكاة لا تتصل بجهاز GPS فعلي.</p>
  <form method="post" action="<?= e(page_url('shipments')) ?>" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action" value="create_shipment">
    <label class="form-field"><span>مدينة الانطلاق</span><select name="origin" required data-testid="select-origin"><option value="">اختر المدينة</option><?php foreach (array_keys($cities) as $city): ?><option value="<?= e($city) ?>"><?= e($city) ?></option><?php endforeach; ?></select></label>
    <label class="form-field"><span>مدينة الوجهة</span><select name="destination" required data-testid="select-destination"><option value="">اختر المدينة</option><?php foreach (array_keys($cities) as $city): ?><option value="<?= e($city) ?>"><?= e($city) ?></option><?php endforeach; ?></select></label>
    <label class="form-field"><span>نوع النقل</span><select name="transport_type" required><option value="land">بري</option><option value="sea">بحري — محاكاة</option></select></label>
    <label class="form-field"><span>عدد نقاط التتبع</span><input type="number" name="points_count" min="3" max="10" value="5" required></label>
    <div class="form-actions"><button class="button button-primary" type="submit" data-testid="button-create-shipment">إنشاء الشحنة وبدء التتبع</button></div>
  </form>
</section>
