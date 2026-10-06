<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Project;
use App\Models\Section;
use App\Models\Semester;
use App\Models\StageTemplate;
use App\Models\Team;
use App\Models\Technology;
use App\Models\User;
use App\Support\Grade;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fake demo data for development and presentation only.
 * Every user created here has is_demo = true.
 * All demo accounts share the same password: password
 */
class DemoSeeder extends Seeder
{
    private string $password;
    private Semester $semester;
    private array $branches = [];
    private array $faculties = [];
    private Department $itDepartment;
    private User $coordinator;
    private array $supervisors = [];
    private array $sections = [];
    private array $technologies = [];
    private User $ahmad;
    private User $mohammad;

    public function run(): void
    {
        // Hash once: bcrypt is slow on purpose, and we create 50 users
        $this->password = Hash::make('password');

        $this->createUniversityStructure();
        $this->createStaff();
        $this->createSectionsAndStudents();
        $this->createTechnologies();
        $this->createDemoProject();
    }

    private function createUniversityStructure(): void
    {
        $this->semester = Semester::create([
            'name' => 'Spring 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $branches = [
            'hebron' => ['الخليل', 'Hebron'],
            'ramallah' => ['رام الله والبيرة', 'Ramallah & Al-Bireh'],
            'nablus' => ['نابلس', 'Nablus'],
        ];

        foreach ($branches as $key => [$ar, $en]) {
            $this->branches[$key] = Branch::create(['name_ar' => $ar, 'name_en' => $en]);
        }

        $faculties = [
            'tech' => [
                'name_ar' => 'كلية التكنولوجيا والعلوم التطبيقية',
                'name_en' => 'Faculty of Technology and Applied Sciences',
                'icon' => 'cpu',
                'color' => '#1E3A8A',
                'is_featured' => true,
                'departments' => [
                    ['تكنولوجيا المعلومات والاتصالات', 'Information and Communication Technology'],
                    ['علم الحاسوب', 'Computer Science'],
                ],
            ],
            'admin' => [
                'name_ar' => 'كلية العلوم الإدارية والاقتصادية',
                'name_en' => 'Faculty of Administrative and Economic Sciences',
                'icon' => 'briefcase',
                'color' => '#047857',
                'is_featured' => false,
                'departments' => [
                    ['إدارة الأعمال', 'Business Administration'],
                    ['المحاسبة', 'Accounting'],
                ],
            ],
            'education' => [
                'name_ar' => 'كلية العلوم التربوية',
                'name_en' => 'Faculty of Educational Sciences',
                'icon' => 'book',
                'color' => '#B45309',
                'is_featured' => false,
                'departments' => [
                    ['التعليم الأساسي', 'Elementary Education'],
                ],
            ],
            'social' => [
                'name_ar' => 'كلية التنمية الاجتماعية والأسرية',
                'name_en' => 'Faculty of Social and Family Development',
                'icon' => 'users',
                'color' => '#7C3AED',
                'is_featured' => false,
                'departments' => [
                    ['الخدمة الاجتماعية', 'Social Work'],
                ],
            ],
        ];

        foreach ($faculties as $key => $data) {
            $departments = $data['departments'];
            unset($data['departments']);

            $faculty = Faculty::create($data);
            $this->faculties[$key] = $faculty;

            foreach ($departments as [$ar, $en]) {
                $faculty->departments()->create(['name_ar' => $ar, 'name_en' => $en]);
            }
        }

        $this->itDepartment = $this->faculties['tech']->departments()->first();
    }

    private function createStaff(): void
    {
        $this->coordinator = $this->makeUser([
            'university_id' => '900001',
            'name' => 'أ. سامر نصار',
            'email' => 'coordinator@qou.edu',
            'role' => 'coordinator',
        ]);

        $supervisors = [
            ['900101', 'د. حمزة مجاهد', 'hamza.mujahed@qou.edu'],
            ['900102', 'د. منى عودة', 'mona.odeh@qou.edu'],
            ['900103', 'د. خالد بركات', 'khaled.barakat@qou.edu'],
            ['900104', 'د. ريم الشريف', 'reem.alsharif@qou.edu'],
            ['900105', 'د. يوسف عمرو', 'yousef.amro@qou.edu'],
        ];

        foreach ($supervisors as [$id, $name, $email]) {
            $this->supervisors[] = $this->makeUser([
                'university_id' => $id,
                'name' => $name,
                'email' => $email,
                'role' => 'supervisor',
            ]);
        }

        $committee = [
            ['900201', 'د. لينا حمدان', 'lina.hamdan@qou.edu'],
            ['900202', 'د. ماهر قاسم', 'maher.qasem@qou.edu'],
            ['900203', 'د. هبة زيدان', 'heba.zaidan@qou.edu'],
            ['900204', 'د. نضال الأحمد', 'nidal.alahmad@qou.edu'],
        ];

        foreach ($committee as [$id, $name, $email]) {
            $this->makeUser([
                'university_id' => $id,
                'name' => $name,
                'email' => $email,
                'role' => 'committee',
            ]);
        }
    }

    private function createSectionsAndStudents(): void
    {
        $sections = [
            '402' => ['hebron', 0],
            '403' => ['ramallah', 1],
            '405' => ['nablus', 2],
        ];

        foreach ($sections as $number => [$branchKey, $supervisorIndex]) {
            $this->sections[$number] = Section::create([
                'number' => (string) $number,
                'department_id' => $this->itDepartment->id,
                'branch_id' => $this->branches[$branchKey]->id,
                'semester_id' => $this->semester->id,
                'supervisor_id' => $this->supervisors[$supervisorIndex]->id,
            ]);
        }

        $firstNames = [
            'سارة', 'عمر', 'ليان', 'يزن', 'نور', 'كريم', 'رهف', 'آدم', 'جنى', 'باسل',
            'دانة', 'سليم', 'تالا', 'مالك', 'هديل', 'زيد', 'لمى', 'فارس', 'رنا', 'قيس',
        ];
        $lastNames = [
            'حمدان', 'النجار', 'عودة', 'الخطيب', 'قاسم', 'زيدان', 'الأحمد', 'بركات',
            'الشريف', 'عمرو', 'دويكات', 'شتية', 'الجعبري', 'طوقان', 'المصري', 'عرفات',
        ];

        // The two students from the design come first, then 38 generated ones
        $names = ['أحمد عيسى', 'محمد خالد'];
        for ($i = 0; count($names) < 40; $i++) {
            $names[] = $firstNames[$i % count($firstNames)] . ' ' . $lastNames[($i * 3) % count($lastNames)];
        }

        foreach ($names as $index => $name) {
            $number = match (true) {
                $index < 14 => '402',
                $index < 27 => '403',
                default => '405',
            };
            $section = $this->sections[$number];
            $universityId = '0120' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            $student = $this->makeUser([
                'university_id' => $universityId,
                'name' => $name,
                'email' => $universityId . '@students.qou.edu',
                'role' => 'student',
                'branch_id' => $section->branch_id,
                'section_id' => $section->id,
            ]);

            if ($index === 0) {
                $this->ahmad = $student;
            } elseif ($index === 1) {
                $this->mohammad = $student;
            }
        }
    }

    private function createTechnologies(): void
    {
        $names = [
            'Laravel', 'PHP', 'MySQL', 'JavaScript', 'Python', 'TensorFlow',
            'Flutter', 'Firebase', 'Java', 'Android', 'Node.js', 'Arduino',
        ];

        foreach ($names as $name) {
            $this->technologies[$name] = Technology::create(['name' => $name]);
        }
    }

    private function createDemoProject(): void
    {
        $hamza = $this->supervisors[0];

        $team = Team::create([
            'section_id' => $this->sections['402']->id,
            'leader_id' => $this->ahmad->id,
            'name' => 'فريق الزراعة الذكية',
            'status' => 'active',
        ]);

        $invitedAt = Carbon::parse('2026-02-20 10:00');
        foreach ([$this->ahmad, $this->mohammad] as $student) {
            $team->members()->create([
                'user_id' => $student->id,
                'status' => 'accepted',
                'invited_at' => $invitedAt,
                'expires_at' => $invitedAt->copy()->addDays(7),
                'responded_at' => $invitedAt->copy()->addDay(),
            ]);
        }

        $approvedAt = Carbon::parse('2026-03-10 11:00');

        $project = Project::create([
            'team_id' => $team->id,
            'supervisor_id' => $hamza->id,
            'semester_id' => $this->semester->id,
            'title' => 'منصة الذكاء الاصطناعي للزراعة الذكية في فلسطين',
            'description' => 'منصة تساعد المزارعين الفلسطينيين على مراقبة محاصيلهم باستخدام حساسات إنترنت الأشياء. '
                . 'تحلل المنصة بيانات التربة والطقس بنماذج الذكاء الاصطناعي وتقدم توصيات للري والتسميد. '
                . 'كما تنبه المزارع مبكراً عند ظهور مؤشرات أمراض النباتات.',
            'category' => 'ذكاء اصطناعي',
            'keywords' => ['زراعة ذكية', 'ذكاء اصطناعي', 'إنترنت الأشياء', 'فلسطين'],
            'status' => 'in_progress',
            'progress_percent' => 65, // fixed demo value agreed for the design, not calculated
            'approved_by' => $hamza->id,
            'approved_at' => $approvedAt,
            'submitted_at' => Carbon::parse('2026-03-03 09:30'),
        ]);

        $project->technologies()->attach([
            $this->technologies['Laravel']->id,
            $this->technologies['Python']->id,
            $this->technologies['TensorFlow']->id,
            $this->technologies['MySQL']->id,
            $this->technologies['Arduino']->id,
        ]);

        // Copy the stage templates: first two done, development in progress, rest locked
        foreach (StageTemplate::orderBy('sort_order')->get() as $template) {
            $status = match (true) {
                $template->sort_order <= 2 => 'completed',
                $template->sort_order === 3 => 'in_progress',
                default => 'locked',
            };
            $dueDate = $approvedAt->copy()->addDays($template->days_after_approval);
            $isDone = $status === 'completed';

            $project->stages()->create([
                'stage_template_id' => $template->id,
                'name_ar' => $template->name_ar,
                'name_en' => $template->name_en,
                'description_ar' => $template->description_ar,
                'sort_order' => $template->sort_order,
                'weight' => $template->weight,
                'status' => $status,
                'due_date' => $dueDate,
                'completed_at' => $isDone ? $dueDate : null,
                'approved_by' => $isDone ? $hamza->id : null,
                'approved_at' => $isDone ? $dueDate : null,
            ]);
        }

        $score = 92;
        $project->supervisorEvaluation()->create([
            'supervisor_id' => $hamza->id,
            'score' => $score,
            'letter' => Grade::letter($score),
            'label_ar' => Grade::label(Grade::key($score), 'ar'),
            'comment' => 'التزام ممتاز بالمواعيد وجودة عالية في التصميم.',
        ]);
    }

    private function makeUser(array $data): User
    {
        return User::create(array_merge([
            'password' => $this->password,
            'department_id' => $this->itDepartment->id,
            'branch_id' => $this->branches['hebron']->id,
            'must_change_password' => false,
            'is_demo' => true,
        ], $data));
    }
}
