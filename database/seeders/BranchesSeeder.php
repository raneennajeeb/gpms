<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * The 18 branches of Al-Quds Open University.
 * The code is the official branch code and is also the prefix of a student's university ID.
 */
class BranchesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->branches() as $branch) {
            Branch::updateOrCreate(['code' => $branch['code']], $branch);
        }
    }

    private function branches(): array
    {
        return [
            ['code' => '01110', 'name_ar' => 'القدس', 'name_en' => 'Jerusalem'],
            ['code' => '01300', 'name_ar' => 'رام الله والبيرة', 'name_en' => 'Ramallah & Al-Bireh'],
            ['code' => '01310', 'name_ar' => 'أريحا', 'name_en' => 'Jericho'],
            ['code' => '01400', 'name_ar' => 'بيت لحم', 'name_en' => 'Bethlehem'],
            ['code' => '01500', 'name_ar' => 'الخليل', 'name_en' => 'Hebron'],
            ['code' => '01510', 'name_ar' => 'دورا', 'name_en' => 'Dura'],
            ['code' => '01520', 'name_ar' => 'يطا', 'name_en' => 'Yatta'],
            ['code' => '01200', 'name_ar' => 'نابلس', 'name_en' => 'Nablus'],
            ['code' => '01220', 'name_ar' => 'قلقيلية', 'name_en' => 'Qalqilya'],
            ['code' => '01230', 'name_ar' => 'سلفيت', 'name_en' => 'Salfit'],
            ['code' => '01240', 'name_ar' => 'طوباس', 'name_en' => 'Tubas'],
            ['code' => '01700', 'name_ar' => 'جنين', 'name_en' => 'Jenin'],
            ['code' => '01800', 'name_ar' => 'طولكرم', 'name_en' => 'Tulkarm'],
            ['code' => '01600', 'name_ar' => 'غزة', 'name_en' => 'Gaza'],
            ['code' => '01950', 'name_ar' => 'شمال غزة', 'name_en' => 'North Gaza'],
            ['code' => '01610', 'name_ar' => 'الوسطى', 'name_en' => 'Middle Area'],
            ['code' => '01900', 'name_ar' => 'خان يونس', 'name_en' => 'Khan Younis'],
            ['code' => '01910', 'name_ar' => 'رفح', 'name_en' => 'Rafah'],
        ];
    }
}
