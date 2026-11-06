<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DocumentDeletionRequested;
use App\Notifications\DocumentDeletionResult;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentActiveDeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private UnitKerja $unitKerja;
    private User $admin;
    private User $owner;
    private User $otherUser;
    private DocumentType $docType;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'PT Test Sejahtera', 'code' => 'TEST']);
        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Cimahi', 'code' => 'CMH', 'is_pusat' => true]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Kearsipan', 'kode_unit_kerja' => 'ARS']);

        $this->admin = User::factory()->create([
            'name' => 'Super Administrator',
            'system_role' => 'admin',
            'is_active' => true,
        ]);
        $this->admin->companies()->attach($this->company->id);
        $this->admin->branches()->attach($this->branch->id);

        $this->owner = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja->id,
            'name' => 'Staff Pembuat',
            'system_role' => 'user',
            'is_active' => true,
        ]);
        $this->owner->companies()->attach($this->company->id);
        $this->owner->branches()->attach($this->branch->id);

        $this->otherUser = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja->id,
            'name' => 'Staff Lain',
            'system_role' => 'user',
            'is_active' => true,
        ]);
        $this->otherUser->companies()->attach($this->company->id);
        $this->otherUser->branches()->attach($this->branch->id);

        $this->docType = DocumentType::create(['name' => 'Berita Acara', 'code' => 'BA', 'category' => 'arsip']);

        $this->document = Document::create([
            'document_number' => '002/BA/CMH/XI/2026',
            'title' => 'Berita Acara Serah Terima Aset',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'visibility' => Document::VISIBILITY_GENERAL,
        ]);

        // Active version
        DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_number' => 1,
            'content' => 'Official Content',
            'file_path' => 'documents/test.docx',
            'author_id' => $this->owner->id,
            'author_name' => $this->owner->name,
            'status' => 'active',
        ]);
    }

    public function test_non_admin_owner_can_request_deletion_with_notes(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)
            ->post(route('documents.request-deletion', $this->document), [
                'notes' => 'Dokumen ini dibuat salah nomor dan perlu diganti.',
            ]);

        $response->assertRedirect(route('documents.show', $this->document));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'id' => $this->document->id,
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_request_notes' => 'Dokumen ini dibuat salah nomor dan perlu diganti.',
        ]);

        Notification::assertSentTo(
            $this->admin,
            DocumentDeletionRequested::class,
            function ($notification) {
                return $notification->document->id === $this->document->id
                    && $notification->requesterName === $this->owner->name
                    && $notification->notes === 'Dokumen ini dibuat salah nomor dan perlu diganti.';
            }
        );
    }

    public function test_deletion_request_requires_notes(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('documents.request-deletion', $this->document), [
                'notes' => '',
            ]);

        $response->assertSessionHasErrors(['notes']);
        $this->assertNull($this->document->fresh()->deletion_requested_by_id);
    }

    public function test_non_owner_cannot_request_deletion(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->post(route('documents.request-deletion', $this->document), [
                'notes' => 'Unauthorized deletion attempt',
            ]);

        $response->assertStatus(403);
        $this->assertNull($this->document->fresh()->deletion_requested_by_id);
    }

    public function test_numbering_sequence_context_evaluates_gap_risk_accurately(): void
    {
        // Document 1
        $doc1 = Document::create([
            'document_number' => '001/BA/CMH/XI/2026',
            'title' => 'Dokumen Pertama',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);

        // Document 3
        $doc3 = Document::create([
            'document_number' => '003/BA/CMH/XI/2026',
            'title' => 'Dokumen Ketiga',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
        ]);

        $service = app(DocumentService::class);

        // For Doc 2 (in between 001 and 003): has gap risk
        $context2 = $service->getNumberingSequenceContext($this->document);
        $this->assertTrue($context2['has_sequence']);
        $this->assertEquals(2, $context2['current_seq']);
        $this->assertEquals('001/BA/CMH/XI/2026', $context2['previous_document']['document_number']);
        $this->assertEquals('003/BA/CMH/XI/2026', $context2['next_document']['document_number']);
        $this->assertTrue($context2['has_gap_risk']);
        $this->assertFalse($context2['is_latest']);

        // For Doc 3 (latest in series): no gap risk, is latest
        $context3 = $service->getNumberingSequenceContext($doc3);
        $this->assertTrue($context3['has_sequence']);
        $this->assertEquals(3, $context3['current_seq']);
        $this->assertEquals('002/BA/CMH/XI/2026', $context3['previous_document']['document_number']);
        $this->assertNull($context3['next_document']);
        $this->assertFalse($context3['has_gap_risk']);
        $this->assertTrue($context3['is_latest']);
    }

    public function test_admin_can_approve_deletion_request(): void
    {
        Notification::fake();

        $this->document->update([
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_requested_at' => now(),
            'deletion_request_notes' => 'Nomor salah urutan.',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('approvals.deletion-request.approve', $this->document));

        $response->assertSessionHas('success');

        $freshDoc = Document::withTrashed()->find($this->document->id);
        $this->assertTrue($freshDoc->trashed());
        $this->assertNull($freshDoc->deletion_requested_by_id);
        $this->assertNull($freshDoc->deletion_request_notes);

        Notification::assertSentTo(
            $this->owner,
            DocumentDeletionResult::class,
            function ($notification) {
                return $notification->status === 'approved'
                    && $notification->actorName === $this->admin->name;
            }
        );
    }

    public function test_admin_can_reject_deletion_request(): void
    {
        Notification::fake();

        $this->document->update([
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_requested_at' => now(),
            'deletion_request_notes' => 'Ingin menghapus dokumen aktif.',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('approvals.deletion-request.reject', $this->document), [
                'notes' => 'Dokumen ini sudah didistribusikan ke klien, tidak boleh dihapus.',
            ]);

        $response->assertSessionHas('success');

        $freshDoc = $this->document->fresh();
        $this->assertFalse($freshDoc->trashed());
        $this->assertNull($freshDoc->deletion_requested_by_id);
        $this->assertNull($freshDoc->deletion_request_notes);

        Notification::assertSentTo(
            $this->owner,
            DocumentDeletionResult::class,
            function ($notification) {
                return $notification->status === 'rejected'
                    && $notification->actorName === $this->admin->name
                    && $notification->notes === 'Dokumen ini sudah didistribusikan ke klien, tidak boleh dihapus.';
            }
        );
    }

    public function test_owner_can_cancel_deletion_request(): void
    {
        $this->document->update([
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_requested_at' => now(),
            'deletion_request_notes' => 'Ingin hapus.',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('documents.cancel-deletion-request', $this->document));

        $response->assertRedirect(route('documents.show', $this->document));
        $response->assertSessionHas('success');

        $freshDoc = $this->document->fresh();
        $this->assertNull($freshDoc->deletion_requested_by_id);
        $this->assertNull($freshDoc->deletion_request_notes);
    }

    public function test_admin_can_access_approvals_deletions_tab(): void
    {
        $this->document->update([
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_requested_at' => now(),
            'deletion_request_notes' => 'Alasan hapus aktif.',
        ]);

        // Admin can access
        $response = $this->actingAs($this->admin)
            ->get(route('approvals.deletions'));

        $response->assertStatus(200);
        $response->assertSee('Permohonan Penghapusan Menunggu Persetujuan');
        $response->assertSee($this->document->title);
        $response->assertSee('Alasan hapus aktif.');

        // Non-admin is blocked
        $nonAdminResponse = $this->actingAs($this->owner)
            ->get(route('approvals.deletions'));

        $nonAdminResponse->assertStatus(403);
    }

    public function test_owner_sees_request_delete_modal_on_active_document_show(): void
    {
        // Owner visits active document before requesting deletion
        $response = $this->actingAs($this->owner)
            ->get(route('documents.show', $this->document));

        $response->assertStatus(200);
        $response->assertSee('request-delete-modal-' . $this->document->id);
        $response->assertSee('Tata Kelola Dokumen Korporat');
        $response->assertSee('Ajukan Permohonan Penghapusan');

        // Owner requests deletion
        $this->document->update([
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_requested_at' => now(),
            'deletion_request_notes' => 'Alasan hapus aktif saya.',
        ]);

        $responsePending = $this->actingAs($this->owner)
            ->get(route('documents.show', $this->document));

        $responsePending->assertStatus(200);
        $responsePending->assertSee('Menunggu Persetujuan Hapus');
        $responsePending->assertSee('Batalkan Permohonan');
    }

    public function test_admin_sees_numbering_consideration_banner_on_show(): void
    {
        $this->document->update([
            'deletion_requested_by_id' => $this->owner->id,
            'deletion_requested_at' => now(),
            'deletion_request_notes' => 'Mohon hapus dokumen ini karena dobel penomoran.',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('documents.show', $this->document));

        $response->assertStatus(200);
        $response->assertSee('Permohonan Penghapusan Dokumen Aktif');
        $response->assertSee('Pertimbangan Penomoran Seri Dokumen');
        $response->assertSee('Dokumen Ini (Target Hapus)');
        $response->assertSee('Mohon hapus dokumen ini karena dobel penomoran.');
        $response->assertSee('Setujui & Hapus');
        $response->assertSee('Tolak');
    }
}
