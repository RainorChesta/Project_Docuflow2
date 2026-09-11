<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registered_user_is_pending_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isPendingVerification());
        $this->assertFalse($user->isVerified());
    }

    public function test_unverified_user_is_redirected_to_verification_pending_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'division_id' => null,
            'system_role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Test-Enforce-Verification', '1')
            ->post('/login', [
                'email' => 'unverified@example.com',
                'password' => 'password123',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('verification.pending'));
    }

    public function test_unverified_user_is_locked_out_of_dashboard_and_features(): void
    {
        $user = User::factory()->create([
            'division_id' => null,
            'system_role' => 'user',
            'is_active' => true,
        ]);

        // Attempt accessing dashboard
        $response = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->get('/dashboard');

        $response->assertRedirect(route('verification.pending'));

        // Attempt accessing documents
        $response = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->get('/documents');

        $response->assertRedirect(route('verification.pending'));

        // Attempt JSON API request
        $response = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->getJson('/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'AccountPendingVerification',
            ]);
    }

    public function test_unverified_user_can_view_verification_pending_screen_and_logout(): void
    {
        $user = User::factory()->create([
            'division_id' => null,
            'system_role' => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->get('/verification-pending');

        $response->assertStatus(200);
        $response->assertSee(__('Menunggu Verifikasi Admin'));
        $response->assertSee($user->email);

        // Test in Indonesian locale
        $responseId = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->withSession(['locale' => 'id'])
            ->get('/verification-pending');

        $responseId->assertStatus(200);
        $responseId->assertSee('Menunggu Verifikasi Admin');

        // User can log out
        $logoutResponse = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->post('/logout');

        $this->assertGuest();
        $logoutResponse->assertRedirect('/');
    }

    public function test_verified_user_accesses_dashboard_normally_and_cannot_visit_verification_pending(): void
    {
        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'IT Dept', 'code' => 'IT']);

        $user = User::factory()->create([
            'division_id' => $division->id,
            'system_role' => 'user',
            'is_active' => true,
        ]);
        $user->companies()->attach($company->id);
        $user->branches()->attach($branch->id);
        $user->divisions()->attach($division->id);

        $this->assertTrue($user->isVerified());
        $this->assertFalse($user->isPendingVerification());

        // Login redirects to dashboard for verified user
        $response = $this->withHeader('X-Test-Enforce-Verification', '1')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));

        // If verified user tries to access /verification-pending, redirected to dashboard
        $pendingResponse = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->get('/verification-pending');

        $pendingResponse->assertRedirect(route('dashboard'));
    }

    public function test_admin_user_management_can_filter_pending_verification_users(): void
    {
        $admin = User::factory()->create([
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        $company = Company::create(['name' => 'PT Sukses', 'code' => 'SKS']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'HR Dept', 'code' => 'HR']);

        // Verified user
        $verifiedUser = User::factory()->create([
            'name' => 'Verified Karyawan',
            'division_id' => $division->id,
            'system_role' => 'user',
            'is_active' => true,
        ]);
        $verifiedUser->companies()->attach($company->id);
        $verifiedUser->branches()->attach($branch->id);

        // Unverified user
        $unverifiedUser = User::factory()->create([
            'name' => 'Pending Karyawan',
            'division_id' => null,
            'system_role' => 'user',
            'is_active' => true,
        ]);

        // Filter status=pending
        $response = $this->actingAs($admin)
            ->get('/admin/users?status=pending');

        $response->assertStatus(200);
        $response->assertSee('Pending Karyawan');
        $response->assertDontSee('Verified Karyawan');

        // Filter status=1 (Aktif Verified)
        $responseActive = $this->actingAs($admin)
            ->get('/admin/users?status=1');

        $responseActive->assertStatus(200);
        $responseActive->assertSee('Verified Karyawan');
        $responseActive->assertDontSee('Pending Karyawan');
    }

    public function test_admin_verifying_user_by_assigning_division_and_company_branch_unlocks_account(): void
    {
        $admin = User::factory()->create([
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        $company = Company::create(['name' => 'PT Maju', 'code' => 'MJU']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang', 'is_pusat' => false, 'code' => 'CBG']);
        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'division_id' => null,
            'system_role' => 'user',
            'is_active' => true,
        ]);

        $this->assertFalse($user->isVerified());

        // Admin updates user, assigning division, company, branch
        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'system_role' => 'user',
            'is_active' => 1,
            'division_ids' => [$division->id],
            'company_ids' => [$company->id],
            'branch_ids' => [$branch->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue($user->isVerified());
        $this->assertFalse($user->isPendingVerification());
        $this->assertEquals($division->id, $user->division_id);
        $this->assertTrue($user->companies->contains($company->id));
        $this->assertTrue($user->branches->contains($branch->id));

        // Now user can access dashboard without lock
        $accessResponse = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->get('/dashboard');

        $accessResponse->assertStatus(200);
    }

    public function test_verification_pending_page_and_status_api_reflect_partial_verification_progress(): void
    {
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\UserVerificationUpdated::class,
        ]);

        $admin = User::factory()->create([
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        $division = Division::create(['name' => 'IT Engineering', 'code' => 'IT']);
        $company = Company::create(['name' => 'PT Doku Tech', 'code' => 'DOKU']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);

        $user = User::factory()->create([
            'name' => 'Rian Pratama',
            'email' => 'rian@example.com',
            'division_id' => null,
            'system_role' => 'user',
            'is_active' => true,
        ]);

        // 1. Initial State: Unverified, no division, no company/branch
        $statusResp = $this->actingAs($user)->getJson('/verification-status');
        $statusResp->assertStatus(200)
            ->assertJson([
                'is_verified' => false,
                'has_division' => false,
                'has_company' => false,
                'has_branch' => false,
                'has_company_and_branch' => false,
            ]);

        // 2. Admin only assigns Division without assigning Company and Branch
        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Rian Pratama',
            'email' => 'rian@example.com',
            'system_role' => 'user',
            'is_active' => 1,
            'division_ids' => [$division->id],
            'company_ids' => [],
            'branch_ids' => [],
        ]);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\UserVerificationUpdated::class);

        $user->refresh();

        // Verification status endpoint now reflects division assigned, but company & branch still pending
        $statusResp2 = $this->actingAs($user)->getJson('/verification-status');
        $statusResp2->assertStatus(200)
            ->assertJson([
                'is_verified' => false,
                'has_division' => true,
                'has_company' => false,
                'has_branch' => false,
                'has_company_and_branch' => false,
                'division_names' => ['IT Engineering'],
            ]);

        // Verification pending blade view reflects Division step completed while Company & Branch pending
        $pageResp = $this->actingAs($user)
            ->withHeader('X-Test-Enforce-Verification', '1')
            ->get('/verification-pending');

        $pageResp->assertStatus(200);
        $pageResp->assertSee('IT Engineering');
        $pageResp->assertSee('hasDivision: true', false);
        $pageResp->assertSee('hasCompany: false', false);
        $pageResp->assertSee('hasBranch: false', false);

        // 3. Admin now assigns Company and Branch
        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Rian Pratama',
            'email' => 'rian@example.com',
            'system_role' => 'user',
            'is_active' => 1,
            'division_ids' => [$division->id],
            'company_ids' => [$company->id],
            'branch_ids' => [$branch->id],
        ]);

        $statusResp3 = $this->actingAs($user)->getJson('/verification-status');
        $statusResp3->assertStatus(200)
            ->assertJson([
                'is_verified' => true,
                'has_division' => true,
                'has_company' => true,
                'has_branch' => true,
                'has_company_and_branch' => true,
                'redirect_url' => route('dashboard'),
            ]);
    }
}
