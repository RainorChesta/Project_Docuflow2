<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccordionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_branch_accordion_with_pusat_and_sub_branches(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);

        $companyA = Company::create(['name' => 'PT Alfa Harmoni', 'code' => 'ALF']);
        $pusatA = Branch::create(['company_id' => $companyA->id, 'name' => 'Pusat Alfa', 'is_pusat' => true]);
        $branchA1 = Branch::create(['company_id' => $companyA->id, 'name' => 'Cabang Bandung', 'is_pusat' => false, 'code' => 'BDG']);
        $branchA2 = Branch::create(['company_id' => $companyA->id, 'name' => 'Cabang Surabaya', 'is_pusat' => false, 'code' => 'SBY']);

        $companyB = Company::create(['name' => 'PT Beta Mandiri', 'code' => 'BET']);
        $pusatB = Branch::create(['company_id' => $companyB->id, 'name' => 'Pusat Beta', 'is_pusat' => true]);

        $response = $this->actingAs($admin)->get(route('admin.branches.index'));

        $response->assertOk();
        $response->assertViewHas('companyGroups');
        $response->assertViewHas('companies');

        // Check column header
        $response->assertSee('Jumlah Cabang');

        // Check Company A and its Pusat
        $response->assertSee('PT ALFA HARMONI');
        $response->assertSee('PUSAT ALFA');
        $response->assertSee('2 Cabang');
        $response->assertSee('CABANG BANDUNG');
        $response->assertSee('CABANG SURABAYA');
        $response->assertSee('BDG');
        $response->assertSee('SBY');

        // Check Company B and its Pusat
        $response->assertSee('PT BETA MANDIRI');
        $response->assertSee('PUSAT BETA');
        $response->assertSee('Hanya Pusat');
    }

    public function test_admin_can_filter_branches_by_company(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);

        $companyA = Company::create(['name' => 'PT Alfa Harmoni', 'code' => 'ALF']);
        Branch::create(['company_id' => $companyA->id, 'name' => 'Pusat Alfa', 'is_pusat' => true]);
        Branch::create(['company_id' => $companyA->id, 'name' => 'Cabang Bandung', 'is_pusat' => false, 'code' => 'BDG']);

        $companyB = Company::create(['name' => 'PT Beta Mandiri', 'code' => 'BET']);
        Branch::create(['company_id' => $companyB->id, 'name' => 'Pusat Beta', 'is_pusat' => true]);

        $response = $this->actingAs($admin)->get(route('admin.branches.index', ['company_id' => $companyA->id]));

        $response->assertOk();
        $response->assertSee('PT ALFA HARMONI');
        $response->assertSee('CABANG BANDUNG');

        $groups = $response->viewData('companyGroups');
        $this->assertCount(1, $groups);
        $this->assertTrue($groups->contains('id', $companyA->id));
        $this->assertFalse($groups->contains('id', $companyB->id));
    }

    public function test_non_admin_cannot_access_branch_master(): void
    {
        $regularUser = User::factory()->create(['system_role' => 'user']);

        $response = $this->actingAs($regularUser)->get(route('admin.branches.index'));

        $response->assertForbidden();
    }
}
