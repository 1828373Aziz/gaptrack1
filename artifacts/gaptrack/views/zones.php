<?php
$zoneStats = [];
foreach ($data['zones'] as $zone) {
    $zoneStats[(int)$zone['id']] = ['zone' => $zone, 'hits' => 0];
}
foreach ($data['shipments'] as $shipment) {
    foreach ($shipment['points'] as $point) {
        foreach ($data['zones'] as $zone) {
            if (point_in_zone((float)$point['lat'], (float)$point['lng'], $zone)) {
                $zoneStats[(int)$zone['id']]['hits']++;
                break;
            }
        }
    }
}
?>
<section class="page-heading"><div><p class="eyebrow">إشارة وسياق</p><h1>مناطق التغطية</h1><p>عرّف المناطق المعروفة بضعف الاتصال كي لا تُعامل كل فجوة كحالة طارئة.</p></div><a class="button button-primary" href="#add-zone">＋ إضافة منطقة</a></section>

<?php if (isset($_GET['saved'])): ?><div class="notice notice-success">تمت إضافة المنطقة إلى بيانات العرض.</div><?php elseif (isset($_GET['deleted'])): ?><div class="notice notice-success">تم حذف المنطقة من بيانات العرض.</div><?php endif; ?>

<section class="insight-banner zone-explainer"><span class="insight-icon">▥</span><div class="insight-copy"><b>المعرفة تغيّر معنى الانقطاع</b><p>عند دخول الشحنة منطقة مسجلة، يوضح النظام أن فقد الإشارة متوقع ويعرض مدة تقديرية بدل إصدار إنذار طارئ.</p></div><a class="text-link" href="<?= e(page_url('reports')) ?>">عرض التحليل ←</a></section>

<section class="zone-cards">
  <?php if ($zoneStats): foreach ($zoneStats as $stat): $zone = $stat['zone']; ?>
    <article class="panel zone-card" data-testid="zone-card-<?= e((string)$zone['id']) ?>">
      <div class="zone-card-top"><span class="metric-icon green">◎</span><span class="status-pill warning">فجوة متوقعة</span></div>
      <h2><?= e($zone['name']) ?></h2>
      <div class="zone-coordinates"><span><small>خط العرض</small><b><?= number_format((float)$zone['lat_min'], 3) ?> — <?= number_format((float)$zone['lat_max'], 3) ?></b></span><span><small>خط الطول</small><b><?= number_format((float)$zone['lng_min'], 3) ?> — <?= number_format((float)$zone['lng_max'], 3) ?></b></span></div>
      <div class="zone-card-footer"><span>مدة الانقطاع المتوقعة <b><?= number_format((int)$zone['expected_gap_minutes'], 0, '.', '٬') ?> دقيقة</b></span><span><?= number_format((int)$stat['hits'], 0, '.', '٬') ?> نقطة داخل النطاق</span></div>
      <form method="post" action="<?= e(page_url('zones')) ?>" class="delete-form" data-confirm="تأكيد حذف منطقة التغطية؟">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="delete_zone"><input type="hidden" name="zone_id" value="<?= e((string)$zone['id']) ?>">
        <button class="button button-danger-quiet" type="submit" data-testid="button-delete-zone-<?= e((string)$zone['id']) ?>">حذف المنطقة ×</button>
      </form>
    </article>
  <?php endforeach; else: ?><div class="panel empty-state"><b>لا توجد مناطق مسجلة</b><p>أضف أول منطقة لبدء تصنيف فجوات الإشارة.</p></div><?php endif; ?>
</section>

<section class="panel create-panel" id="add-zone">
  <div class="panel-heading"><div><p class="eyebrow">إضافة إلى قاعدة المعرفة</p><h2>إضافة منطقة ضعف تغطية</h2></div></div>
  <p class="panel-description">الإحداثيات تحدد مستطيلًا تقريبيًا. البيانات الحالية للاختبار، وليست سجلًا موثقًا لمناطق الشبكات.</p>
  <form method="post" action="<?= e(page_url('zones')) ?>" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="create_zone">
    <label class="form-field field-wide"><span>اسم المنطقة</span><input type="text" name="name" placeholder="مثال: منطقة ضعف تغطية تجريبية" required minlength="2" maxlength="100" data-testid="input-zone-name"></label>
    <label class="form-field"><span>خط العرض — الحد الأدنى</span><input type="number" step="0.000001" name="lat_min" placeholder="24.000000" min="-90" max="90" required></label>
    <label class="form-field"><span>خط العرض — الحد الأقصى</span><input type="number" step="0.000001" name="lat_max" placeholder="25.000000" min="-90" max="90" required></label>
    <label class="form-field"><span>خط الطول — الحد الأدنى</span><input type="number" step="0.000001" name="lng_min" placeholder="43.000000" min="-180" max="180" required></label>
    <label class="form-field"><span>خط الطول — الحد الأقصى</span><input type="number" step="0.000001" name="lng_max" placeholder="44.000000" min="-180" max="180" required></label>
    <label class="form-field"><span>مدة الانقطاع المتوقعة (دقيقة)</span><input type="number" name="expected_gap_minutes" value="30" min="1" max="1440" required></label>
    <div class="form-actions"><button class="button button-primary" type="submit" data-testid="button-create-zone">إضافة المنطقة</button></div>
  </form>
</section>
