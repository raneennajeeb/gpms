<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_student_dashboard(): void
    {
        $response = $this->get('/student/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_student_cannot_open_supervisor_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/supervisor/dashboard');

        $response->assertForbidden();
    }

    public function test_user_who_must_change_password_is_sent_to_change_password_page(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get('/student/dashboard');

        $response->assertRedirect('/password/change');
    }
}
