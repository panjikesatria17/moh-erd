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
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_lock_mode'],
            ['value' => 'hard_lock']
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
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_lock_mode'],
            ['value' => 'hard_lock']
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
            ->post(route('ui.program-control.update'), [
                'enabled' => '0',
                'lock_mode' => 'read_only',
                'license_expires_at' => now()->addDays(30)->toDateString(),
                'license_grace_days' => 3,
            ])
            ->assertRedirect(route('ui.program-control.index'));

        $this->assertDatabaseHas('app_settings', [
            'key' => 'program_enabled',
            'value' => '0',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'program_lock_mode',
            'value' => 'read_only',
        ]);
    }

    public function test_non_super_admin_can_read_but_cannot_write_when_program_off_and_read_only_mode(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_enabled'],
            ['value' => '0']
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_lock_mode'],
            ['value' => 'read_only']
        );

        $purchasing = User::query()->create([
            'name' => 'Purchasing Program ReadOnly',
            'email' => 'purchasing.program.readonly@example.com',
            'password' => 'password123',
            'role' => UserRole::PURCHASING->value,
        ]);

        $this->actingAs($purchasing)
            ->get(route('ui.purchase-orders.index'))
            ->assertOk();

        $this->actingAs($purchasing)
            ->post(route('ui.program-control.update'), [
                'enabled' => '1',
                'lock_mode' => 'hard_lock',
            ])
            ->assertStatus(423);
    }

    public function test_license_expiry_and_grace_period_can_force_program_off(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_enabled'],
            ['value' => '1']
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_lock_mode'],
            ['value' => 'hard_lock']
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_license_expires_at'],
            ['value' => now()->subDays(5)->toDateTimeString()]
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'program_license_grace_days'],
            ['value' => '2']
        );

        $owner = User::query()->create([
            'name' => 'Owner Expired License',
            'email' => 'owner.expired.license@example.com',
            'password' => 'password123',
            'role' => UserRole::OWNER->value,
        ]);

        $this->actingAs($owner)
            ->get(route('ui.dashboard'))
            ->assertStatus(423)
            ->assertSee('masa lisensi sudah berakhir');
    }
}
