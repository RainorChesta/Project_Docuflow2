<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private DocumentType $docType;
    private Branch $branch;
    private Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $this->branch = Branch::create(['name' => 'Branch 1', 'code' => 'B1', 'company_id' => $company->id]);
        $this->division = Division::create(['name' => 'IT Dept', 'code' => 'IT']);
        $this->docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);

        $this->user = User::factory()->create([
            'system_role' => 'staff',
            'division_id' => $this->division->id,
            'is_active' => true,
        ]);
        $this->user->branches()->attach($this->branch->id);
        $this->user->companies()->attach($company->id);
    }

    public function test_check_number_returns_not_exists_for_available_number(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('documents.check-number', [
            'document_number' => '001/SK/IT/B1/IX/2026',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'checked' => true,
                'exists' => false,
            ]);
        $this->assertStringContainsString('BELUM DIGUNAKAN', $response->json('message'));
    }

    public function test_check_number_ignores_incomplete_sequence_numbers(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('documents.check-number', [
            'document_number' => '00/SOP-00/JBM/IX/2026',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'checked' => false,
            ]);
    }

    public function test_check_number_returns_exists_with_document_info_for_used_number(): void
    {
        $existingDoc = Document::create([
            'title' => 'Existing Policy Doc',
            'document_number' => '001/SK/IT/B1/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'division_id' => $this->division->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        // When checking 001, it exists
        $response1 = $this->actingAs($this->user)->getJson(route('documents.check-number', [
            'document_number' => '001/SK/IT/B1/IX/2026',
        ]));

        $response1->assertStatus(200)
            ->assertJson([
                'checked' => true,
                'exists' => true,
                'document' => [
                    'id' => $existingDoc->id,
                    'title' => 'Existing Policy Doc',
                ],
            ]);
        $this->assertStringContainsString('SUDAH DIGUNAKAN', $response1->json('message'));

        // When user changes to 002, it does NOT exist (available)
        $response2 = $this->actingAs($this->user)->getJson(route('documents.check-number', [
            'document_number' => '002/SK/IT/B1/IX/2026',
        ]));

        $response2->assertStatus(200)
            ->assertJson([
                'checked' => true,
                'exists' => false,
            ]);
        $this->assertStringContainsString('BELUM DIGUNAKAN', $response2->json('message'));
    }

    public function test_user_can_create_uploaded_document_with_manual_number(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'Manual Number Doc',
            'document_type_id' => $this->docType->id,
            'format_choice' => 'baru',
            'is_upload' => '1',
            'file' => $file,
            'document_number' => 'CUSTOM-999/SK/2026',
            'branch_id' => $this->branch->id,
            'branch_ids' => [$this->branch->id],
        ]);

        $this->assertDatabaseHas('documents', [
            'title' => 'Manual Number Doc',
            'document_number' => 'CUSTOM-999/SK/2026',
        ]);
    }

    public function test_validation_rejects_duplicate_uploaded_document_number(): void
    {
        Document::create([
            'title' => 'First Doc',
            'document_number' => 'DUPLICATE-001',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'division_id' => $this->division->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('test.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'Second Doc',
            'document_type_id' => $this->docType->id,
            'format_choice' => 'baru',
            'is_upload' => '1',
            'file' => $file,
            'document_number' => 'DUPLICATE-001',
            'branch_id' => $this->branch->id,
            'branch_ids' => [$this->branch->id],
        ]);

        $response->assertSessionHasErrors('document_number');
    }
}
