<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Setting;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DocumentExpiredNotification;
use App\Notifications\DocumentExpiringWarningNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminDocumentExpirationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->company = Company::create(['name' => 'PT Test Corp', 'code' => 'TEST']);
        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'Kantor Pusat', 'is_pusat' => true]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Unit SDM & Umum', 'kode_unit_kerja' => 'SDM']);
        $this->docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK', 'category' => 'naskah_dinas']);

        $this->admin = User::factory()->create([
            'system_role' => 'admin',
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $this->admin->companies()->sync([$this->company->id]);
        $this->admin->branches()->sync([$this->branch->id]);

        $this->staff = User::factory()->create([
            'system_role' => 'staff',
            'unit_kerja_id' => $this->unitKerja->id,
        ]);
        $this->staff->companies()->sync([$this->company->id]);
        $this->staff->branches()->sync([$this->branch->id]);
    }

    public function test_non_admin_cannot_access_expiration_management_page(): void
    {
        $response = $this->actingAs($this->staff)->get(route('admin.expirations.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_expiration_management_page_and_default_reminder(): void
    {
        Setting::set('document_expiration_reminder_days', 30);

        // Document with expiration
        $docExpiring = Document::create([
            'title' => 'Kontrak Sewa Gedung',
            'document_number' => '001/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(20)->toDateString(),
            'is_expired' => false,
        ]);

        // Permanent document (without expiration) -> should be excluded from expiration page
        $docPermanent = Document::create([
            'title' => 'SOP Kebijakan Perusahaan Permanen',
            'document_number' => '002/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => null,
            'is_expired' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.expirations.index'));
        $response->assertOk();
        $response->assertViewIs('admin.expirations.index');
        $response->assertSee('Kontrak Sewa Gedung');
        $response->assertDontSee('SOP Kebijakan Perusahaan Permanen');
        $response->assertSee('30');
    }

    public function test_admin_can_update_default_expiration_reminder_period(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.expirations.update-default'), [
            'default_reminder_days' => 45,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(45, (int) Setting::get('document_expiration_reminder_days'));
    }

    public function test_admin_can_configure_custom_reminder_for_specific_document(): void
    {
        $doc = Document::create([
            'title' => 'Perjanjian Khusus Vendor',
            'document_number' => '003/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(60)->toDateString(),
            'is_expired' => false,
        ]);

        // Set custom 15 days reminder
        $response = $this->actingAs($this->admin)->put(route('admin.expirations.update-document-reminder', $doc), [
            'expiration_reminder_days' => 15,
            'use_default_reminder' => 0,
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals(15, $doc->expiration_reminder_days);
        $this->assertEquals(15, $doc->effective_reminder_days);

        // Switch back to default
        $response2 = $this->actingAs($this->admin)->put(route('admin.expirations.update-document-reminder', $doc), [
            'use_default_reminder' => 1,
        ]);

        $response2->assertRedirect();
        $doc->refresh();
        $this->assertNull($doc->expiration_reminder_days);
        $this->assertEquals(30, $doc->effective_reminder_days);
    }

    public function test_admin_cannot_configure_reminder_days_exceeding_expiration_period(): void
    {
        // Document expires in 30 days
        $doc = Document::create([
            'title' => 'Kontrak Sewa 30 Hari',
            'document_number' => '004/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(30)->toDateString(),
            'is_expired' => false,
        ]);

        // Trying to set reminder to 35 days (exceeds 30 days) -> should fail validation
        $response = $this->actingAs($this->admin)->put(route('admin.expirations.update-document-reminder', $doc), [
            'expiration_reminder_days' => 35,
            'use_default_reminder' => 0,
        ]);

        $response->assertSessionHasErrors('expiration_reminder_days');
        $doc->refresh();
        $this->assertNotEquals(35, $doc->expiration_reminder_days);

        // Trying via JSON endpoint -> 422 Unprocessable Entity
        $jsonResponse = $this->actingAs($this->admin)->putJson(route('admin.expirations.update-document-reminder', $doc), [
            'expiration_reminder_days' => 40,
            'use_default_reminder' => 0,
        ]);

        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJsonValidationErrors('expiration_reminder_days');

        // Setting reminder within allowed limit (30 days or less) -> should succeed
        $validResponse = $this->actingAs($this->admin)->put(route('admin.expirations.update-document-reminder', $doc), [
            'expiration_reminder_days' => 30,
            'use_default_reminder' => 0,
        ]);

        $validResponse->assertRedirect();
        $validResponse->assertSessionHasNoErrors();
        $doc->refresh();
        $this->assertEquals(30, $doc->expiration_reminder_days);
    }

    public function test_admin_can_dispatch_manual_notification_to_document_owner(): void
    {
        Notification::fake();

        $doc = Document::create([
            'title' => 'Sertifikat Lisensi Perangkat',
            'document_number' => '005/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(10)->toDateString(),
            'is_expired' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.expirations.notify', $doc));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Notification::assertSentTo($this->staff, DocumentExpiringWarningNotification::class);

        $doc->refresh();
        $this->assertTrue($doc->is_expiration_notified);
        $this->assertNotNull($doc->expiration_notified_at);
    }

    public function test_permanent_document_creation_has_null_expiration_and_no_notifications(): void
    {
        Notification::fake();

        // Create document without expiration date
        $doc = Document::create([
            'title' => 'Surat Keputusan Permanen Tanpa Kedaluwarsa',
            'document_number' => '009/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'expiration_date' => null,
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Permanent Doc</p>',
            'author_name' => $this->staff->name,
            'status' => 'active',
            'file_path' => 'docs/perm.docx',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        $this->assertFalse($doc->hasExpiration());
        $this->assertFalse($doc->isExpired());
        $this->assertFalse($doc->isExpiringSoon());
        $this->assertNull($doc->daysUntilExpiration());

        $this->artisan('app:check-document-expiration')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_configure_modal_endpoint_does_not_modify_document_expiration_date(): void
    {
        $originalExpDate = now()->addDays(40)->toDateString();

        $doc = Document::create([
            'title' => 'Dokumen Masa Berlaku Tetap',
            'document_number' => '011/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => $originalExpDate,
            'is_expired' => false,
        ]);

        // Attempting to pass a different expiration date via update-document-reminder
        $response = $this->actingAs($this->admin)->put(route('admin.expirations.update-document-reminder', $doc), [
            'expiration_reminder_days' => 10,
            'use_default_reminder' => 0,
            'expiration_date' => now()->addDays(90)->toDateString(),
        ]);

        $response->assertRedirect();
        $doc->refresh();

        // Reminder days updated, but expiration_date MUST remain the original fixed date
        $this->assertEquals(10, $doc->expiration_reminder_days);
        $this->assertEquals($originalExpDate, $doc->expiration_date->toDateString());
    }

    public function test_switching_from_default_to_custom_reminder_triggers_custom_notification_when_within_window(): void
    {
        Notification::fake();

        // Document created with default expiration (30 days threshold) with 20 days remaining
        $doc = Document::create([
            'title' => 'Dokumen Beralih ke Pengingat Khusus',
            'document_number' => '022/SK/TEST/X/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staff->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'status' => 'released',
            'expiration_date' => now()->addDays(20)->toDateString(),
            'expiration_reminder_days' => null, // Default
            'is_expired' => false,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Version 1</p>',
            'author_name' => $this->staff->name,
            'status' => 'active',
            'file_path' => 'docs/test22.pdf',
        ]);
        $doc->update(['current_version_id' => $version->id]);

        // Default 30-day notification was sent
        Notification::assertSentToTimes($this->staff, DocumentExpiringWarningNotification::class, 1);
        $this->assertEquals('30days', $doc->fresh()->expiration_notif_status);

        // Admin now switches from default to custom reminder period of 20 days
        $response = $this->actingAs($this->admin)->put(route('admin.expirations.update-document-reminder', $doc), [
            'expiration_reminder_days' => 20,
            'use_default_reminder' => 0,
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals(20, $doc->expiration_reminder_days);

        // Custom 20-day notification is triggered and sent
        Notification::assertSentToTimes($this->staff, DocumentExpiringWarningNotification::class, 2);
        $this->assertEquals('20days', $doc->expiration_notif_status);

        // Running daily scheduler right after does NOT duplicate
        $this->artisan('app:check-document-expiration')->assertSuccessful();
        Notification::assertSentToTimes($this->staff, DocumentExpiringWarningNotification::class, 2);
    }
}
