<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_log_in_and_reach_the_student_dashboard(): void
    {
        // The factory gives every user the password "password"
        $student = User::factory()->create(['university_id' => '12345678']);

        $response = $this->post('/login', [
            'university_id' => '12345678',
            'password' => 'password',
        ]);

        $response->assertRedirect('/student/dashboard');
        $this->assertAuthenticatedAs($student);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['university_id' => '12345678']);

        $response = $this->from('/login')->post('/login', [
            'university_id' => '12345678',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_unregistered_university_id_shows_not_registered_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'university_id' => '99999999',
            'password' => 'anything',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('login_alert', __('auth.not_registered'));
        $this->assertGuest();
    }
}
