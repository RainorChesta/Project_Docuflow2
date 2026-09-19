<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\ApprovalRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApprovalRoutingService $service;
    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ApprovalRoutingService::class);
        
        // Setup base organization
        $this->company = Company::create(['name' => 'PT Test', 'code' => 'TST']);
        $this->branch = Branch::create(['name' => 'Branch Test', 'company_id' => $this->company->id]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'IT Unit', 'kode_unit_kerja' => '01']);
    }

    public function test_it_resolves_head_when_available()
    {
        $head = User::create([
            'name' => 'Head User',
            'email' => 'head@example.com',
            'password' => bcrypt('password'),
            'system_role' => 'head',
            'unit_kerja_id' => $this->unitKerja->id,
            'is_active' => true
        ]);
        $head->companies()->attach($this->company->id);
        $head->branches()->attach($this->branch->id);

        $documentType = DocumentType::create(['name' => 'SOP', 'code' => 'SOP', 'category' => 'akreditasi']);

        $document = Document::create([
            'title' => 'Test',
            'document_number' => '123',
            'owner_id' => $head->id,
            'document_type_id' => $documentType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);

        $result = $this->service->resolveApprover($document);

        $this->assertFalse($result['isFallback']);
        $this->assertEquals('head', $result['role']);
        $this->assertTrue($result['approvers']->contains($head));
    }

    public function test_it_falls_back_to_admin_when_no_head()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'system_role' => 'admin',
            'is_active' => true
        ]);
        $admin->companies()->attach($this->company->id);

        $documentType = DocumentType::create(['name' => 'SOP 2', 'code' => 'SOP2', 'category' => 'akreditasi']);

        $document = Document::create([
            'title' => 'Test',
            'document_number' => '124',
            'owner_id' => $admin->id,
            'document_type_id' => $documentType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id, // no head exists for this unit kerja
        ]);

        $result = $this->service->resolveApprover($document);

        $this->assertTrue($result['isFallback']);
        $this->assertEquals('admin', $result['role']);
        $this->assertTrue($result['approvers']->contains($admin));
    }

    public function test_it_falls_back_to_direktur_when_no_head_or_admin()
    {
        $direktur = User::create([
            'name' => 'Direktur User',
            'email' => 'direktur@example.com',
            'password' => bcrypt('password'),
            'system_role' => 'direktur',
            'is_active' => true
        ]);
        $direktur->companies()->attach($this->company->id);

        $documentType = DocumentType::create(['name' => 'SOP 3', 'code' => 'SOP3', 'category' => 'akreditasi']);

        $document = Document::create([
            'title' => 'Test',
            'document_number' => '125',
            'owner_id' => $direktur->id,
            'document_type_id' => $documentType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id, // no head or admin exists
        ]);

        $result = $this->service->resolveApprover($document);

        $this->assertTrue($result['isFallback']);
        $this->assertEquals('direktur', $result['role']);
        $this->assertTrue($result['approvers']->contains($direktur));
    }
}
