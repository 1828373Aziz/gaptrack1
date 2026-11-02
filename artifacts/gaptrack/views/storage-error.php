<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تعذر الاتصال — GapTrack</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
  <main class="app-shell" style="min-height:100vh;display:grid;place-items:center;padding:24px">
    <section class="panel" style="width:min(680px,100%);padding:32px">
      <p class="eyebrow">إعداد قاعدة البيانات</p>
      <h1>تعذر فتح GapTrack</h1>
      <p><?= e($storageErrorMessage) ?></p>
      <p class="panel-description">تأكد من تشغيل MySQL في XAMPP، وأن إعدادات config.local.php تطابق قاعدة gaptrack. لا تضع كلمة المرور في رابط الموقع أو ترسلها في المحادثة.</p>
      <a class="button button-secondary" href="http://localhost/phpmyadmin/">فتح phpMyAdmin</a>
    </section>
  </main>
</body>
</html>
