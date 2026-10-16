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
        $response->assertSessionHasErrors(['university_id' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_unregistered_university_id_shows_the_same_message(): void
    {
        $response = $this->from('/login')->post('/login', [
            'university_id' => '99999999',
            'password' => 'anything',
        ]);

        $response->assertRedirect('/login');
        // Identical to the wrong-password message, so nobody can tell which IDs exist
        $response->assertSessionHasErrors(['university_id' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_a_disabled_account_shows_the_same_message(): void
    {
        User::factory()->create(['university_id' => '12345678', 'is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'university_id' => '12345678',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['university_id' => __('auth.failed')]);
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['university_id' => '12345678']);

        foreach (range(1, 5) as $ignored) {
            $this->from('/login')->post('/login', [
                'university_id' => '12345678',
                'password' => 'wrong-password',
            ]);
        }

        // The sixth attempt is refused before the password is even checked
        $response = $this->from('/login')->post('/login', [
            'university_id' => '12345678',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('university_id');
        $this->assertGuest();
    }
}
