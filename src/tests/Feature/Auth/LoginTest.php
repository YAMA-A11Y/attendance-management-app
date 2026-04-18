<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $overrides);
    }

    public function test_email_is_required()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $payload = $this->validPayload([
            'email' => '',
        ]);

        $response = $this->from('/login')->post('/login', $payload);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    public function test_password_is_required()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $payload = $this->validPayload([
            'password' => '',
        ]);

        $response = $this->from('/login')->post('/login', $payload);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $payload = $this->validPayload([
            'email' => 'wrong@example.com',
        ]);

        $response = $this->from('/login')->post('/login', $payload);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertSame('ログイン情報が登録されていません', session('errors')->first('email'));
    }
}
