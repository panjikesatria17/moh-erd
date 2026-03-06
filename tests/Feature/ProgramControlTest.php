<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_is_enabled_by_default_for_non_super_admin(): void
    {
        $purchasing = User::query()->create([
            'name' => 'Purchasing Program Default',
            'email' => 'purchasing.program.default@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $response = $this->actingAs($purchasing)->get(route('ui.purchase-orders.index'));

        $response->assertOk();
    }

    public function test_non_super_admin_is_blocked_when_program_disabled(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_enabled'],
            ['value' => '0']
        );

        $owner = User::query()->create([
            'name' => 'Owner Program Disabled',
            'email' => 'owner.program.disabled@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $response = $this->actingAs($owner)->get(route('ui.dashboard'));

        $response->assertStatus(423);
        $response->assertSee('Program Sedang Dinonaktifkan');
    }

    public function test_super_admin_can_access_program_control_and_toggle_program(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_enabled'],
            ['value' => '1']
        );

        $superAdmin = User::query()->create([
            'name' => 'Super Admin Program Control',
            'email' => 'superadmin.program.control@example.com',
            'password' => 'password123',
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('ui.program-control.index'))
            ->assertOk()
            ->assertSee('Kontrol Program Global');

        $this->actingAs($superAdmin)
            ->post(route('ui.program-control.update'), ['enabled' => '0'])
            ->assertRedirect(route('ui.program-control.index'));

        $this->assertDatabaseHas('app_settings', [
            'key' => 'program_enabled',
            'value' => '0',
        ]);
    }
}
