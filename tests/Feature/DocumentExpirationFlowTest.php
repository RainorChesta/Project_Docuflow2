<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DocumentExpiredNotification;
use App\Notifications\DocumentExpiringWarningNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentExpirationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK', 'category' => 'naskah_dinas']);
    }

    public function test_document_without_expiration_is_excluded_from_recaps_and_notifications(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit SDM', 'kode_unit_kerja' => 'SDM']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $sharedUser = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $sharedUser->companies()->sync([$this->company->id]);
        $sharedUser->branches()->sync([$this->branch->id]);

        $unit->update(['pic_user_id' => $owner->id]);

        $doc = Document::create([
            'title' => 'Dokumen Tanpa Expired',
            'document_number' => '001/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => null,
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Test content</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        DocumentShare::create([
            'document_id' => $doc->id,
            'user_id' => $sharedUser->id,
            'role' => 'viewer',
        ]);

        $this->assertFalse($doc->hasExpiration());
        $this->assertFalse($doc->isExpired());
        $this->assertFalse($doc->isExpiringSoon());

        $this->artisan('app:check-document-expiration')->assertSuccessful();

        Notification::assertNothingSent();

        $response = $this->actingAs($owner)->get(route('dashboard'))->assertOk();
        $ownerExpiringDocs = $response->viewData('ownerExpiringDocs') ?? collect();
        $this->assertFalse($ownerExpiringDocs->contains('id', $doc->id));
        $this->assertEquals(0, $ownerExpiringDocs->count());
    }

    public function test_document_with_expiration_notifies_only_owner(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit Keuangan', 'kode_unit_kerja' => 'KEU']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $sharedUser = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $sharedUser->companies()->sync([$this->company->id]);
        $sharedUser->branches()->sync([$this->branch->id]);

        // Document with 45 days remaining -> should NOT trigger notification (since default reminder is 30 days)
        $doc45 = Document::create([
            'title' => 'Dokumen H-45',
            'document_number' => '045/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(45)->toDateString(),
            'is_expired' => false,
        ]);
        DocumentVersion::create([
            'document_id' => $doc45->id,
            'version_number' => 1,
            'content' => '<p>Test 45</p>',
            'author_name' => 'Admin',
            'status' => 'active',
            'file_path' => 'docs/test45.pdf',
        ]);
        $doc45->update(['current_version_id' => 1]);

        $this->artisan('app:check-document-expiration')->assertSuccessful();
        Notification::assertNothingSent();

        // Document with 5 days remaining (H-7 window) -> triggers H-7 notification to owner only
        $doc = Document::create([
            'title' => 'Kontrak Kerjasama Segera Habis',
            'document_number' => '002/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(5)->toDateString(),
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Test content 2</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test2.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        DocumentShare::create([
            'document_id' => $doc->id,
            'user_id' => $sharedUser->id,
            'role' => 'viewer',
        ]);

        $this->artisan('app:check-document-expiration')->assertSuccessful();

        Notification::assertSentTo($owner, DocumentExpiringWarningNotification::class);
        Notification::assertNotSentTo($sharedUser, DocumentExpiringWarningNotification::class);
    }

    public function test_document_triggers_notification_immediately_upon_release_within_h7_window(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit Pelayanan', 'kode_unit_kerja' => 'YAN']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Dokumen Langsung Rilis H-7',
            'document_number' => '077/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(7)->toDateString(),
            'is_expired' => false,
        ]);

        // Creating active version marks document as released -> immediately triggers notification
        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Direct Release</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test77.pdf',
        ]);

        // Notification is sent immediately without running artisan command
        Notification::assertSentTo($owner, DocumentExpiringWarningNotification::class);
    }

    public function test_expired_document_sends_expired_notification_to_owner(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit Logistik', 'kode_unit_kerja' => 'LOG']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Perjanjian Sewa Kadaluwarsa',
            'document_number' => '004/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->subDay()->toDateString(),
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Test content 4</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test4.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        $this->artisan('app:check-document-expiration')->assertSuccessful();

        $doc->refresh();
        $this->assertTrue($doc->is_expired);
        $this->assertTrue($doc->isExpired());

        Notification::assertSentTo($owner, DocumentExpiredNotification::class);
    }

    public function test_shared_user_and_pic_see_recap_on_dashboard(): void
    {
        $picUser = User::factory()->create(['system_role' => 'staff']);
        $picUser->companies()->sync([$this->company->id]);
        $picUser->branches()->sync([$this->branch->id]);

        $unit = UnitKerja::create([
            'nama_unit_kerja' => 'Unit Operasional',
            'kode_unit_kerja' => 'OPS',
            'pic_user_id' => $picUser->id,
        ]);
        
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $sharedUser = User::factory()->create(['system_role' => 'staff']);
        $sharedUser->companies()->sync([$this->company->id]);
        $sharedUser->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Dokumen Berjangka Khusus',
            'document_number' => '003/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(10)->toDateString(),
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Test content 3</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test3.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        DocumentShare::create([
            'document_id' => $doc->id,
            'user_id' => $sharedUser->id,
            'role' => 'viewer',
        ]);

        // Shared user gets recap on dashboard
        $sharedResponse = $this->actingAs($sharedUser)->get(route('dashboard'))->assertOk();
        $sharedExpiringDocs = $sharedResponse->viewData('sharedExpiringDocs');
        $this->assertTrue($sharedExpiringDocs->contains('id', $doc->id));

        // PIC Unit Kerja gets recap on dashboard
        $picResponse = $this->actingAs($picUser)->get(route('dashboard'))->assertOk();
        $picExpiringDocs = $picResponse->viewData('picExpiringDocs');
        $this->assertTrue($picExpiringDocs->contains('id', $doc->id));
    }

    public function test_default_expiration_notification_is_sent_only_once_during_lifecycle(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit SDM & Umum', 'kode_unit_kerja' => 'SDMU']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Dokumen Default Expired 20 Hari',
            'document_number' => '020/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(20)->toDateString(),
            'expiration_reminder_days' => null, // Uses default global setting (30 days)
            'is_expired' => false,
        ]);

        // Creating active version (triggers DocumentVersion saved event)
        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Version 1</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test20.pdf',
        ]);

        // Updating document pointer (triggers Document saved event)
        $doc->update(['current_version_id' => $version->id]);

        // Further document touch/save
        $doc->touch();

        // Running daily scheduler command
        $this->artisan('app:check-document-expiration')->assertSuccessful();

        // Notification must have been sent EXACTLY ONCE, not duplicated
        Notification::assertSentToTimes($owner, DocumentExpiringWarningNotification::class, 1);
    }

    public function test_subsequent_saves_or_scheduler_runs_do_not_retrigger_default_expiration_notification(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit Legal', 'kode_unit_kerja' => 'LEG']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Dokumen Legal 25 Hari',
            'document_number' => '025/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(25)->toDateString(),
            'expiration_reminder_days' => null,
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Version 1</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test25.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        // Re-saving the document multiple times (e.g. updating other attributes)
        $doc->update(['title' => 'Dokumen Legal 25 Hari Updated']);
        $doc->update(['paper_size' => 'Letter']);

        // Running artisan command twice
        $this->artisan('app:check-document-expiration')->assertSuccessful();
        $this->artisan('app:check-document-expiration')->assertSuccessful();

        // Must still be sent only 1 time
        Notification::assertSentToTimes($owner, DocumentExpiringWarningNotification::class, 1);
    }

    public function test_manual_direct_send_notification_is_not_duplicated_by_scheduler(): void
    {
        Notification::fake();

        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit Operasional', 'kode_unit_kerja' => 'OPS']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $admin = User::factory()->create(['system_role' => 'admin']);
        $admin->companies()->sync([$this->company->id]);
        $admin->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Dokumen Kirim Langsung 45 Hari',
            'document_number' => '021/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(45)->toDateString(),
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Content</p>',
            'author_name' => 'Admin User',
            'status' => 'active',
            'file_path' => 'docs/test21.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        // Direct send by admin
        $this->actingAs($admin)->post(route('admin.expirations.notify', $doc))->assertRedirect();

        // Running daily scheduler command right after
        $this->artisan('app:check-document-expiration')->assertSuccessful();

        // Exactly 1 notification sent (direct send), scheduler does not duplicate
        Notification::assertSentToTimes($owner, DocumentExpiringWarningNotification::class, 1);
    }

    public function test_notification_controller_deduplicates_unread_expiration_notifications(): void
    {
        $unit = UnitKerja::create(['nama_unit_kerja' => 'Unit Pelayanan 2', 'kode_unit_kerja' => 'YAN2']);
        $owner = User::factory()->create(['unit_kerja_id' => $unit->id, 'system_role' => 'staff']);
        $owner->companies()->sync([$this->company->id]);
        $owner->branches()->sync([$this->branch->id]);

        $doc = Document::create([
            'title' => 'Dokumen Notif Duplikasi UI',
            'document_number' => '099/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $owner->id,
            'unit_kerja_id' => $unit->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(20)->toDateString(),
            'is_expired' => false,
        ]);

        // Simulate 2 unread database notifications of the same warning type for the same document
        $owner->notify(new DocumentExpiringWarningNotification($doc, 20, '30days'));
        $owner->notify(new DocumentExpiringWarningNotification($doc, 20, '30days'));

        $this->assertEquals(2, $owner->unreadNotifications()->count());

        // NotificationController index() and unreadCount() should deduplicate to 1
        $response = $this->actingAs($owner)->getJson(route('notifications.index'))->assertOk();
        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);

        $unreadResponse = $this->actingAs($owner)->getJson(route('notifications.unread-count'))->assertOk();
        $this->assertEquals(1, $unreadResponse->json('unread_count'));
    }
}
