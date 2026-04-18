<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ], $overrides);
    }

    public function test_email_is_required()
    {
        Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $payload = $this->validPayload([
            'email' => '',
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', $payload);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    public function test_password_is_required()
    {
        Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $payload = $this->validPayload([
            'password' => '',
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', $payload);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    public function test_login_fails_with_invalid_credentials()
    {
        Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $payload = $this->validPayload([
            'email' => 'wrong@example.com',
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', $payload);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertSame('ログイン情報が登録されていません', session('errors')->first('email'));
    }
}
