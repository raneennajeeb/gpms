<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'similarity_threshold' => '70',
            'invitation_expiry_days' => '7',
            'reminder_days' => json_encode([7, 3, 1]),
            'max_team_size' => '3',
            'min_team_size' => '1',
            'weight_report' => '20',
            'weight_presentation' => '15',
            'weight_discussion' => '15',
            'weight_project' => '50',
            'max_upload_mb' => '20',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
