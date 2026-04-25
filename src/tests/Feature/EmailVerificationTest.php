<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_is_sent_after_register()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'test_user',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verify_email_guidance_page_has_link_to_mailhog()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('登録していただいたメールアドレスに認証メールを送付しました。');
        $response->assertSee('認証はこちらから');
        $response->assertSee('http://localhost:8025', false);
    }

    public function test_user_is_redirected_to_attendance_page_after_email_verification()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
