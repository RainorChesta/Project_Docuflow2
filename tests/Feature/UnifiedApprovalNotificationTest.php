<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DocumentApprovalRequested;
use App\Notifications\SignatureRequested;
use App\Services\ApprovalRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UnifiedApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected DocumentType $docType;
    protected User $author;
    protected User $signerHead;
    protected User $signerDirector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'PT CITRA MEDIKA HOSPITALITA', 'code' => 'CMH']);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'KANTOR PUSAT',
            'code' => 'PST',
            'is_pusat' => true,
        ]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Human Resources', 'kode_unit_kerja' => '03']);
        $this->docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK', 'category' => 'akreditasi']);

        $this->author = User::factory()->create([
            'name' => 'RehanDHU',
            'email' => 'rehan@cmh.co.id',
            'system_role' => 'user',
            'unit_kerja_id' => $this->unitKerja->id,
            'is_active' => true,
        ]);
        $this->author->companies()->attach($this->company->id);
        $this->author->branches()->attach($this->branch->id);

        $this->signerHead = User::factory()->create([
            'name' => 'Siti Kadiv',
            'email' => 'siti@cmh.co.id',
            'system_role' => 'head',
            'unit_kerja_id' => $this->unitKerja->id,
            'is_active' => true,
        ]);
        $this->signerHead->companies()->attach($this->company->id);
        $this->signerHead->branches()->attach($this->branch->id);

        $this->signerDirector = User::factory()->create([
            'name' => 'Budi Direktur',
            'email' => 'budi@cmh.co.id',
            'system_role' => 'direktur',
            'is_active' => true,
        ]);
        $this->signerDirector->companies()->attach($this->company->id);
        $this->signerDirector->branches()->attach($this->branch->id);
    }

    public function test_document_with_signature_request_sends_single_merged_notification(): void
    {
        Notification::fake();

        $sig = Signature::create([
            'user_id' => $this->signerHead->id,
            'file_path' => 'signatures/siti.png',
            'type' => 'original',
        ]);

        $document = Document::create([
            'title' => 'TESS',
            'document_number' => '006/SK-04/MMC-DHU/IX/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->author->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'status' => 'pending',
            'content' => '<p>Document content</p>',
        ]);

        $sigRequest = SignatureRequest::create([
            'requester_id' => $this->author->id,
            'target_user_id' => $this->signerHead->id,
            'document_id' => $document->id,
            'requested_signature_id' => $sig->id,
            'status' => 'pending',
            'notified_at' => null,
        ]);

        app(ApprovalRoutingService::class)->compileWorkflowFromSignatures($document, $version, $this->author);

        // 1. Verify DocumentApprovalRequested is sent to signerHead exactly ONCE
        Notification::assertSentToTimes(
            $this->signerHead,
            DocumentApprovalRequested::class,
            1
        );

        // 2. Verify duplicate SignatureRequested is NOT sent to signerHead
        Notification::assertNotSentTo(
            $this->signerHead,
            SignatureRequested::class
        );

        // 3. Verify notification payload contents
        $notification = new DocumentApprovalRequested($document, $version, $this->author->name);
        $data = $notification->toArray($this->signerHead);

        $this->assertEquals(__('Permintaan Persetujuan & Tanda Tangan'), $data['title']);
        $this->assertTrue($data['has_signature']);
        $this->assertFalse($data['is_stamp']);
        $this->assertEquals('signature', $data['icon']);
        $this->assertEquals('signature', $data['request_type']);
        $this->assertEquals($sigRequest->id, $data['signature_request_id']);
        $this->assertStringContainsString('RehanDHU', $data['message']);

        // 4. Verify signature request record notified_at was stamped
        $this->assertNotNull($sigRequest->fresh()->notified_at);
    }

    public function test_document_with_stamp_request_sends_single_merged_stamp_notification(): void
    {
        Notification::fake();

        $stamp = Signature::create([
            'user_id' => $this->signerDirector->id,
            'company_id' => $this->company->id,
            'file_path' => 'signatures/stamp.png',
            'type' => 'company_stamp',
        ]);

        $document = Document::create([
            'title' => 'TESS STAMP',
            'document_number' => '007/SK-04/MMC-DHU/IX/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->signerHead->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'author_id' => $this->signerHead->id,
            'author_name' => $this->signerHead->name,
            'status' => 'pending',
            'content' => '<p>Document content</p>',
        ]);

        $sigRequest = SignatureRequest::create([
            'requester_id' => $this->signerHead->id,
            'target_user_id' => $this->signerDirector->id,
            'document_id' => $document->id,
            'requested_signature_id' => $stamp->id,
            'status' => 'pending',
            'notified_at' => null,
        ]);

        app(ApprovalRoutingService::class)->compileWorkflowFromSignatures($document, $version, $this->signerHead);

        // 1. Verify DocumentApprovalRequested is sent to signerDirector exactly ONCE
        Notification::assertSentToTimes(
            $this->signerDirector,
            DocumentApprovalRequested::class,
            1
        );

        // 2. Verify duplicate SignatureRequested is NOT sent
        Notification::assertNotSentTo(
            $this->signerDirector,
            SignatureRequested::class
        );

        // 3. Verify notification payload contents
        $notification = new DocumentApprovalRequested($document, $version, $this->signerHead->name);
        $data = $notification->toArray($this->signerDirector);

        $this->assertEquals(__('Permintaan Persetujuan & Stempel Perusahaan'), $data['title']);
        $this->assertTrue($data['has_signature']);
        $this->assertTrue($data['is_stamp']);
        $this->assertEquals('stamp', $data['icon']);
        $this->assertEquals('stamp', $data['request_type']);
        $this->assertEquals($this->company->name, $data['company_name']);
        $this->assertStringContainsString('TESS STAMP', $data['message']);
    }

    public function test_consecutive_workflow_compilations_do_not_dispatch_duplicate_notifications(): void
    {
        $document = Document::create([
            'title' => 'GATAU YA',
            'document_number' => '008/SK-04/MMC-DHU/IX/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->author->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'status' => 'pending',
            'content' => '<p>Pending Doc</p>',
        ]);

        $routingService = app(ApprovalRoutingService::class);

        // First compile (e.g. from ONLYOFFICE save status 2)
        $routingService->compileWorkflowFromSignatures($document, $version, $this->author);

        // Second compile immediately after (e.g. from finish-editing controller)
        $routingService->compileWorkflowFromSignatures($document, $version, $this->author);

        // Verify that signerHead received only 1 DocumentApprovalRequested notification in database
        $notifCount = $this->signerHead->notifications()
            ->where('type', DocumentApprovalRequested::class)
            ->count();

        $this->assertSame(1, $notifCount);

        // Verify that notification index API endpoint returns exactly 1 item and unread_count = 1
        $response = $this->actingAs($this->signerHead)->getJson(route('notifications.index'));
        $response->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $this->assertCount(1, $response->json('notifications'));
    }
}
