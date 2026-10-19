<section class="about-hero">
  <p class="eyebrow">مشروع تتبع ودعم قرار</p>
  <h1>عندما تصمت الإشارة،<br><span>يستمر التفسير.</span></h1>
  <p>GapTrack طبقة توضيحية فوق تتبع الشحنات: تفرق بين فجوة متوقعة داخل منطقة ضعف تغطية وبين انقطاع طويل يحتاج إلى متابعة.</p>
  <a class="button button-primary" href="<?= e(page_url('dashboard')) ?>">الدخول إلى لوحة المتابعة ←</a>
</section>

<div class="dashboard-grid about-grid">
  <section class="panel about-card"><span class="about-number">01</span><p class="eyebrow">المشكلة</p><h2>آخر موقع لا يشرح سبب الصمت</h2><p>أنظمة التتبع الأساسية قد تعرض آخر نقطة GPS فقط. عند انقطاع الاتصال، يصعب معرفة ما إذا كان السبب تغطية ضعيفة أو تأخرًا غير معتاد.</p></section>
  <section class="panel about-card"><span class="about-number">02</span><p class="eyebrow">الفكرة</p><h2>السياق قبل التنبيه</h2><p>يقارن النظام موقع النقطة بمناطق التغطية المسجلة، ويحسب الفاصل بين التحديثات. الهدف أن يحصل فريق المتابعة على تفسير أولي بدل الصمت.</p></section>
</div>

<section class="panel feature-panel">
  <div class="panel-heading"><div><p class="eyebrow">ما يقدمه النموذج</p><h2>من عرض الموقع إلى فهم الرحلة</h2></div></div>
  <div class="feature-grid">
    <article><span class="feature-symbol">⌖</span><h3>تتبع مسار توضيحي</h3><p>عرض النقاط على مخطط جغرافي وتشغيلها خطوة بخطوة.</p></article>
    <article><span class="feature-symbol">▥</span><h3>تصنيف الفجوات</h3><p>تمييز نقاط داخل مناطق التغطية المعروفة عن الانقطاع الطويل خارجها.</p></article>
    <article><span class="feature-symbol">↗</span><h3>تنبيه استباقي تقريبي</h3><p>إظهار أقرب منطقة ضعف عند اقتراب الشحنة منها.</p></article>
    <article><span class="feature-symbol">▤</span><h3>تحليل جغرافي</h3><p>تجميع نقاط الانقطاع لتسهيل ملاحظة المناطق المتكررة.</p></article>
  </div>
</section>

<section class="panel compare-panel">
  <div class="panel-heading"><div><p class="eyebrow">القيمة المضافة</p><h2>التتبع الأساسي وGapTrack</h2></div></div>
  <div class="table-scroll"><table class="data-table"><thead><tr><th>القدرة</th><th>عرض الموقع فقط</th><th>GapTrack</th></tr></thead><tbody>
    <tr><td>عرض آخر موقع</td><td>متاح</td><td class="compare-yes">متاح</td></tr>
    <tr><td>تفسير الفجوة داخل نطاق معروف</td><td class="compare-no">غير متاح عادةً</td><td class="compare-yes">نعم، ضمن البيانات المسجلة</td></tr>
    <tr><td>تمييز انقطاع طويل خارج النطاق</td><td class="compare-no">بدون تصنيف</td><td class="compare-yes">تنبيه تجريبي</td></tr>
    <tr><td>عرض مناطق تكرار الانقطاع</td><td class="compare-no">غير متاح</td><td class="compare-yes">خريطة تحليلية</td></tr>
  </tbody></table></div>
</section>

<section class="notice notice-predictive"><span class="notice-symbol">i</span><div><b>حدود النسخة الحالية</b><p>الرحلات والمناطق المعروضة تجريبية. لا يوجد اتصال بمصدر GPS فعلي أو قاعدة MySQL الأصلية في هذه النسخة. التنبؤ والمسارات توضيحية ولا تصلح لاتخاذ قرار تشغيلي قبل ربط بيانات موثقة والتحقق من قواعد التصنيف.</p></div></section>
