<?php

namespace Tests\Feature;

use App\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_manage_staff_accounts(): void
    {
        $staff = StaffUser::query()->create(['email' => 'staff@example.test', 'display_name' => 'Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);
        $this->actingAs($staff)->get(route('staff.users.index'))->assertForbidden();

        $admin = StaffUser::query()->create(['email' => 'admin@example.test', 'display_name' => 'Admin', 'password' => 'correct horse battery staple', 'role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->get(route('staff.users.index'))->assertOk()->assertSee('Staff accounts')->assertSee('ADMIN');
    }

    public function test_admin_can_create_and_deactivate_staff_account_but_not_self(): void
    {
        $admin = StaffUser::query()->create(['email' => 'admin@example.test', 'display_name' => 'Admin', 'password' => 'correct horse battery staple', 'role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->post(route('staff.users.store'), [
            'display_name' => 'New Inspector', 'email' => 'inspector@example.test', 'role' => 'staff',
            'password' => 'a sufficiently long password', 'password_confirmation' => 'a sufficiently long password',
        ])->assertRedirect(route('staff.users.index'));
        $user = StaffUser::query()->where('email', 'inspector@example.test')->firstOrFail();
        $this->assertTrue($user->active);

        $this->actingAs($admin)->post(route('staff.users.toggle', $user))->assertRedirect();
        $this->assertFalse($user->fresh()->active);
        $this->actingAs($admin)->post(route('staff.users.toggle', $admin))->assertSessionHasErrors('staff');
    }

    public function test_password_mismatch_is_reported_on_confirmation_field(): void
    {
        $admin = StaffUser::query()->create(['email' => 'mismatch-admin@example.test', 'display_name' => 'Admin', 'password' => 'correct horse battery staple', 'role' => 'admin', 'active' => true]);

        $this->actingAs($admin)->post(route('staff.users.store'), [
            'display_name' => 'Mismatch User', 'email' => 'mismatch@example.test', 'role' => 'staff',
            'password' => 'Rafay123', 'password_confirmation' => 'rafay123',
        ])->assertSessionHasErrors('password_confirmation');
    }

    public function test_admin_can_change_another_staff_members_role_and_audit_it(): void
    {
        $admin = StaffUser::query()->create(['email' => 'role-admin@example.test', 'display_name' => 'Admin', 'password' => 'correct horse battery staple', 'role' => 'admin', 'active' => true]);
        $staff = StaffUser::query()->create(['email' => 'role-staff@example.test', 'display_name' => 'Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);

        $this->actingAs($admin)->put(route('staff.users.role.update', $staff), ['role' => 'admin'])->assertRedirect();

        $this->assertDatabaseHas('staff_users', ['id' => $staff->id, 'role' => 'admin']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.role_updated', 'auditable_id' => $staff->id]);
    }

    public function test_admin_can_delete_an_inactive_staff_account_but_not_an_active_one(): void
    {
        $admin = StaffUser::query()->create(['email' => 'delete-admin@example.test', 'display_name' => 'Admin', 'password' => 'correct horse battery staple', 'role' => 'admin', 'active' => true]);
        $staff = StaffUser::query()->create(['email' => 'delete-staff@example.test', 'display_name' => 'Staff', 'password' => 'correct horse battery staple', 'role' => 'staff', 'active' => true]);

        $this->actingAs($admin)->delete(route('staff.users.destroy', $staff))->assertSessionHasErrors('staff');
        $this->actingAs($admin)->post(route('staff.users.toggle', $staff));
        $this->actingAs($admin)->delete(route('staff.users.destroy', $staff))->assertRedirect();

        $this->assertDatabaseMissing('staff_users', ['id' => $staff->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.deleted', 'auditable_id' => $staff->id]);
    }
}
