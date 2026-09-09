<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Notifications\DocumentApprovalResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BroadcastingResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_with_broadcast_channel_succeeds_even_when_websocket_server_is_offline(): void
    {
        // Notice: We do NOT use Notification::fake() here on purpose.
        // We want to test the actual notification pipeline including the broadcast channel.

        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);

        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'Author User']);
        $reviewer = User::factory()->create(['division_id' => $division->id, 'name' => 'Reviewer Head', 'system_role' => 'head']);

        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP']);
        $document = Document::create([
            'document_number' => '001/FIN/SOP/2026',
            'title' => 'Financial SOP',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'division_id' => $division->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'general',
        ]);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'content' => '<p>Initial Content</p>',
        ]);

        // Sending a notification that uses ['database', 'broadcast']
        // Must NOT throw BroadcastException or any Throwable even if Reverb server is offline
        $notification = new DocumentApprovalResult(
            $document,
            $version,
            'approved',
            $reviewer->name,
            'Approved cleanly'
        );

        $author->notify($notification);

        // Database notification was successfully written
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $author->id,
            'type' => DocumentApprovalResult::class,
        ]);

        $dbNotification = $author->notifications()->first();
        $this->assertNotNull($dbNotification);
        $this->assertSame('approval_result', $dbNotification->data['type'] ?? null);
        $this->assertSame('approval', $dbNotification->data['icon'] ?? null);
    }

    public function test_controller_approval_flow_succeeds_without_broadcast_exception_when_real_notifications_sent(): void
    {
        $company = Company::create(['name' => 'PT Test', 'code' => 'TEST']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);

        $author = User::factory()->create(['division_id' => $division->id, 'name' => 'Author User']);
        $author->companies()->attach($company->id);
        $author->branches()->attach($branch->id);

        $reviewer = User::factory()->create([
            'division_id' => $division->id,
            'name' => 'Reviewer Head',
            'system_role' => 'head',
        ]);
        $reviewer->companies()->attach($company->id);
        $reviewer->branches()->attach($branch->id);

        $docType = DocumentType::create(['name' => 'Standard SOP', 'code' => 'SOP']);
        $document = Document::create([
            'document_number' => '002/FIN/SOP/2026',
            'title' => 'Invoice Policy',
            'document_type_id' => $docType->id,
            'owner_id' => $author->id,
            'division_id' => $division->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'visibility' => 'division',
            'approver_role' => 'head',
        ]);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
            'content' => '<p>Pending version</p>',
        ]);

        // Simulates clicking Approve button on the approvals page
        $response = $this->actingAs($reviewer)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
                'active_division_id' => $division->id,
            ])
            ->post(route('approvals.approve', [$document, $version]), [
                'notes' => 'Approved successfully via controller',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Document version is now active
        $this->assertSame('active', $version->fresh()->status);

        // Notification received by author in DB
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $author->id,
            'type' => DocumentApprovalResult::class,
        ]);
    }
}
