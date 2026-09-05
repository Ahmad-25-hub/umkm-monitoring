<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_employee_login_from_employee_dashboard(): void
    {
        $this->get(route('employee.dashboard'))
            ->assertRedirectToRoute('employee.login');
    }

    public function test_authenticated_user_without_employee_membership_is_sent_to_business_code_step(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertRedirectToRoute('employee.business.join.create');
    }

    public function test_employee_dashboard_renders_task_workspace_for_active_business(): void
    {
        $employee = User::factory()->create([
            'name' => 'Karyawan Nyata',
            'email' => 'karyawan@nyata.test',
        ]);
        $business = Business::factory()->create(['name' => 'Usaha Aktif Karyawan']);
        BusinessMembership::factory()
            ->employee()
            ->for($employee)
            ->for($business)
            ->create();

        $this->actingAs($employee)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Dashboard Karyawan')
            ->assertSeeText('Tugas Anda hari ini.')
            ->assertSeeText('Usaha Aktif Karyawan')
            ->assertSeeText('Karyawan Nyata')
            ->assertSeeText('Unit terjual')
            ->assertSeeText('Data stok belum tersedia')
            ->assertDontSeeText('Aman & Terkendali')
            ->assertDontSeeText('Tidak ada peringatan stok kritis')
            ->assertSeeText('Daftar tugas');
    }

    public function test_employee_session_cannot_select_business_without_active_employee_membership(): void
    {
        $employee = User::factory()->create();
        $allowedBusiness = Business::factory()->create(['name' => 'Tenant Karyawan Diizinkan']);
        $otherBusiness = Business::factory()->create(['name' => 'Tenant Karyawan Terlarang']);
        BusinessMembership::factory()
            ->employee()
            ->for($employee)
            ->for($allowedBusiness)
            ->create();

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $otherBusiness->id])
            ->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSessionHas('active_employee_business_id', $allowedBusiness->id)
            ->assertSeeText('Tenant Karyawan Diizinkan')
            ->assertDontSeeText('Tenant Karyawan Terlarang');
    }

    public function test_owner_team_performance_uses_real_employees_from_active_tenant_only(): void
    {
        $owner = User::factory()->create();
        $activeBusiness = Business::factory()->create(['name' => 'Tenant Pemilik Aktif']);
        BusinessMembership::factory()
            ->for($owner)
            ->for($activeBusiness)
            ->create();

        $activeEmployee = User::factory()->create([
            'name' => 'Anggota Tenant Aktif',
            'email' => 'aktif@tenant.test',
        ]);
        BusinessMembership::factory()
            ->employee()
            ->for($activeEmployee)
            ->for($activeBusiness)
            ->create();

        $otherBusiness = Business::factory()->create(['name' => 'Tenant Lain']);
        $otherEmployee = User::factory()->create([
            'name' => 'Anggota Tenant Lain',
            'email' => 'lain@tenant.test',
        ]);
        BusinessMembership::factory()
            ->employee()
            ->for($otherEmployee)
            ->for($otherBusiness)
            ->create();

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $activeBusiness->id])
            ->get(route('overview'))
            ->assertOk()
            ->assertSeeText('Anggota tim')
            ->assertSeeText('1 dari 1 karyawan aktif')
            ->assertSeeText('Anggota Tenant Aktif')
            ->assertSeeText('aktif@tenant.test')
            ->assertDontSeeText('Anggota Tenant Lain')
            ->assertDontSeeText('lain@tenant.test')
            ->assertDontSeeText('Rp12,4 jt penjualan');
    }
}
