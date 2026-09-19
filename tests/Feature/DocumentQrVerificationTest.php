<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentQrVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company1;
    protected Company $company2;
    protected Branch $branch1;
    protected Branch $branch2;
    protected UnitKerja $unitKerja1;
    protected UnitKerja $unitKerja2;
    protected User $owner;
    protected User $unauthorizedUser;
    protected Document $document;
    protected string $qrToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company1 = Company::create(['name' => 'Company One', 'code' => 'CP1']);
        $this->company2 = Company::create(['name' => 'Company Two', 'code' => 'CP2']);

        $this->branch1 = Branch::create(['company_id' => $this->company1->id, 'name' => 'Branch One', 'code' => 'BR1']);
        $this->branch2 = Branch::create(['company_id' => $this->company2->id, 'name' => 'Branch Two', 'code' => 'BR2']);

        $this->unitKerja1 = UnitKerja::create(['nama_unit_kerja' => 'Unit Kerja One', 'kode_unit_kerja' => '01']);
        $this->unitKerja2 = UnitKerja::create(['nama_unit_kerja' => 'Unit Kerja Two', 'kode_unit_kerja' => '02']);

        $docType = DocumentType::create(['name' => 'Internal Memo', 'code' => 'IM', 'category' => 'naskah_dinas']);

        // Owner in Company 1, Branch 1, Unit Kerja 1
        $this->owner = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja1->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->owner->branches()->attach($this->branch1->id);
        $this->owner->companies()->attach($this->company1->id);

        // Unauthorized user in Company 2, Branch 2, Unit Kerja 2
        $this->unauthorizedUser = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja2->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->unauthorizedUser->branches()->attach($this->branch2->id);
        $this->unauthorizedUser->companies()->attach($this->company2->id);

        // Restricted / Personal document
        $this->document = Document::create([
            'title' => 'Confidential Financial Plan',
            'document_number' => '001/IM/BR1/IX/2026',
            'unit_kerja_id' => $this->unitKerja1->id,
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'owner_id' => $this->owner->id,
            'document_type_id' => $docType->id,
            'visibility' => Document::VISIBILITY_PERSONAL,
        ]);

        $version = $this->document->versions()->create([
            'version_number' => 1,
            'content' => '<p>Confidential content</p>',
            'author_id' => $this->owner->id,
            'author_name' => $this->owner->name,
            'status' => 'active',
        ]);

        $this->document->update(['current_version_id' => $version->id]);

        $qrCodeService = app(QrCodeService::class);
        $url = $qrCodeService->qrcodeUrl($this->document);
        $this->qrToken = basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_guest_can_access_qr_verification_page_via_token()
    {
        $response = $this->get(route('documents.hash', ['token' => $this->qrToken]));

        $response->assertStatus(200);
        $response->assertViewIs('documents.verified');
        $response->assertSee('Confidential Financial Plan');
        $response->assertSee('001/IM/BR1/IX/2026');
        $response->assertSee('Unit Kerja One');
        $response->assertSee(__('Dokumen Valid & Terverifikasi'));
        // Ensure preview button uses the token-based URL, not the numeric ID
        $response->assertSee(route('documents.hash.preview', ['token' => $this->qrToken]));
    }

    public function test_user_without_document_access_can_access_qr_verification_page()
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('documents.hash', ['token' => $this->qrToken]));

        $response->assertStatus(200);
        $response->assertViewIs('documents.verified');
        $response->assertSee('Confidential Financial Plan');
        $response->assertSee('001/IM/BR1/IX/2026');
        $response->assertSee(__('Dokumen Valid & Terverifikasi'));
    }

    public function test_user_without_access_gets_full_standalone_403_without_sidebar_or_navbar()
    {
        // When clicking preview via QR hash token
        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('documents.hash.preview', ['token' => $this->qrToken]));

        $response->assertStatus(403);
        $response->assertSee(__('Dokumen Tidak Bisa Diakses'));
        
        // Assert standalone page: MUST NOT render app layout navigation elements
        $response->assertDontSee('id="themeToggleBtn"', false);
        $response->assertDontSee('contextSwitchForm', false);
        $response->assertDontSee('sidebar', false);
        $response->assertDontSee(__('Dokumen Umum'));
        $response->assertDontSee(__('Dokumen Saya'));
        
        // Assert QR context return button
        $response->assertSee(route('documents.hash', ['token' => $this->qrToken]));
    }

    public function test_user_without_access_gets_standalone_403_when_from_qr_param_passed()
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('documents.preview', ['document' => $this->document, 'from' => 'qr']));

        $response->assertStatus(403);
        $response->assertSee(__('Dokumen Tidak Bisa Diakses'));
        $response->assertDontSee('contextSwitchForm', false);
    }

    public function test_user_with_access_can_open_preview_via_hash()
    {
        $response = $this->actingAs($this->owner)
            ->get(route('documents.hash.preview', ['token' => $this->qrToken]));

        $response->assertStatus(200);
        $response->assertViewIs('documents.preview');
    }

    public function test_guest_opening_hash_preview_is_redirected_to_login()
    {
        $response = $this->get(route('documents.hash.preview', ['token' => $this->qrToken]));

        $response->assertRedirect(route('login'));
    }

    public function test_invalid_qr_token_returns_404()
    {
        $response = $this->get(route('documents.hash', ['token' => 'invalid-token-12345']));

        $response->assertStatus(404);
    }

    public function test_expired_document_status_on_verification_page()
    {
        $this->document->update([
            'is_expired' => true,
            'expiration_date' => now()->subDay(),
        ]);

        $response = $this->get(route('documents.hash', ['token' => $this->qrToken]));

        $response->assertStatus(200);
        $response->assertSee(__('Dokumen Kedaluwarsa'));
    }

    public function test_unapproved_document_status_on_verification_page()
    {
        $this->document->update(['current_version_id' => null]);

        $response = $this->get(route('documents.hash', ['token' => $this->qrToken]));

        $response->assertStatus(200);
        $response->assertSee(__('Dokumen Belum Disetujui'));
    }
}
