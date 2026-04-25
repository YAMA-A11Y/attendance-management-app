<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;
    private function adminUser()
    {
        $admin = new Admin();
        $admin->id = 1;
        $admin->name = '管理者';
        $admin->email = 'admin@example.com';

        return $admin;
    }

    public function test_admin_can_view_all_staff_names_and_email_address()
    {
        $admin = $this->adminUser();

        /** @var \App\Models\User $firstUser */
        $firstUser = User::factory()->create([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $secondUser */
        $secondUser = User::factory()->create([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee('スタッフ一覧');

        $response->assertSee('山田 太郎');
        $response->assertSee('yamada@example.com');

        $response->assertSee('佐藤 花子');
        $response->assertSee('sato@example.com');
    }
}
