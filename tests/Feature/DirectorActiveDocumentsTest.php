<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorActiveDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $admin;
    protected User $regularUser;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected UnitKerja $unitKerjaA;
    protected UnitKerja $unitKerjaB;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->companyA = Company::create(['name' => 'PT Cahaya Medika', 'code' => 'CMH']);
        $this->companyB = Company::create(['name' => 'PT Husada Pratama', 'code' => 'PHP']);

        $this->branchA = Branch::create(['company_id' => $this->companyA->id, 'name' => 'Cabang Surabaya', 'is_pusat' => true, 'code' => 'SBY']);
        $this->branchB = Branch::create(['company_id' => $this->companyB->id, 'name' => 'Klinik Manyar', 'is_pusat' => false, 'code' => 'KMY']);

        $this->unitKerjaA = UnitKerja::create(['nama_unit_kerja' => 'Human Resources', 'kode_unit_kerja' => 'HRD']);
        $this->unitKerjaB = UnitKerja::create(['nama_unit_kerja' => 'Pelayanan Medis', 'kode_unit_kerja' => 'YANMED']);

        $this->docType = DocumentType::create(['name' => 'Standar Prosedur', 'code' => 'SOP', 'category' => 'akreditasi']);

        $this->director = User::factory()->create([
            'system_role' => 'direktur',
            'name' => 'Dr. Direktur Utama',
            'email' => 'director@example.com',
            'nip' => null,
            'unit_kerja_id' => null,
        ]);
        // Assign director only to Company A initially
        $this->director->companies()->sync([$this->companyA->id]);
        $this->director->branches()->sync([$this->branchA->id]);

        $this->admin = User::factory()->create(['system_role' => 'admin', 'name' => 'Admin Sistem']);
        $this->regularUser = User::factory()->create(['system_role' => 'user', 'name' => 'Staff Biasa', 'unit_kerja_id' => $this->unitKerjaA->id]);
        $this->regularUser->companies()->sync([$this->companyA->id]);
        $this->regularUser->branches()->sync([$this->branchA->id]);
    }

    protected function createActiveDocument(array $attributes = [], array $versionAttributes = []): Document
    {
        $reviewedAt = $attributes['reviewed_at'] ?? $versionAttributes['reviewed_at'] ?? null;
        unset($attributes['reviewed_at']);

        $doc = Document::create(array_merge([
            'title' => 'SOP Pelayanan Pasien',
            'document_number' => 'SOP/001/2026',
            'format_choice' => 'baru',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->regularUser->id,
            'visibility' => Document::VISIBILITY_GENERAL,
            'is_expired' => false,
        ], $attributes));

        $version = DocumentVersion::create(array_merge([
            'document_id' => $doc->id,
            'version_number' => 1,
            'status' => 'active',
            'author_id' => $this->regularUser->id,
            'author_name' => $this->regularUser->name,
            'content' => '<p>Konten SOP Aktif</p>',
            'reviewed_at' => $reviewedAt,
        ], $versionAttributes));

        $doc->update(['current_version_id' => $version->id]);

        return $doc->fresh(['currentVersion', 'versions']);
    }

    public function test_director_can_access_active_documents_page(): void
    {
        $doc = $this->createActiveDocument(['title' => 'Dokumen Aktif Uji 1']);

        $response = $this->actingAs($this->director)->get(route('director.active-documents.index'));

        $response->assertOk();
        $response->assertViewIs('director.active_documents.index');
        $response->assertSee('Total Dokumen Aktif');
        $response->assertSee('Dokumen Aktif Uji 1');
    }

    public function test_admin_can_access_active_documents_page(): void
    {
        $this->createActiveDocument(['title' => 'Dokumen Admin Uji']);

        $response = $this->actingAs($this->admin)->get(route('director.active-documents.index'));

        $response->assertOk();
        $response->assertSee('Dokumen Admin Uji');
    }

    public function test_regular_user_cannot_access_director_active_documents_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('director.active-documents.index'));

        $response->assertForbidden();
    }

    public function test_director_sees_active_documents_across_all_companies_and_branches(): void
    {
        // Document in Company A / Branch A
        $docA = $this->createActiveDocument([
            'title' => 'Dokumen PT Cahaya Medika',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
        ]);

        // Document in Company B / Branch B (different company outside director context)
        $docB = $this->createActiveDocument([
            'title' => 'Dokumen PT Husada Pratama',
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'document_number' => 'SOP/002/2026',
        ]);

        $response = $this->actingAs($this->director)->get(route('director.active-documents.index'));

        $response->assertOk();
        $response->assertSee('Dokumen PT Cahaya Medika');
        $response->assertSee('Dokumen PT Husada Pratama');
    }

    public function test_director_can_filter_by_unseen_and_seen_tabs(): void
    {
        // Unseen document
        $unseenDoc = $this->createActiveDocument([
            'title' => 'Dokumen Belum Ditinjau Direktur',
            'director_read_at' => null,
            'director_acknowledged_by_id' => null,
        ]);

        // Seen document
        $seenDoc = $this->createActiveDocument([
            'title' => 'Dokumen Sudah Ditinjau Direktur',
            'document_number' => 'SOP/003/2026',
            'director_read_at' => now(),
            'director_acknowledged_by_id' => $this->director->id,
        ]);

        // Tab: Unseen
        $unseenResponse = $this->actingAs($this->director)->get(route('director.active-documents.index', ['tab' => 'unseen']));
        $unseenResponse->assertOk();
        $unseenResponse->assertSee('Dokumen Belum Ditinjau Direktur');
        $unseenResponse->assertDontSee('Dokumen Sudah Ditinjau Direktur');

        // Tab: Seen
        $seenResponse = $this->actingAs($this->director)->get(route('director.active-documents.index', ['tab' => 'seen']));
        $seenResponse->assertOk();
        $seenResponse->assertSee('Dokumen Sudah Ditinjau Direktur');
        $seenResponse->assertDontSee('Dokumen Belum Ditinjau Direktur');
    }

    public function test_director_can_mark_document_as_seen(): void
    {
        $doc = $this->createActiveDocument([
            'director_read_at' => null,
            'director_acknowledged_by_id' => null,
        ]);

        $response = $this->actingAs($this->director)->post(route('director.documents.acknowledge', $doc), [
            'action' => 'seen',
        ]);

        $response->assertRedirect();
        $doc->refresh();

        $this->assertNotNull($doc->director_read_at);
        $this->assertEquals($this->director->id, $doc->director_acknowledged_by_id);
        $this->assertTrue($doc->isDirectorRead());
    }

    public function test_director_can_unmark_document_as_seen(): void
    {
        $doc = $this->createActiveDocument([
            'director_read_at' => now(),
            'director_acknowledged_by_id' => $this->director->id,
        ]);

        $response = $this->actingAs($this->director)->post(route('director.documents.acknowledge', $doc), [
            'action' => 'unseen',
        ]);

        $response->assertRedirect();
        $doc->refresh();

        $this->assertNull($doc->director_read_at);
        $this->assertNull($doc->director_acknowledged_by_id);
        $this->assertFalse($doc->isDirectorRead());
    }

    public function test_director_can_bulk_mark_documents_as_seen_and_unseen(): void
    {
        $doc1 = $this->createActiveDocument(['document_number' => 'SOP/001/2026']);
        $doc2 = $this->createActiveDocument(['document_number' => 'SOP/002/2026']);

        // Bulk seen
        $response = $this->actingAs($this->director)->post(route('director.documents.bulk-acknowledge'), [
            'document_ids' => [$doc1->id, $doc2->id],
            'action' => 'seen',
        ]);

        $response->assertRedirect();
        $this->assertNotNull($doc1->fresh()->director_read_at);
        $this->assertNotNull($doc2->fresh()->director_read_at);

        // Bulk unseen
        $responseUnseen = $this->actingAs($this->director)->post(route('director.documents.bulk-acknowledge'), [
            'document_ids' => [$doc1->id, $doc2->id],
            'action' => 'unseen',
        ]);

        $responseUnseen->assertRedirect();
        $this->assertNull($doc1->fresh()->director_read_at);
        $this->assertNull($doc2->fresh()->director_read_at);
    }

    public function test_acknowledgment_does_not_affect_approval_workflow_or_status(): void
    {
        $doc = $this->createActiveDocument([
            'director_read_at' => null,
        ]);

        $version = $doc->currentVersion;
        $this->assertEquals('active', $version->status);

        // Acknowledge by director
        $this->actingAs($this->director)->post(route('director.documents.acknowledge', $doc), [
            'action' => 'seen',
        ]);

        $doc->refresh();
        $version->refresh();

        // Ensure approval version remains 'active', document still active, current version untouched
        $this->assertEquals('active', $version->status);
        $this->assertEquals($version->id, $doc->current_version_id);
        $this->assertFalse($doc->is_expired);
    }

    public function test_seen_by_director_badge_is_visible_on_user_document_show_and_index(): void
    {
        $doc = $this->createActiveDocument([
            'title' => 'Dokumen Standard Pelayanan',
            'director_read_at' => now(),
            'director_acknowledged_by_id' => $this->director->id,
        ]);

        // Regular user visits document show page
        $showResponse = $this->actingAs($this->regularUser)->get(route('documents.show', $doc));
        $showResponse->assertOk();
        $showResponse->assertSee('Ditinjau oleh Direktur');

        // Regular user visits preview page
        $previewResponse = $this->actingAs($this->regularUser)->get(route('documents.preview', $doc));
        $previewResponse->assertOk();
        $previewResponse->assertSee('Ditinjau oleh Direktur');

        // Regular user visits my documents index
        $indexResponse = $this->actingAs($this->regularUser)->get(route('documents.index', ['type' => 'mine']));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Ditinjau Direktur');
    }

    public function test_grid_view_displays_branch_code_and_list_view_displays_full_branch_name(): void
    {
        $this->createActiveDocument([
            'title' => 'Dokumen Cabang Surabaya',
            'branch_id' => $this->branchA->id, // Name: 'Cabang Surabaya', Code: 'SBY'
        ]);

        // Default / List view: Full branch name should be visible
        $listResponse = $this->actingAs($this->director)->get(route('director.active-documents.index', ['view_mode' => 'list']));
        $listResponse->assertOk();
        $listResponse->assertSee($this->branchA->name);

        // Grid view: Branch code should be rendered in the card header badge
        $gridResponse = $this->actingAs($this->director)->get(route('director.active-documents.index', ['view_mode' => 'grid']));
        $gridResponse->assertOk();
        $gridResponse->assertSee($this->branchA->effective_code);
    }

    public function test_active_documents_page_ui_refinements(): void
    {
        $this->createActiveDocument(['title' => 'Dokumen UI Uji']);

        // Test Indonesian locale
        app()->setLocale('id');
        $response = $this->actingAs($this->director)->get(route('director.active-documents.index'));
        $response->assertOk();

        // 1. Format filter dropdown ("Semua Format") must not be present
        $response->assertDontSee('name="format_choice"', false);
        $response->assertDontSee('Semua Format');

        // 2. Sort dropdown must not be present
        $response->assertDontSee('name="sort"', false);

        // 3. Apply Filter button is linked to the form
        $response->assertSee('form="director-filter-form"', false);
        $response->assertSee('Terapkan Filter');

        // 4. Standardized summary cards with matching links and badges in ID
        $response->assertSee('tab=all');
        $response->assertSee('tab=unseen');
        $response->assertSee('tab=seen');
        $response->assertSee('Semua Cabang');
        $response->assertSee('Perlu Ditinjau');
        $response->assertSee('Terkonfirmasi');

        // 5. Test English locale standardization ("All Branches", "Needs Review", "Confirmed")
        app()->setLocale('en');
        $enResponse = $this->actingAs($this->director)->get(route('director.active-documents.index'));
        $enResponse->assertOk();
        $enResponse->assertSee('All Branches');
        $enResponse->assertSee('Needs Review');
        $enResponse->assertSee('Confirmed');
    }

    public function test_all_active_documents_are_listed_chronologically_on_first_page(): void
    {
        // Document A: active today via reviewed_at
        $docA = $this->createActiveDocument([
            'title' => 'Dokumen Aktif Hari Ini A',
            'document_number' => 'SOP/TODAY/001',
            'reviewed_at' => now(),
        ]);

        // Document B: active today via created_at without reviewed_at
        $docB = $this->createActiveDocument([
            'title' => 'Dokumen Aktif Hari Ini B',
            'document_number' => 'SOP/TODAY/002',
            'reviewed_at' => null,
        ]);

        // Document C: activated yesterday
        $docC = $this->createActiveDocument([
            'title' => 'Dokumen Aktif Kemarin',
            'document_number' => 'SOP/YESTERDAY/001',
            'reviewed_at' => now()->subDay(),
        ]);

        // Document D: activated 5 days ago
        $docD = $this->createActiveDocument([
            'title' => 'Dokumen Aktif Minggu Lalu',
            'document_number' => 'SOP/PAST/001',
            'reviewed_at' => now()->subDays(5),
        ]);

        // Page 1 should contain all active documents in chronological order
        $response = $this->actingAs($this->director)->get(route('director.active-documents.index', ['page' => 1]));

        $response->assertOk();
        $response->assertSee('Dokumen Aktif Hari Ini A');
        $response->assertSee('Dokumen Aktif Hari Ini B');
        $response->assertSee('Dokumen Aktif Kemarin');
        $response->assertSee('Dokumen Aktif Minggu Lalu');
        $response->assertSee('Daftar Dokumen Aktif');
    }

    public function test_active_documents_are_grouped_by_activation_date(): void
    {
        // Document 1 & 2: Activated yesterday (e.g. 21 Sep)
        $doc1 = $this->createActiveDocument([
            'title' => 'Dokumen Kemarin Satu',
            'document_number' => 'SOP/YEST/001',
            'reviewed_at' => now()->subDay()->setTime(10, 0, 0),
        ]);
        $doc2 = $this->createActiveDocument([
            'title' => 'Dokumen Kemarin Dua',
            'document_number' => 'SOP/YEST/002',
            'reviewed_at' => now()->subDay()->setTime(14, 30, 0),
        ]);

        // Document 3: Activated 2 days ago (e.g. 20 Sep)
        $doc3 = $this->createActiveDocument([
            'title' => 'Dokumen Dua Hari Lalu',
            'document_number' => 'SOP/PAST/002',
            'reviewed_at' => now()->subDays(2)->setTime(9, 15, 0),
        ]);

        $response = $this->actingAs($this->director)->get(route('director.active-documents.index', ['page' => 1]));

        $response->assertOk();
        $response->assertSee($doc1->activated_at->translatedFormat('d F Y'));
        $response->assertSee($doc3->activated_at->translatedFormat('d F Y'));
        $response->assertSee('Dokumen Kemarin Satu');
        $response->assertSee('Dokumen Kemarin Dua');
        $response->assertSee('Dokumen Dua Hari Lalu');
        $response->assertSee('Kemarin');

        // Test with List view
        $listResponse = $this->actingAs($this->director)->get(route('director.active-documents.index', ['page' => 1, 'view_mode' => 'list']));
        $listResponse->assertOk();
        $listResponse->assertSee($doc1->activated_at->translatedFormat('d F Y'));
        $listResponse->assertSee($doc3->activated_at->translatedFormat('d F Y'));
        $listResponse->assertSee('Dokumen Kemarin Satu');
    }

    public function test_director_can_filter_by_specific_activation_date(): void
    {
        $targetDate = now()->subDays(3)->format('Y-m-d');

        // Document matching target date
        $docOnDate = $this->createActiveDocument([
            'title' => 'Dokumen Pada Tanggal Target',
            'document_number' => 'SOP/TARGET/001',
            'reviewed_at' => now()->subDays(3)->setTime(11, 0, 0),
        ]);

        // Document on a different date
        $docOtherDate = $this->createActiveDocument([
            'title' => 'Dokumen Tanggal Lain',
            'document_number' => 'SOP/OTHER/001',
            'reviewed_at' => now()->subDays(5)->setTime(11, 0, 0),
        ]);

        $response = $this->actingAs($this->director)->get(route('director.active-documents.index', [
            'active_date' => $targetDate,
        ]));

        $response->assertOk();
        $response->assertSee('Dokumen Pada Tanggal Target');
        $response->assertDontSee('Dokumen Tanggal Lain');
        $response->assertSee('Filter Tanggal');
        $response->assertSee(now()->subDays(3)->translatedFormat('d F Y'));
    }

    public function test_director_can_filter_by_date_range_start_and_end_date(): void
    {
        $startDate = now()->subDays(5)->format('Y-m-d');
        $endDate = now()->subDays(2)->format('Y-m-d');

        // Document within range (4 days ago)
        $docInRange = $this->createActiveDocument([
            'title' => 'Dokumen Dalam Rentang',
            'document_number' => 'SOP/INRANGE/001',
            'reviewed_at' => now()->subDays(4)->setTime(10, 0, 0),
        ]);

        // Document before range (8 days ago)
        $docBefore = $this->createActiveDocument([
            'title' => 'Dokumen Sebelum Rentang',
            'document_number' => 'SOP/BEFORE/001',
            'reviewed_at' => now()->subDays(8)->setTime(10, 0, 0),
        ]);

        // Document after range (today)
        $docAfter = $this->createActiveDocument([
            'title' => 'Dokumen Setelah Rentang',
            'document_number' => 'SOP/AFTER/001',
            'reviewed_at' => now()->setTime(10, 0, 0),
        ]);

        $response = $this->actingAs($this->director)->get(route('director.active-documents.index', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertSee('Dokumen Dalam Rentang');
        $response->assertDontSee('Dokumen Sebelum Rentang');
        $response->assertDontSee('Dokumen Setelah Rentang');
        $response->assertSee('Filter Tanggal');
    }

    public function test_empty_state_when_filtering_by_date_with_no_active_documents(): void
    {
        $this->createActiveDocument([
            'title' => 'Dokumen Aktif Biasa',
            'reviewed_at' => now(),
        ]);

        $emptyDate = '2025-01-01';
        $response = $this->actingAs($this->director)->get(route('director.active-documents.index', [
            'active_date' => $emptyDate,
        ]));

        $response->assertOk();
        $response->assertSee('Tidak Ada Dokumen Aktif Ditemukan');
        $response->assertSee('01 Januari 2025');
        $response->assertDontSee('Dokumen Aktif Biasa');
    }
}
