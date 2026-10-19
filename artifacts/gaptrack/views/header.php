<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="GapTrack يفسر فجوات إشارة الشحنات، يميز المتوقع من غير الطبيعي، ويعرض أثرها جغرافيًا.">
  <meta name="theme-color" content="#163c3b">
  <title><?= e($pageTitles[$page] ?? 'GapTrack') ?> — GapTrack</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="<?= e(page_url('dashboard')) ?>"><span class="brand-mark">G</span><span><b>GapTrack</b><small>وضوح لكل رحلة</small></span></a>
    <div class="workspace-pill"><span class="online-dot"></span><span>عمليات المملكة</span><span class="pill-arrow">⌄</span></div>
    <p class="nav-caption">مساحة العمل</p>
    <nav class="main-nav" aria-label="التنقل الرئيسي">
      <a class="<?= $page === 'dashboard' ? 'active' : '' ?>" href="<?= e(page_url('dashboard')) ?>"><span class="nav-symbol">◫</span><span>نظرة عامة</span></a>
      <a class="<?= in_array($page, ['shipments', 'shipment'], true) ? 'active' : '' ?>" href="<?= e(page_url('shipments')) ?>"><span class="nav-symbol">◇</span><span>الشحنات</span></a>
      <a class="<?= $page === 'reports' ? 'active' : '' ?>" href="<?= e(page_url('reports')) ?>"><span class="nav-symbol">▤</span><span>تحليل التغطية</span></a>
      <a class="<?= $page === 'zones' ? 'active' : '' ?>" href="<?= e(page_url('zones')) ?>"><span class="nav-symbol">◎</span><span>مناطق التغطية</span></a>
      <a class="<?= $page === 'about' ? 'active' : '' ?>" href="<?= e(page_url('about')) ?>"><span class="nav-symbol">?</span><span>عن GapTrack</span></a>
    </nav>
    <div class="sidebar-bottom">
      <div class="system-status"><span class="online-dot"></span><div><small>حالة النظام</small><b>نسخة عرض تجريبية</b></div><span class="status-check">●</span></div>
      <div class="profile-row"><span class="avatar">ع</span><span><b>فريق العمليات</b><small>مركز التحكم</small></span><span class="pill-arrow">⌄</span></div>
    </div>
  </aside>
  <main class="main-area">
    <header class="topbar">
      <div class="breadcrumb"><span><?= e($pageTitles[$page] ?? 'GapTrack') ?></span><span class="crumb-sep">‹</span><span>GapTrack</span></div>
      <div class="topbar-meta"><span class="demo-label"><i></i> بيانات تجريبية</span><span class="today-label"><?= e(arabic_today()) ?></span><span class="avatar avatar-small">ع</span></div>
      <button class="mobile-menu" type="button" aria-label="فتح القائمة" data-menu-toggle>☰</button>
    </header>
    <div class="page-container">
      <?php if ($errorMessage !== ''): ?>
        <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
      <?php endif; ?>
