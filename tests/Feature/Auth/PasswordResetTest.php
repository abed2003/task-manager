<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_is_disabled(): void
    {
        $this->get('/forgot-password')
            ->assertRedirect('/login');
    }

    public function test_reset_password_email_is_not_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/login');

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_is_disabled(): void
    {
        $this->get('/reset-password/fake-token')
            ->assertRedirect('/login');
    }
}
