<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\ApprovalRouteResolved;
use App\Notifications\DocumentApprovalRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationUrlSanitizationTest extends TestCase
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
        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);
        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP']);

        $this->user = User::factory()->create([
            'division_id' => $division->id,
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->user->branches()->attach($branch->id);
        $this->user->companies()->attach($company->id);

        $this->document = Document::create([
            'title' => 'ssnsn',
            'document_number' => '060/SOP/FIN/HQ/IX/2026',
            'division_id' => $division->id,
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

    public function test_document_approval_requested_notification_uses_relative_url()
    {
        $notification = new DocumentApprovalRequested($this->document, $this->version, 'rehan');
        $data = $notification->toArray($this->user);

        $this->assertEquals('/documents/' . $this->document->id, $data['url']);
        $this->assertStringNotContainsString('host.docker.internal', $data['url']);
    }

    public function test_approval_route_resolved_notification_uses_relative_url()
    {
        $notification = new ApprovalRouteResolved($this->document, 'head', 'Approver Name', 'Routing message', false);
        $data = $notification->toArray($this->user);

        $this->assertEquals('/documents/' . $this->document->id, $data['url']);
        $this->assertStringNotContainsString('host.docker.internal', $data['url']);
    }

    public function test_notification_controller_normalizes_legacy_host_docker_internal_urls()
    {
        // Insert legacy notification with host.docker.internal into DB
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => DocumentApprovalRequested::class,
            'data' => [
                'type' => 'approval_request',
                'title' => 'Permintaan Persetujuan Dokumen',
                'message' => 'rehan mengajukan dokumen "ssnsn" (v1) untuk persetujuan.',
                'url' => 'http://host.docker.internal:8000/documents/' . $this->document->id,
                'icon' => 'approval',
                'document_id' => $this->document->id,
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $notifications = $response->json('notifications');

        $this->assertNotEmpty($notifications);
        $this->assertEquals('/documents/' . $this->document->id, $notifications[0]['url']);
        $this->assertStringNotContainsString('host.docker.internal', $notifications[0]['url']);
    }

    public function test_onlyoffice_callback_forces_root_url_even_when_request_host_is_docker_internal()
    {
        config(['app.url' => 'http://localhost:8000']);

        // Simulate request coming from Docker with Host: host.docker.internal:8000
        $request = \Illuminate\Http\Request::create(
            'http://host.docker.internal:8000/api/onlyoffice/callback?document_id=' . $this->document->id,
            'POST',
            ['status' => 1] // status 1: document being edited (no token needed)
        );

        $controller = app(\App\Http\Controllers\OnlyOfficeController::class);
        $response = $controller->callback($request, $this->document);

        $this->assertEquals(200, $response->getStatusCode());
        // URL::to('/') should be forced to config('app.url')
        $this->assertEquals('http://localhost:8000', url('/'));
        $this->assertStringNotContainsString('host.docker.internal', url('/'));
    }
}
