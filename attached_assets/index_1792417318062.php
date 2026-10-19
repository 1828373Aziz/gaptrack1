<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>GapTrack — عن المشروع</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .hero {
      background: linear-gradient(135deg, #0a0e1a, #1e3a8a 60%, #2563eb);
      color: white; padding: 60px 30px; text-align: center;
      border-bottom: 1px solid rgba(59,130,246,0.25);
    }
    .hero h1 { font-size: 30px; margin-bottom: 10px; text-shadow: 0 0 24px rgba(59,130,246,0.6); }
    .hero p { font-size: 15px; opacity: 0.85; max-width: 650px; margin: 0 auto 24px; line-height: 1.8; }
    .hero .cta {
      display: inline-block; background: #3b82f6; color: white;
      padding: 12px 28px; border-radius: 30px; text-decoration: none;
      font-weight: bold; font-size: 14px;
      box-shadow: 0 0 22px rgba(59,130,246,0.55);
      transition: transform 0.2s;
    }
    .hero .cta:hover { transform: translateY(-2px); }

    .section { max-width: 950px; margin: 0 auto; padding: 40px 20px; }
    .section h2 { font-size: 19px; color: #e2e8f0; margin-bottom: 14px; }

    .problem-box {
      background: rgba(239,68,68,0.08); border-right: 4px solid #ef4444; padding: 16px 18px;
      border-radius: 10px; margin-bottom: 24px; font-size: 14px; line-height: 1.9; color: #fca5a5;
    }

    .feature-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px; margin-top: 16px;
    }
    .feature-card {
      background: #121a2e; border: 1px solid rgba(59,130,246,0.15);
      border-radius: 14px; padding: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.25);
      transition: transform 0.2s;
    }
    .feature-card:hover { transform: translateY(-3px); }
    .feature-card .icon { font-size: 26px; margin-bottom: 8px; }
    .feature-card h3 { font-size: 15px; color: #e2e8f0; margin-bottom: 6px; }
    .feature-card p { font-size: 13px; color: #94a3b8; line-height: 1.7; }

    .tech-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .tech-tag {
      background: rgba(59,130,246,0.12); color: #60a5fa; padding: 6px 14px;
      border-radius: 20px; font-size: 13px; font-weight: 500;
      border: 1px solid rgba(59,130,246,0.2);
    }

    /* جدول المقارنة */
    .compare-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .compare-table th {
      padding: 12px; font-size: 13px; color: #94a3b8; text-align: center;
      border-bottom: 1px solid rgba(59,130,246,0.15);
    }
    .compare-table th:first-child { text-align: right; }
    .compare-table td {
      padding: 12px; font-size: 13.5px; text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.04); color: #e2e8f0;
    }
    .compare-table td:first-child { text-align: right; color: #94a3b8; }
    .compare-table .cross { color: #ef4444; }
    .compare-table .check { color: #4ade80; }
  </style>
</head>
<body>

<nav class="topnav">
  <div class="brand">🚚 GapTrack</div>
  <div class="links">
    <a href="index.php" class="active">عن المشروع</a>
    <a href="overview.php">النظرة العامة</a>
    <a href="dashboard.php">تتبع الشحنة</a>
    <a href="reports.php">التقرير التحليلي</a>
    <a href="add_shipment.php">+ شحنة جديدة</a>
    <a href="zones.php">إدارة المناطق</a>
  </div>
</nav>

<div class="hero">
  <h1>🚚 GapTrack</h1>
  <p>نظام سد الفجوات في تتبع الشحنات — يتوقع مناطق ضعف التغطية مسبقًا، يتعامل بذكاء مع لحظة الانقطاع الفعلي، ويحوّل بيانات الانقطاعات إلى رؤية واضحة لتحسين البنية التحتية للتغطية.</p>
  <a href="overview.php" class="cta">الدخول إلى النظام ←</a>
</div>

<div class="section">
  <h2>🎯 المشكلة</h2>
  <div class="problem-box">
    أنظمة تتبع الشحنات التقليدية تعرض الموقع اللحظي فقط، وتفشل في التعامل بذكاء مع لحظة انقطاع الإشارة — سواء بسبب مناطق جغرافية ضعيفة التغطية أو أثناء النقل الطويل. النتيجة: صمت تام من النظام وقت الانقطاع، وغياب أي رؤية مجمّعة توضح أكثر المناطق تكرارًا للمشكلة.
  </div>

  <h2>💡 الحل</h2>
  <div class="feature-grid">
    <div class="feature-card">
      <div class="icon">🗺️</div>
      <h3>تتبع لحظي بالخريطة</h3>
      <p>عرض مسار الشحنة الحقيقي مع محاكاة حية لحركتها خطوة بخطوة على خريطة تفاعلية.</p>
    </div>
    <div class="feature-card">
      <div class="icon">🧠</div>
      <h3>كشف ذكي للانقطاع</h3>
      <p>يميّز النظام تلقائيًا بين انقطاع طبيعي (منطقة ضعف تغطية معروفة) وانقطاع غير طبيعي يستدعي المتابعة.</p>
    </div>
    <div class="feature-card">
      <div class="icon">🔔</div>
      <h3>تنبيهات تلقائية</h3>
      <p>بدل الصمت وقت الانقطاع، يرسل النظام تنبيهًا يوضح آخر موقع معروف والوقت المتوقع لعودة الإشارة.</p>
    </div>
    <div class="feature-card">
      <div class="icon">📊</div>
      <h3>تقرير تحليلي</h3>
      <p>خريطة حرارية وجدول يوضحان أكثر المناطق تكرارًا للانقطاع، لدعم قرارات تحسين التغطية.</p>
    </div>
  </div>

  <h2 style="margin-top:30px;">⚖️ GapTrack مقابل الأنظمة التقليدية</h2>
  <div class="card" style="padding:8px 20px;">
    <table class="compare-table">
      <tr>
        <th>الميزة</th>
        <th>نظام تتبع تقليدي</th>
        <th>GapTrack</th>
      </tr>
      <tr>
        <td>عرض الموقع اللحظي</td>
        <td class="check">✔</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>تفسير سبب الانقطاع</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>تمييز انقطاع طبيعي عن غير طبيعي</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>تنبيه استباقي قبل فقدان الإشارة</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
      <tr>
        <td>تقرير تحليلي لمناطق الضعف</td>
        <td class="cross">✘</td>
        <td class="check">✔</td>
      </tr>
    </table>
  </div>

  <h2 style="margin-top:30px;">🛠️ المكدس التقني</h2>
  <div class="tech-list">
    <span class="tech-tag">PHP</span>
    <span class="tech-tag">MySQL</span>
    <span class="tech-tag">HTML / CSS / JavaScript</span>
    <span class="tech-tag">Leaflet.js</span>
    <span class="tech-tag">XAMPP</span>
  </div>
</div>

</body>
</html>