<?php

namespace Database\Seeders;

use App\Models\StageTemplate;
use Illuminate\Database\Seeder;

class StageTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Weights must add up to 100. The coordinator can edit these later.
        $stages = [
            [
                'name_ar' => 'اعتماد الفكرة',
                'name_en' => 'Idea Approval',
                'description_ar' => 'تقديم فكرة المشروع ومراجعتها واعتمادها من المشرف.',
                'weight' => 10,
                'days_after_approval' => 0,
            ],
            [
                'name_ar' => 'التصميم',
                'name_en' => 'Design',
                'description_ar' => 'تحليل المتطلبات وتصميم قاعدة البيانات والواجهات.',
                'weight' => 15,
                'days_after_approval' => 21,
            ],
            [
                'name_ar' => 'التطوير والبرمجة',
                'name_en' => 'Development',
                'description_ar' => 'بناء النظام وبرمجة الميزات الأساسية واختبارها.',
                'weight' => 35,
                'days_after_approval' => 63,
            ],
            [
                'name_ar' => 'التقرير المرحلي',
                'name_en' => 'Progress Report',
                'description_ar' => 'تسليم تقرير يوضح ما تم إنجازه والخطة المتبقية.',
                'weight' => 10,
                'days_after_approval' => 77,
            ],
            [
                'name_ar' => 'التقرير النهائي والشفرة المصدرية',
                'name_en' => 'Final Report & Source Code',
                'description_ar' => 'تسليم التقرير النهائي والشفرة المصدرية الكاملة للمشروع.',
                'weight' => 15,
                'days_after_approval' => 98,
            ],
            [
                'name_ar' => 'المناقشة',
                'name_en' => 'Defense',
                'description_ar' => 'عرض المشروع ومناقشته أمام لجنة التقييم.',
                'weight' => 15,
                'days_after_approval' => 112,
            ],
        ];

        foreach ($stages as $index => $stage) {
            StageTemplate::updateOrCreate(
                ['sort_order' => $index + 1],
                $stage
            );
        }
    }
}
