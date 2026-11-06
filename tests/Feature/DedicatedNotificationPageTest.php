<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DocumentApprovalRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DedicatedNotificationPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Document $document;
    protected DocumentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::create(['name' => 'CMH Group', 'code' => 'CMH']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'HQ', 'code' => 'HQ']);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Finance', 'kode_unit_kerja' => '05']);
        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP', 'category' => 'akreditasi']);

        $this->user = User::factory()->create([
            'unit_kerja_id' => $unitKerja->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->user->branches()->attach($branch->id);
        $this->user->companies()->attach($company->id);

        $this->document = Document::create([
            'title' => 'SOP Testing',
            'document_number' => '060/SOP-05/HQ/IX/2026',
            'unit_kerja_id' => $unitKerja->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'owner_id' => $this->user->id,
            'document_type_id' => $docType->id,
            'visibility' => Document::VISIBILITY_GENERAL,
        ]);

        $this->version = $this->document->versions()->create([
            'version_number' => 1,
            'content' => 'Initial content',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_view_dedicated_notification_page(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));

        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertSee(__('Daftar Notifikasi'));
        $response->assertSee('SOP Testing');
    }

    public function test_user_can_mark_single_notification_as_read_via_http(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));
        $notification = $this->user->unreadNotifications()->first();

        $this->assertNotNull($notification);

        $response = $this->actingAs($this->user)->post(route('notifications.read', $notification->id));

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_bulk_mark_notifications_as_read(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));
        $notification1 = $this->user->unreadNotifications()->first();

        $response = $this->actingAs($this->user)->post(route('notifications.bulk-read'), [
            'ids' => [$notification1->id],
        ]);

        $response->assertRedirect();
        $this->assertNotNull($notification1->fresh()->read_at);
    }

    public function test_user_can_delete_single_notification(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));
        $notification = $this->user->notifications()->first();

        $this->assertNotNull($notification);

        $response = $this->actingAs($this->user)->delete(route('notifications.destroy', $notification->id));

        $response->assertRedirect();
        $this->assertNull($this->user->notifications()->find($notification->id));
    }

    public function test_user_can_bulk_delete_notifications(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));
        $notification = $this->user->notifications()->first();

        $this->assertNotNull($notification);

        $response = $this->actingAs($this->user)->delete(route('notifications.bulk-delete'), [
            'ids' => [$notification->id],
        ]);

        $response->assertRedirect();
        $this->assertNull($this->user->notifications()->find($notification->id));
    }

    public function test_user_can_delete_all_notifications(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));
        $this->assertCount(1, $this->user->notifications);

        $response = $this->actingAs($this->user)->delete(route('notifications.delete-all'));

        $response->assertRedirect();
        $this->assertCount(0, $this->user->fresh()->notifications);
    }

    public function test_notification_bell_json_api_remains_functional(): void
    {
        $this->user->notify(new DocumentApprovalRequested($this->document, $this->version, $this->user));

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'notifications',
            'unread_count',
        ]);
        $response->assertJson([
            'unread_count' => 1,
        ]);
    }
}
