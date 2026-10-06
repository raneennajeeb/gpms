<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ request('theme') === 'dark' ? 'dark' : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPMS - Design test</title>
    <link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
    <style>
        .swatches { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: var(--space-2); }
        .swatch { border-radius: var(--radius-md); padding: var(--space-2); box-shadow: var(--shadow-sm); font-size: var(--font-size-sm); }
        .box { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-3); margin-block: var(--space-3); }
    </style>
</head>
<body>
    <main class="container" style="padding-block: var(--space-4);">
        <h1>نظام إدارة مشاريع التخرج</h1>
        <p class="text-muted">GPMS - Graduation Project Management System</p>

        <p>
            <a href="?theme=light">الوضع الفاتح</a> |
            <a href="?theme=dark">الوضع الليلي</a>
        </p>

        <div class="box">
            <h3>بطاقة تجريبية</h3>
            <p>هذا نص عادي بخط Cairo لتجربة القراءة باللغة العربية من اليمين إلى اليسار.</p>
            <p style="font-weight: 300;">سماكة 300</p>
            <p style="font-weight: 600;">سماكة 600</p>
            <p style="font-weight: 900;">سماكة 900</p>
        </div>

        <div class="swatches">
            <div class="swatch" style="background: var(--color-sidebar-bg); color: #fff;">--color-sidebar-bg</div>
            <div class="swatch" style="background: var(--color-table-head-bg); color: #fff;">--color-table-head-bg</div>
            <div class="swatch" style="background: var(--color-primary); color: #fff;">--color-primary</div>
            <div class="swatch" style="background: var(--status-success-bg); color: var(--status-success);">success</div>
            <div class="swatch" style="background: var(--status-warning-bg); color: var(--status-warning);">warning</div>
            <div class="swatch" style="background: var(--status-danger-bg); color: var(--status-danger);">danger</div>
            <div class="swatch" style="background: var(--status-info-bg); color: var(--status-info);">info</div>
            <div class="swatch" style="background: var(--status-neutral-bg); color: var(--status-neutral);">neutral</div>
        </div>
    </main>
</body>
</html>
