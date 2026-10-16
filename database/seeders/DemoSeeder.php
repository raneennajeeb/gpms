<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\DefenseSession;
use App\Models\Idea;
use App\Models\Department;
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
use Illuminate\Support\Str;

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
    private Department $itDepartment;
    private User $coordinator;
    private array $supervisors = [];
    private array $sections = [];
    private array $technologies = [];
    private User $ahmad;
    private User $mohammad;
    private Project $project;
    private array $rooms = [];

    public function run(): void
    {
        // Hash once: bcrypt is slow on purpose, and we create 50 users
        $this->password = Hash::make('password');

        $this->createUniversityStructure();
        $this->createStaff();
        $this->createSectionsAndStudents();
        $this->createTechnologies();
        $this->createIdeasAndPendingInvite();
        $this->createDemoProject();
        $this->createRooms();
        $this->createMeetingsAndDefense();
        $this->createStudentWorkspace();
        $this->createNotifications();
        $this->createActivityLog();

        $this->call(ArchiveDemoSeeder::class);
    }

    private function createUniversityStructure(): void
    {
        $this->semester = Semester::create([
            'name' => 'Spring 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        // Branches come from BranchesSeeder (the real 18); the demo only uses three of them
        $branches = [
            'hebron' => '01500',
            'ramallah' => '01300',
            'nablus' => '01200',
        ];

        foreach ($branches as $key => $code) {
            $this->branches[$key] = Branch::where('code', $code)->firstOrFail();
        }

        // Faculties and departments come from UniversityStructureSeeder (real data)
        $this->itDepartment = Department::where('name_en', 'Information and Communication Technology')->firstOrFail();
    }

    private function createStaff(): void
    {
        // System administrator: the IT department, above any one faculty
        $this->makeUser([
            'university_id' => '900000',
            'name' => 'مسؤول النظام (دائرة تكنولوجيا المعلومات)',
            'email' => 'admin@qou.edu',
            'role' => 'admin',
        ]);

        $this->coordinator = $this->makeUser([
            'university_id' => '900001',
            'name' => 'أ. سامر نصار',
            'email' => 'coordinator@qou.edu',
            'role' => 'coordinator',
            // The coordinator manages this faculty across all its branches
            'faculty_id' => $this->itDepartment->faculty_id,
        ]);

        $supervisors = [
            ['900101', 'د. حمزة مجاهد', 'hamza.mujahed@qou.edu'],
            ['900102', 'د. منى عودة', 'mona.odeh@qou.edu'],
            ['900103', 'د. خالد بركات', 'khaled.barakat@qou.edu'],
            ['900104', 'د. ريم الشريف', 'reem.alsharif@qou.edu'],
            ['900105', 'د. يوسف عمرو', 'yousef.amro@qou.edu'],
        ];

        $officeHours = [
            'الأحد والثلاثاء 10:00 - 12:00',
            'الاثنين والأربعاء 09:00 - 11:00',
            'الثلاثاء 12:00 - 14:00',
            null, // not every doctor publishes them
            'السبت 09:00 - 11:00',
        ];

        foreach ($supervisors as $index => [$id, $name, $email]) {
            $this->supervisors[] = $this->makeUser([
                'university_id' => $id,
                'name' => $name,
                'email' => $email,
                'role' => 'supervisor',
                'phone' => '0599' . str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                'office_hours' => $officeHours[$index] ?? null,
            ]);
        }

        // Sitting on a committee is not a role: these are supervisors too,
        // assigned to a defense session when the coordinator schedules it
        $moreSupervisors = [
            ['900201', 'د. لينا حمدان', 'lina.hamdan@qou.edu'],
            ['900202', 'د. ماهر قاسم', 'maher.qasem@qou.edu'],
            ['900203', 'د. هبة زيدان', 'heba.zaidan@qou.edu'],
            ['900204', 'د. نضال الأحمد', 'nidal.alahmad@qou.edu'],
        ];

        foreach ($moreSupervisors as [$id, $name, $email]) {
            $this->supervisors[] = $this->makeUser([
                'university_id' => $id,
                'name' => $name,
                'email' => $email,
                'role' => 'supervisor',
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
        $names = ['رنين محمد', 'محمد خالد'];
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

            // A university ID starts with the code of the student's own branch
            $universityId = $section->branch->code . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

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

    /** An idea bank to browse, and one invitation still waiting for an answer */
    private function createIdeasAndPendingInvite(): void
    {
        $hamza = $this->supervisors[0];

        $ideas = [
            ['نظام حجز مختبرات الحاسوب', 'نظام يتيح للطلبة حجز أجهزة المختبر في أوقات محددة، مع لوحة للمشرف لمتابعة الحجوزات ومنع التعارض.', ['Laravel', 'MySQL', 'JavaScript']],
            ['تطبيق إرشاد أكاديمي ذكي', 'تطبيق موبايل يقترح على الطالب المساقات المناسبة بناءً على خطته الدراسية وعلاماته السابقة.', ['Flutter', 'Firebase']],
            ['منصة متابعة التدريب الميداني', 'منصة تربط الطالب بجهة التدريب والمشرف الأكاديمي، وتوثق ساعات التدريب والتقارير الأسبوعية.', ['Laravel', 'Bootstrap']],
        ];

        foreach ($ideas as [$title, $description, $technologies]) {
            Idea::create([
                'supervisor_id' => $hamza->id,
                'title' => $title,
                'description' => $description,
                'technologies' => $technologies,
                'status' => 'available',
            ]);
        }

        // A second team in the same section, with an invitation still open,
        // so the invitation flow has something to show on day one
        $leader = User::studentsOfSection($this->sections['402']->id)
            ->whereKeyNot([$this->ahmad->id, $this->mohammad->id])
            ->orderBy('id')
            ->first();

        $invitee = User::studentsOfSection($this->sections['402']->id)
            ->whereKeyNot([$this->ahmad->id, $this->mohammad->id, $leader->id])
            ->orderBy('id')
            ->first();

        $team = Team::create([
            'section_id' => $this->sections['402']->id,
            'leader_id' => $leader->id,
            'name' => 'فريق تحليل البيانات',
            'status' => 'forming',
        ]);

        $team->members()->create([
            'user_id' => $leader->id,
            'status' => 'accepted',
            'invited_at' => now()->subDays(2),
            'expires_at' => now()->addDays(5),
            'responded_at' => now()->subDays(2),
        ]);

        $team->members()->create([
            'user_id' => $invitee->id,
            'status' => 'pending',
            'invited_at' => now()->subDays(2),
            'expires_at' => now()->addDays(5),
        ]);

        $this->notify($invitee, 'team_invited', ['team' => $team->name]);
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

        $project = $this->project = Project::create([
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

    private function createRooms(): void
    {
        $rooms = [
            ['قاعة 101', 'المبنى الرئيسي', 40, 'hebron'],
            ['قاعة 102', 'المبنى الرئيسي', 30, 'hebron'],
            ['مختبر الحاسوب 1', 'مبنى التكنولوجيا', 25, 'hebron'],
            ['قاعة 201', 'المبنى الرئيسي', 35, 'ramallah'],
            ['مختبر الحاسوب 2', 'مبنى التكنولوجيا', 25, 'ramallah'],
            ['قاعة 301', 'المبنى الرئيسي', 30, 'nablus'],
        ];

        foreach ($rooms as [$name, $building, $capacity, $branchKey]) {
            $this->rooms[] = $this->branches[$branchKey]->rooms()->create([
                'name' => $name,
                'building' => $building,
                'capacity' => $capacity,
            ]);
        }
    }

    private function createMeetingsAndDefense(): void
    {
        $hamza = $this->supervisors[0];

        // Upcoming items use dates relative to today, so dashboards always have something upcoming
        $meetings = [
            ['اجتماع متابعة التصميم', Carbon::parse('2026-03-17 12:00'), 'done'],
            ['مراجعة نموذج الذكاء الاصطناعي', Carbon::parse('2026-04-14 12:00'), 'done'],
            ['متابعة مرحلة التطوير', now()->addDays(3)->setTime(12, 0), 'confirmed'],
        ];

        foreach ($meetings as [$title, $startsAt, $status]) {
            $this->project->meetings()->create([
                'supervisor_id' => $hamza->id,
                'title' => $title,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHour(),
                'room_id' => $this->rooms[2]->id,
                'status' => $status,
            ]);
        }

        $section = $this->sections['402'];

        // The Hebron committee pool: supervisors other than the section's own,
        // because a supervisor never sits on their own students' committee
        $pool = User::where('role', 'supervisor')
            ->whereKeyNot($section->supervisor_id)
            ->orderBy('id')
            ->take(4)
            ->get();

        $this->branches['hebron']->committeePool()->attach($pool->pluck('id'));

        // A defense session for the section in two weeks, Saturday to Wednesday only
        $date = now()->addDays(14);

        while ($date->isThursday() || $date->isFriday()) {
            $date->addDay();
        }

        $session = DefenseSession::create([
            'section_id' => $section->id,
            'supervisor_id' => $section->supervisor_id,
            'room_id' => $this->rooms[0]->id,
            'session_date' => $date->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
            'created_by' => $section->supervisor_id,
        ]);

        // Three of the pool sit on this session; the first one chairs it
        foreach ($pool->take(3) as $index => $member) {
            $session->committee()->attach($member->id, ['role' => $index === 0 ? 'chair' : 'member']);
        }

        // The section's projects, one hour each, in order
        $projects = Project::whereHas('team', fn ($query) => $query->where('section_id', $section->id))
            ->take(2)
            ->get();

        foreach ($projects as $index => $project) {
            $session->sessionProjects()->create([
                'project_id' => $project->id,
                'position' => $index + 1,
                'start_time' => sprintf('%02d:00', 9 + $index),
                'end_time' => sprintf('%02d:00', 10 + $index),
            ]);
        }
    }

    /** Submissions, tasks, team messages and one answered inquiry */
    private function createStudentWorkspace(): void
    {
        $project = $this->project;
        $hamza = $this->supervisors[0];

        // One submission per stage that has started
        $submissions = [
            ['التصميم', 'وثيقة التصميم التفصيلي', 'report', '-20 days', 'accepted'],
            ['التطوير والبرمجة', 'التقرير المرحلي الأول', 'report', '+6 days', 'pending'],
            ['التطوير والبرمجة', 'الكود المصدري - الإصدار الأولي', 'source_code', '+20 days', 'pending'],
        ];

        foreach ($submissions as [$stageName, $title, $type, $due, $status]) {
            $stage = $project->stages()->where('name_ar', $stageName)->first();

            $project->submissions()->create([
                'project_stage_id' => $stage?->id,
                'title' => $title,
                'type' => $type,
                'due_date' => Carbon::parse($due),
                'status' => $status,
                'supervisor_notes' => $status === 'accepted' ? 'عمل منظم، تابعوا على نفس المستوى.' : null,
            ]);
        }

        $tasks = [
            ['تصميم قاعدة البيانات', 'done', 0, '-15 days'],
            ['واجهة تسجيل الدخول', 'done', 1, '-10 days'],
            ['ربط نموذج الذكاء الاصطناعي', 'doing', 0, '+4 days'],
            ['شاشة تقارير المزارع', 'doing', 1, '+9 days'],
            ['اختبارات الوحدة', 'todo', 0, '+15 days'],
            ['كتابة دليل المستخدم', 'todo', 1, null],
        ];

        $members = $project->team->members()->where('status', 'accepted')->pluck('user_id')->all();

        foreach ($tasks as $index => [$title, $status, $position, $due]) {
            $project->tasks()->create([
                'title' => $title,
                'assigned_to' => $members[$index % count($members)],
                'status' => $status,
                'position' => $position,
                'due_date' => $due ? Carbon::parse($due) : null,
            ]);
        }

        $messages = [
            [$this->ahmad, 'خلصت تصميم قاعدة البيانات، رفعتها على الدرايف.'],
            [$this->mohammad, 'تمام، بشتغل على ربط النموذج اليوم.'],
            [$this->ahmad, 'ما تنسى نجهز التقرير المرحلي قبل الموعد.'],
        ];

        foreach ($messages as [$user, $body]) {
            $project->team->messages()->create(['user_id' => $user->id, 'body' => $body]);
        }

        // A note from the supervisor to the whole team, and a personal reply
        $project->comments()->create([
            'user_id' => $hamza->id,
            'kind' => 'team_note',
            'body' => 'انتبهوا لتوثيق مصادر البيانات في الفصل الثالث، وراجعوا صيغة المراجع.',
        ]);

        $inquiry = $project->inquiries()->create([
            'student_id' => $this->ahmad->id,
            'supervisor_id' => $hamza->id,
            'type' => 'documentation_inquiry',
            'message' => 'دكتور، هل نعتمد صيغة APA في المراجع أم الصيغة المعتمدة في دليل الكلية؟',
            'status' => 'replied',
            'reply' => 'اعتمدوا صيغة APA، وسأرفع لكم نموذجاً جاهزاً.',
            'replied_at' => now()->subDay(),
        ]);

        $this->notify($this->ahmad, 'inquiry_replied', ['supervisor' => $hamza->name]);
    }
    private function createNotifications(): void
    {
        $this->notify($this->ahmad, 'stage_approved', ['stage' => 'التصميم'], read: true);
        $this->notify($this->ahmad, 'meeting_confirmed', ['title' => 'متابعة مرحلة التطوير']);
        $this->notify($this->ahmad, 'defense_scheduled', ['room' => 'قاعة 101']);
        $this->notify($this->supervisors[0], 'inquiry_received', ['student' => 'رنين محمد']);
    }

    private function notify(User $user, string $key, array $params, bool $read = false): void
    {
        // We store a translation key, not the text, so the message shows in the user's language
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'demo',
            'data' => ['key' => 'notifications.' . $key, 'params' => $params],
            'read_at' => $read ? now() : null,
        ]);
    }

    private function createActivityLog(): void
    {
        $entries = [
            ['project_submitted', 'قدّم الفريق فكرة المشروع للمراجعة.', $this->ahmad, '2026-03-03 09:30'],
            ['project_approved', 'اعتمد المشرف فكرة المشروع.', $this->supervisors[0], '2026-03-10 11:00'],
            ['stage_completed', 'اكتملت مرحلة اعتماد الفكرة.', $this->supervisors[0], '2026-03-10 11:05'],
            ['meeting_done', 'عُقد اجتماع متابعة التصميم.', $this->supervisors[0], '2026-03-17 13:00'],
            ['stage_completed', 'اكتملت مرحلة التصميم.', $this->supervisors[0], '2026-03-31 10:00'],
            ['stage_started', 'بدأت مرحلة التطوير والبرمجة.', $this->ahmad, '2026-04-01 09:00'],
        ];

        foreach ($entries as [$action, $description, $user, $date]) {
            ActivityLog::forceCreate([
                'project_id' => $this->project->id,
                'user_id' => $user->id,
                'action' => $action,
                'description' => $description,
                'created_at' => Carbon::parse($date),
            ]);
        }
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
