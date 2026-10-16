<?php

namespace Database\Seeders;

use App\Models\ArchiveEntry;
use App\Models\Branch;
use App\Models\Faculty;
use App\Models\Technology;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo archive of past graduation projects (fake but realistic data).
 */
class ArchiveDemoSeeder extends Seeder
{
    private const STUDENT_FIRST_NAMES = [
        'سارة', 'عمر', 'ليان', 'يزن', 'نور', 'كريم', 'رهف', 'آدم', 'جنى', 'باسل',
        'دانة', 'سليم', 'تالا', 'مالك', 'هديل', 'زيد', 'لمى', 'فارس', 'رنا', 'قيس',
        'ملك', 'أنس', 'شهد', 'حمزة',
    ];

    private const STUDENT_LAST_NAMES = [
        'حمدان', 'النجار', 'عودة', 'الخطيب', 'قاسم', 'زيدان', 'دويكات', 'شتية',
        'الجعبري', 'طوقان', 'المصري', 'عرفات', 'أبو بكر', 'صلاح', 'جرادات',
    ];

    private const SUPERVISOR_NAMES = [
        'د. حمزة مجاهد', 'د. منى عودة', 'د. خالد بركات',
        'د. ريم الشريف', 'د. يوسف عمرو', 'د. سمير حداد',
    ];

    private const COMMITTEE_NAMES = [
        'د. لينا حمدان', 'د. ماهر قاسم', 'د. هبة زيدان',
        'د. نضال الأحمد', 'د. عبير ناصر', 'د. وائل منصور',
    ];

    private const HALLS = [
        'hebron' => ['قاعة 101', 'قاعة 102', 'مختبر الحاسوب 1'],
        'ramallah' => ['قاعة 201', 'مختبر الحاسوب 2'],
        'nablus' => ['قاعة 301'],
    ];

    private const CLOSING_SENTENCES = [
        'tech' => 'يتميز المشروع بواجهة عربية سهلة الاستخدام ويمكن تطويره ليخدم شريحة أوسع من المستخدمين.',
        'admin' => 'خرجت الدراسة بتوصيات عملية لأصحاب القرار والمستثمرين.',
        'education' => 'قدمت الدراسة توصيات تساعد المعلمين على تحسين أساليب التدريس.',
        'social' => 'قدم المشروع توصيات قابلة للتطبيق للمؤسسات العاملة في المجال.',
    ];

    public function run(): void
    {
        $faculties = [
            'tech' => Faculty::where('name_en', 'Faculty of Technology and Applied Sciences')->first(),
            'admin' => Faculty::where('name_en', 'Faculty of Administrative and Economic Sciences')->first(),
            'education' => Faculty::where('name_en', 'Faculty of Educational Sciences')->first(),
            'social' => Faculty::where('name_en', 'Faculty of Social and Family Development')->first(),
        ];

        $branches = [
            'hebron' => Branch::where('name_en', 'Hebron')->first(),
            'ramallah' => Branch::where('name_en', 'Ramallah & Al-Bireh')->first(),
            'nablus' => Branch::where('name_en', 'Nablus')->first(),
        ];
        $branchKeys = array_keys($branches);

        // Archive entries are added by a supervisor
        $creator = User::where('email', 'hamza.mujahed@qou.edu')->first();

        foreach ($this->projects() as $i => $data) {
            [$facultyKey, $title, $goal, $year, $grade, $techs, $competition, $result] = $data;

            $faculty = $faculties[$facultyKey];
            $branchKey = $branchKeys[$i % 3];
            $halls = self::HALLS[$branchKey];
            // Spread demo projects over the first two departments of each faculty
            $departments = $faculty->departments()->orderBy('id')->take(2)->get();
            $department = $departments[$i % $departments->count()];

            $entry = ArchiveEntry::create([
                'faculty_id' => $faculty->id,
                'department_id' => $department->id,
                'branch_id' => $branches[$branchKey]->id,
                'created_by' => $creator->id,
                'title' => $title,
                // The idea in one line; the description then says how and why
                'idea_summary' => $goal,
                'description' => $this->buildDescription($techs, $facultyKey),
                'year' => $year,
                'supervisor_name' => self::SUPERVISOR_NAMES[$i % count(self::SUPERVISOR_NAMES)],
                'defense_date' => Carbon::create($year, 6, 10 + ($i % 15)),
                'hall_name' => $halls[$i % count($halls)],
                'grade' => $grade,
                'final_score' => $this->scoreFor($grade, $i),
                'competition' => $competition !== null,
                'competition_name' => $competition,
                'competition_result' => $result,
                'is_featured' => $competition !== null && $grade === 'excellent',
            ]);

            $studentCount = 2 + ($i % 2);
            for ($j = 0; $j < $studentCount; $j++) {
                $entry->students()->create(['student_name' => $this->studentName($i, $j)]);
            }

            for ($j = 0; $j < 3; $j++) {
                $name = self::COMMITTEE_NAMES[($i + $j) % count(self::COMMITTEE_NAMES)];
                $entry->committee()->create(['member_name' => $name]);
            }

            foreach ($techs as $techName) {
                $entry->technologies()->attach(Technology::firstOrCreate(['name' => $techName])->id);
            }

            // Student names and technologies are searchable too, so fold them in
            $entry->refreshSearchText();
        }
    }

    private function buildDescription(array $techs, string $facultyKey): string
    {
        $methods = $techs
            ? 'اعتمد الفريق في تنفيذه على ' . implode('، ', $techs) . '.'
            : 'اعتمد الفريق على المنهج الوصفي والزيارات الميدانية.';

        return $methods . ' ' . self::CLOSING_SENTENCES[$facultyKey];
    }

    private function scoreFor(string $grade, int $i): float
    {
        return match ($grade) {
            'excellent' => 86 + ($i % 12),
            'very_good' => 80 + ($i % 5),
            default => 70 + ($i % 10),
        };
    }

    private function studentName(int $i, int $j): string
    {
        $first = self::STUDENT_FIRST_NAMES[($i * 2 + $j) % count(self::STUDENT_FIRST_NAMES)];
        $last = self::STUDENT_LAST_NAMES[($i + $j * 5) % count(self::STUDENT_LAST_NAMES)];

        return $first . ' ' . $last;
    }

    // [faculty, title, goal sentence, year, grade, technologies, competition, result]
    private function projects(): array
    {
        return [
            ['tech', 'نظام إدارة العيادات الطبية الإلكتروني', 'يتيح النظام حجز المواعيد إلكترونياً وإدارة ملفات المرضى في العيادات الصغيرة.', 2022, 'excellent', ['Laravel', 'MySQL', 'JavaScript'], null, null],
            ['tech', 'تطبيق تعليم البرمجة للأطفال', 'تطبيق تفاعلي يعلّم الأطفال أساسيات البرمجة من خلال ألعاب وتحديات قصيرة.', 2022, 'very_good', ['Flutter', 'Firebase'], 'مسابقة الريادة التقنية', 'المركز الثاني'],
            ['tech', 'منصة التبرع بالدم', 'تربط المنصة المتبرعين ببنوك الدم وترسل تنبيهات عند الحاجة لفصيلة معينة.', 2022, 'good', ['Laravel', 'MySQL', 'Firebase'], null, null],
            ['tech', 'نظام حضور الطلاب بالتعرف على الوجه', 'يسجل النظام حضور الطلاب تلقائياً عبر كاميرا القاعة باستخدام تقنيات التعرف على الوجه.', 2023, 'excellent', ['Python', 'TensorFlow'], 'هاكاثون فلسطين', 'المركز الأول'],
            ['tech', 'متجر إلكتروني للمنتجات الفلسطينية المحلية', 'يساعد المتجر التعاونيات النسوية على عرض منتجاتها وبيعها عبر الإنترنت.', 2023, 'very_good', ['Laravel', 'MySQL', 'JavaScript'], null, null],
            ['tech', 'نظام مراقبة جودة المياه بإنترنت الأشياء', 'يقيس النظام جودة مياه الخزانات بحساسات متصلة ويرسل النتائج إلى تطبيق جوال.', 2023, 'excellent', ['Arduino', 'Python', 'Firebase'], 'معرض الابتكار الجامعي', 'جائزة أفضل فكرة'],
            ['tech', 'تطبيق مواصلات عامة لمدينة الخليل', 'يعرض التطبيق خطوط الحافلات ومواعيدها ويقدّر وقت الوصول إلى كل محطة.', 2023, 'good', ['Android', 'Java', 'Firebase'], null, null],
            ['tech', 'نظام إدارة المكتبات الجامعية', 'ينظم النظام استعارة الكتب ويرسل تذكيرات بمواعيد الإرجاع.', 2022, 'good', ['PHP', 'MySQL'], null, null],
            ['tech', 'منصة الاختبارات الإلكترونية', 'تتيح المنصة للمدرسين إنشاء اختبارات قصيرة وتصحيحها تلقائياً.', 2024, 'very_good', ['Laravel', 'MySQL', 'JavaScript'], null, null],
            ['tech', 'تطبيق إرشاد سياحي للبلدة القديمة في القدس', 'يعرض التطبيق المعالم التاريخية مع شرح صوتي وخرائط تفاعلية.', 2024, 'excellent', ['Flutter', 'Firebase'], 'مسابقة التحول الرقمي', 'المركز الثالث'],
            ['tech', 'نظام كشف الأخبار المضللة باللغة العربية', 'يحلل النظام النصوص الإخبارية العربية ويقدّر احتمال أن تكون مضللة.', 2024, 'excellent', ['Python', 'TensorFlow'], null, null],
            ['tech', 'نظام حجز الملاعب الرياضية', 'يتيح النظام حجز الملاعب ودفع الرسوم وإدارة جداول المباريات.', 2024, 'good', ['Laravel', 'MySQL'], null, null],
            ['tech', 'تطبيق متابعة مرضى السكري', 'يسجل التطبيق قراءات السكر ويذكّر بمواعيد الأدوية ويعرض رسوماً بيانية للتطور.', 2024, 'very_good', ['Flutter', 'Firebase'], null, null],
            ['tech', 'نظام إدارة المستودعات للشركات الصغيرة', 'يتتبع النظام المخزون ويحذّر عند انخفاض كميات الأصناف.', 2024, 'good', ['Java', 'MySQL'], null, null],
            ['tech', 'منصة التطوع الشبابية', 'تربط المنصة الشباب بالمبادرات التطوعية في مدنهم.', 2025, 'very_good', ['Node.js', 'MySQL', 'JavaScript'], null, null],
            ['tech', 'نظام ري ذكي للحدائق المنزلية', 'يتحكم النظام بالري تلقائياً حسب رطوبة التربة ودرجة الحرارة.', 2025, 'excellent', ['Arduino', 'Android'], 'مسابقة الروبوت الفلسطينية', 'المركز الأول'],
            ['tech', 'مساعد ذكي للإجابة على أسئلة الطلاب الجامعيين', 'يجيب المساعد عن الأسئلة المتكررة حول التسجيل والمواد الدراسية.', 2025, 'excellent', ['Python', 'TensorFlow', 'Laravel'], null, null],
            ['tech', 'نظام إدارة الشكاوى البلدية', 'يمكّن النظام المواطنين من تقديم الشكاوى ومتابعة حالتها إلكترونياً.', 2025, 'good', ['Laravel', 'MySQL'], null, null],
            ['tech', 'تطبيق تعلم لغة الإشارة', 'يعلّم التطبيق لغة الإشارة العربية من خلال فيديوهات قصيرة وتمارين تفاعلية.', 2025, 'very_good', ['Flutter', 'TensorFlow'], 'جائزة الابتكار الاجتماعي', 'تنويه خاص'],
            ['tech', 'منصة توظيف للخريجين الجدد', 'تعرض المنصة فرص التدريب والعمل وتطابقها مع مهارات الخريج.', 2025, 'very_good', ['Laravel', 'MySQL', 'JavaScript'], null, null],
            ['tech', 'نظام كشف أمراض أوراق الزيتون بالصور', 'يصنف النظام صور أوراق الزيتون ويحدد الأمراض الشائعة فيها.', 2026, 'excellent', ['Python', 'TensorFlow', 'Android'], 'هاكاثون الزراعة الذكية', 'المركز الأول'],
            ['tech', 'نظام إدارة حضانات الأطفال', 'ينظم النظام تسجيل الأطفال والتواصل اليومي مع الأهالي.', 2026, 'good', ['PHP', 'MySQL', 'JavaScript'], null, null],
            ['tech', 'تطبيق تتبع الحافلات المدرسية', 'يعرض التطبيق موقع الحافلة المدرسية مباشرة للأهالي.', 2026, 'very_good', ['Flutter', 'Firebase', 'Node.js'], null, null],
            ['tech', 'نظام أرشفة إلكترونية للوثائق الحكومية', 'يحول النظام الوثائق الورقية إلى أرشيف رقمي قابل للبحث.', 2026, 'excellent', ['Laravel', 'MySQL'], null, null],
            ['tech', 'منصة الدروس الخصوصية عبر الإنترنت', 'تربط المنصة الطلاب بالمعلمين وتنظم الحصص المباشرة.', 2023, 'good', ['Node.js', 'JavaScript', 'MySQL'], null, null],
            ['tech', 'نظام إدارة الصيدليات', 'يتتبع النظام الأدوية وتواريخ انتهائها ويصدر الفواتير.', 2022, 'very_good', ['Java', 'MySQL'], null, null],
            ['admin', 'أثر التسويق الرقمي على المشاريع الصغيرة في الخليل', 'تدرس الدراسة أثر استخدام وسائل التواصل الاجتماعي على مبيعات المشاريع الصغيرة.', 2024, 'very_good', ['SPSS', 'Excel'], null, null],
            ['admin', 'نموذج مالي لتقييم جدوى أنظمة الطاقة الشمسية المنزلية', 'يحسب النموذج تكلفة الاستثمار وفترة الاسترداد لأنظمة الطاقة الشمسية.', 2025, 'excellent', ['Excel'], null, null],
            ['admin', 'نظام محاسبي مبسط للجمعيات التعاونية الزراعية', 'يصمم المشروع دليل حسابات وإجراءات قيد مناسبة للجمعيات الزراعية.', 2023, 'good', ['Excel'], null, null],
            ['admin', 'واقع الريادة النسوية في محافظة الخليل', 'تدرس الدراسة ميدانياً التحديات التي تواجه رائدات الأعمال.', 2026, 'very_good', ['SPSS'], null, null],
            ['education', 'أثر الألعاب التعليمية على تحصيل طلبة الصف الرابع في الرياضيات', 'تقيس الدراسة أثر الألعاب التعليمية على تحصيل الطلبة مقارنة بالطريقة التقليدية.', 2024, 'excellent', ['SPSS'], null, null],
            ['education', 'دليل المعلم لاستخدام التعليم المدمج في المرحلة الأساسية', 'يقدم الدليل أنشطة وخطط دروس تجمع بين التعليم الوجاهي والإلكتروني.', 2025, 'good', [], null, null],
            ['education', 'اتجاهات أولياء الأمور نحو التعليم الإلكتروني', 'تستطلع الدراسة آراء أولياء الأمور حول تجربة التعليم الإلكتروني لأبنائهم.', 2022, 'very_good', ['SPSS', 'Excel'], null, null],
            ['social', 'برنامج إرشادي للحد من التنمر في المدارس', 'يصمم المشروع جلسات إرشادية لطلبة المرحلة الإعدادية للحد من التنمر.', 2023, 'excellent', [], null, null],
            ['social', 'دور المؤسسات الأهلية في دعم كبار السن', 'تدرس الدراسة الخدمات التي تقدمها المؤسسات الأهلية لكبار السن ومدى كفايتها.', 2025, 'good', ['SPSS'], null, null],
            ['social', 'مبادرة مجتمعية لتعزيز الصحة النفسية لدى طلبة الجامعات', 'تنفذ المبادرة ورش توعية وجلسات دعم لطلبة الجامعات.', 2026, 'very_good', [], 'جائزة المبادرات الشبابية', 'المركز الثاني'],
        ];
    }
}
