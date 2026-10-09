<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

/**
 * Real faculties and bachelor programs of Al-Quds Open University.
 * Source: QOU student statistics. The coordinator can edit them later.
 */
class UniversityStructureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faculties() as $data) {
            $departments = $data['departments'];
            unset($data['departments']);

            $faculty = Faculty::updateOrCreate(['name_en' => $data['name_en']], $data);

            foreach ($departments as [$ar, $en]) {
                $faculty->departments()->updateOrCreate(['name_en' => $en], ['name_ar' => $ar]);
            }
        }
    }

    private function faculties(): array
    {
        return [
            [
                'name_ar' => 'كلية التكنولوجيا والعلوم التطبيقية',
                'name_en' => 'Faculty of Technology and Applied Sciences',
                'icon' => 'cpu',
                'color' => '#1E3A8A',
                'is_featured' => true,
                'departments' => [
                    ['أنظمة المعلومات الحاسوبية', 'Computer Information Systems'],
                    ['تكنولوجيا المعلومات والاتصالات', 'Information and Communication Technology'],
                    ['العلوم', 'Sciences'],
                    ['الرياضيات', 'Mathematics'],
                    ['المكتبات وتقنية المعلومات', 'Library and Information Technology'],
                ],
            ],
            [
                'name_ar' => 'كلية الزراعة',
                'name_en' => 'Faculty of Agriculture',
                'icon' => 'sprout',
                'color' => '#15803D',
                'is_featured' => false,
                'departments' => [
                    ['الإنتاج النباتي والوقاية', 'Plant Production and Protection'],
                    ['الإنتاج الحيواني', 'Animal Production'],
                    ['التصنيع الغذائي', 'Food Processing'],
                    ['التغذية والتصنيع الغذائي', 'Nutrition and Food Processing'],
                ],
            ],
            [
                'name_ar' => 'كلية التنمية الاجتماعية والأسرية',
                'name_en' => 'Faculty of Social and Family Development',
                'icon' => 'users',
                'color' => '#7C3AED',
                'is_featured' => false,
                'departments' => [
                    ['التنمية الاجتماعية', 'Social Development'],
                    ['الخدمة الاجتماعية', 'Social Work'],
                    ['تنمية المجتمع المحلي', 'Community Development'],
                    ['التربية الخاصة', 'Special Education'],
                    ['رعاية الطفل', 'Child Care'],
                    ['النوع الاجتماعي وقضايا التنمية', 'Gender and Development Issues'],
                ],
            ],
            [
                'name_ar' => 'كلية العلوم الإدارية والاقتصادية',
                'name_en' => 'Faculty of Administrative and Economic Sciences',
                'icon' => 'briefcase',
                'color' => '#0E7490',
                'is_featured' => false,
                'departments' => [
                    ['إدارة الأعمال', 'Business Administration'],
                    ['الاقتصاد', 'Economics'],
                    ['المحاسبة', 'Accounting'],
                    ['العلوم المالية والمصرفية', 'Financial and Banking Sciences'],
                    ['التسويق', 'Marketing'],
                    ['الإدارة الصحية', 'Health Management'],
                    ['الإدارة السياحية التطبيقية', 'Applied Tourism Management'],
                    ['علوم التأمين', 'Insurance Sciences'],
                    ['التسويق الرقمي', 'Digital Marketing'],
                    ['الإدارة والسياسات العامة', 'Management and Public Policy'],
                    ['إدارة الأعمال الرقمية', 'Digital Business Administration'],
                ],
            ],
            [
                'name_ar' => 'كلية العلوم التربوية',
                'name_en' => 'Faculty of Educational Sciences',
                'icon' => 'book',
                'color' => '#B45309',
                'is_featured' => false,
                'departments' => [
                    ['المرحلة الأساسية الأولى', 'Basic Education (First Stage)'],
                    ['تعليم التربية الإسلامية', 'Teaching Islamic Education'],
                    ['تعليم الاجتماعيات', 'Teaching Social Studies'],
                    ['التربية الخاصة', 'Special Education'],
                    ['مصادر التعلم وتكنولوجيا التعليم', 'Learning Resources and Educational Technology'],
                    ['الإرشاد والصحة النفسية', 'Counseling and Mental Health'],
                    ['القضاء والسياسة الشرعية', 'Judiciary and Sharia Policy'],
                    ['اللغة العربية وأساليب تدريسها', 'Arabic Language and Teaching Methods'],
                    ['اللغة الإنجليزية وأساليب تدريسها', 'English Language and Teaching Methods'],
                    ['الرياضيات وأساليب تدريسها', 'Mathematics and Teaching Methods'],
                    ['تعليم العلوم', 'Science Teaching'],
                ],
            ],
            [
                'name_ar' => 'كلية الإعلام',
                'name_en' => 'Faculty of Media',
                'icon' => 'megaphone',
                'color' => '#BE123C',
                'is_featured' => false,
                'departments' => [
                    ['الإعلام الجديد', 'New Media'],
                    ['العلاقات العامة والإعلان', 'Public Relations and Advertising'],
                ],
            ],
            [
                'name_ar' => 'كلية الآداب',
                'name_en' => 'Faculty of Arts',
                'icon' => 'languages',
                'color' => '#4338CA',
                'is_featured' => false,
                'departments' => [
                    ['اللغة العربية وآدابها', 'Arabic Language and Literature'],
                    ['اللغة الإنجليزية وآدابها', 'English Language and Literature'],
                    ['اللغة العبرية وآدابها', 'Hebrew Language and Literature'],
                ],
            ],
        ];
    }
}
