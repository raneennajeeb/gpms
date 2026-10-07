@extends('layouts.public')

@section('title', 'Components')

@section('content')
<style>
    .dev-stack { display: grid; gap: var(--space-3); }
    .dev-row { display: flex; flex-wrap: wrap; gap: var(--space-1); align-items: center; }
    .dev-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-2); }
</style>

<div class="container dev-stack">
    <x-page-header title="مكونات GPMS" subtitle="صفحة للمطورين فقط">
        <x-slot:actions>
            <x-button icon="plus">إضافة مشروع جديد</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card title="الأزرار">
        <div class="dev-row">
            <x-button icon="plus">إضافة مشروع جديد</x-button>
            <x-button variant="secondary" icon="file-text">تصدير Excel</x-button>
            <x-button variant="success" icon="file-text">تصدير PDF</x-button>
            <x-button variant="danger" icon="trash-2">حذف المحدد</x-button>
            <x-button variant="secondary" href="#">رابط بشكل زر</x-button>
            <x-button disabled>زر معطل</x-button>
        </div>
    </x-card>

    <div class="dev-grid">
        <x-stat-card icon="folder" label="المشاريع" value="128" color="info" />
        <x-stat-card icon="users" label="الطلاب" value="40" color="success" />
        <x-stat-card icon="file-text" label="بانتظار المراجعة" value="7" color="warning" />
        <x-stat-card icon="trash-2" label="متأخرة" value="2" color="danger" />
    </div>

    <x-card title="الشارات والوسوم">
        <div class="dev-row">
            <x-badge color="success">معتمد</x-badge>
            <x-badge color="warning">قيد المراجعة</x-badge>
            <x-badge color="danger">متأخر</x-badge>
            <x-badge color="info">قيد التنفيذ</x-badge>
            <x-badge color="neutral">مقفل</x-badge>
        </div>
        <div class="dev-row" style="margin-top: var(--space-2);">
            <x-tag>Laravel</x-tag>
            <x-tag>Node.js</x-tag>
            <x-tag>ذكاء اصطناعي</x-tag>
        </div>
    </x-card>

    <x-card title="التقدم">
        <div class="dev-row" style="gap: var(--space-4);">
            <x-progress-ring :value="65" label="الإنجاز" />
            <div style="flex: 1; min-width: 200px; display: grid; gap: var(--space-2);">
                <x-progress-bar :value="65" />
                <x-progress-bar :value="30" color="warning" />
                <x-progress-bar :value="100" color="success" />
            </div>
        </div>
    </x-card>

    <x-card title="حقل الفورم">
        <x-form-field name="university_id" label="الرقم الجامعي" hint="أرقام فقط" inputmode="numeric" />
    </x-card>

    <x-card>
        <x-empty-state icon="inbox" title="لا توجد مشاريع بعد" text="عندما تقدم فكرة مشروعك ستظهر هنا.">
            <x-button icon="plus">تقديم فكرة</x-button>
        </x-empty-state>
    </x-card>
    <x-card title="التفاعل">
        <div class="dev-row">
            <x-button variant="secondary" data-modal-open="demo-modal">فتح نافذة</x-button>
            <x-button variant="success" onclick="showToast('تم الحفظ بنجاح', 'success')">رسالة نجاح</x-button>
            <x-button variant="danger" onclick="showToast('حدث خطأ، حاول مرة أخرى', 'danger')">رسالة خطأ</x-button>
            <x-button variant="secondary" href="{{ url('/dev/flash') }}">رسالة من السيرفر</x-button>
            <form method="GET" action="{{ url('/dev/components') }}" data-confirm="هل أنت متأكد من حذف هذا المشروع؟">
                <x-button type="submit" variant="danger" icon="trash-2">حذف مع تأكيد</x-button>
            </form>
        </div>
    </x-card>

    <x-modal id="demo-modal" title="نافذة تجريبية">
        <p>هذا محتوى النافذة. اضغطي Esc أو خارج النافذة لإغلاقها.</p>
        <x-slot:footer>
            <x-button variant="secondary" data-modal-close>إغلاق</x-button>
            <x-button>حفظ</x-button>
        </x-slot:footer>
    </x-modal>
</div>
@endsection
