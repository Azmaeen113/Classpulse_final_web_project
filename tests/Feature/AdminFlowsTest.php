<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_accurate_counts(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->teacher()->count(2)->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['users'] === 6
                && $stats['teachers'] === 2
                && $stats['students'] === 3;
        });
    }

    public function test_admin_can_search_and_suspend_user(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create([
            'name' => 'Suspend Me',
            'email' => 'suspendme@classpulse.test',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'suspendme']))
            ->assertOk()
            ->assertSee('Suspend Me', false);

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $student))
            ->assertRedirect();

        $this->assertFalse($student->fresh()->is_active);

        $this->actingAs($student->fresh())
            ->get(route('student.dashboard'))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->post(route('admin.users.activate', $student))
            ->assertRedirect();

        $this->assertTrue($student->fresh()->is_active);

        $this->actingAs($student->fresh())
            ->get(route('student.dashboard'))
            ->assertOk();
    }

    public function test_activity_log_page_lists_actions(): void
    {
        $admin = User::factory()->admin()->create();
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'auth.login',
            'description' => 'Admin logged in for test',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Admin logged in for test', false);
    }
}
