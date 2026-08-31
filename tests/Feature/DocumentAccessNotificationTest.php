<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\DocumentDivisionShare;
use App\Models\DocumentShare;
use App\Notifications\DocumentAccessRevoked;
use App\Notifications\DocumentOpenedByGrantedUser;
use App\Notifications\DocumentSharedWithDivision;
use App\Notifications\DocumentSharedWithUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentAccessNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $recipient;
    protected Division $division;
    protected DocumentType $docType;
    protected Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $company = \App\Models\Company::create(['name' => 'PT Jaya', 'code' => 'JBM']);
        $branch = \App\Models\Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->division = Division::create(['name' => 'IT Division', 'code' => 'IT']);
        $this->docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);

        $this->owner = User::factory()->create([
            'division_id' => $this->division->id,
            'name' => 'Owner User',
            'is_active' => true,
        ]);
        $this->owner->companies()->sync([$company->id]);
        $this->owner->branches()->sync([$branch->id]);

        $this->recipient = User::factory()->create([
            'division_id' => $this->division->id,
            'name' => 'Recipient User',
            'is_active' => true,
        ]);
        $this->recipient->companies()->sync([$company->id]);
        $this->recipient->branches()->sync([$branch->id]);

        $this->document = Document::create([
            'title' => 'Important Policy Document',
            'document_number' => '001/S.ED/IT/JBM/VIII/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'division_id' => $this->division->id,
            'owner_id' => $this->owner->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);
    }

    public function test_user_receives_notification_when_granted_access_to_document(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)->post(route('shares.store', $this->document), [
            'type' => 'user',
            'user_id' => $this->recipient->id,
            'role' => 'editor',
        ]);

        $response->assertRedirect();

        Notification::assertSentTo(
            $this->recipient,
            DocumentSharedWithUser::class,
            function (DocumentSharedWithUser $notification) {
                return $notification->document->id === $this->document->id
                    && $notification->role === 'editor'
                    && $notification->sharedByName === $this->owner->name;
            }
        );
    }

    public function test_division_members_receive_notification_when_division_granted_access(): void
    {
        Notification::fake();

        $financeDiv = Division::create(['name' => 'Finance Division', 'code' => 'FIN']);
        $financeUser = User::factory()->create([
            'division_id' => $financeDiv->id,
            'name' => 'Finance Staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post(route('shares.store', $this->document), [
            'type' => 'division',
            'division_id' => $financeDiv->id,
            'role' => 'viewer',
        ]);

        $response->assertRedirect();

        Notification::assertSentTo(
            $financeUser,
            DocumentSharedWithDivision::class,
            function (DocumentSharedWithDivision $notification) use ($financeDiv) {
                return $notification->document->id === $this->document->id
                    && $notification->divisionName === $financeDiv->name
                    && $notification->role === 'viewer';
            }
        );
    }

    public function test_user_receives_notification_when_access_is_revoked(): void
    {
        Notification::fake();

        $share = DocumentShare::create([
            'document_id' => $this->document->id,
            'user_id' => $this->recipient->id,
            'role' => 'viewer',
            'invited_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('shares.destroy', [$this->document, $share]));
        $response->assertRedirect();

        Notification::assertSentTo(
            $this->recipient,
            DocumentAccessRevoked::class,
            function (DocumentAccessRevoked $notification) {
                return $notification->document->id === $this->document->id
                    && $notification->revokedByName === $this->owner->name
                    && $notification->divisionName === null;
            }
        );
    }

    public function test_division_members_receive_notification_when_division_access_is_revoked(): void
    {
        Notification::fake();

        $financeDiv = Division::create(['name' => 'Finance Division', 'code' => 'FIN']);
        $financeUser = User::factory()->create([
            'division_id' => $financeDiv->id,
            'name' => 'Finance Staff',
            'is_active' => true,
        ]);

        $divisionShare = DocumentDivisionShare::create([
            'document_id' => $this->document->id,
            'division_id' => $financeDiv->id,
            'role' => 'viewer',
            'invited_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('shares.division.destroy', [$this->document, $divisionShare]));
        $response->assertRedirect();

        Notification::assertSentTo(
            $financeUser,
            DocumentAccessRevoked::class,
            function (DocumentAccessRevoked $notification) use ($financeDiv) {
                return $notification->document->id === $this->document->id
                    && $notification->revokedByName === $this->owner->name
                    && $notification->divisionName === $financeDiv->name;
            }
        );
    }

    public function test_owner_receives_notification_when_granted_user_opens_document(): void
    {
        Notification::fake();

        // Grant access to recipient
        $this->actingAs($this->owner)->post(route('shares.store', $this->document), [
            'type' => 'user',
            'user_id' => $this->recipient->id,
            'role' => 'viewer',
        ]);

        // Recipient opens the document
        $response = $this->actingAs($this->recipient)->get(route('documents.show', $this->document));
        $response->assertStatus(200);

        Notification::assertSentTo(
            $this->owner,
            DocumentOpenedByGrantedUser::class,
            function (DocumentOpenedByGrantedUser $notification) {
                return $notification->document->id === $this->document->id
                    && $notification->viewerName === $this->recipient->name;
            }
        );
    }

    public function test_owner_opening_own_document_does_not_trigger_notification(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)->get(route('documents.show', $this->document));
        $response->assertStatus(200);

        Notification::assertNothingSent();
    }

    public function test_subsequent_views_within_throttle_window_do_not_send_duplicate_notifications(): void
    {
        Notification::fake();

        // Grant access
        $this->actingAs($this->owner)->post(route('shares.store', $this->document), [
            'type' => 'user',
            'user_id' => $this->recipient->id,
            'role' => 'viewer',
        ]);

        // First open
        $this->actingAs($this->recipient)->get(route('documents.show', $this->document));

        // Second open immediately (within 15 min throttle window)
        $this->actingAs($this->recipient)->get(route('documents.show', $this->document));

        Notification::assertSentToTimes($this->owner, DocumentOpenedByGrantedUser::class, 1);
    }

    public function test_shared_documents_count_counts_same_document_once_even_with_multiple_access_grants(): void
    {
        // 1st grant: viewer
        $this->recipient->notify(new DocumentSharedWithUser($this->document, 'viewer', $this->owner->name));

        // 2nd grant: editor for the same document
        $this->recipient->notify(new DocumentSharedWithUser($this->document, 'editor', $this->owner->name));

        // Should count as 1 because both are for the same document
        $this->assertSame(1, $this->recipient->sharedDocumentsCount());

        // Now share a second distinct document
        $doc2 = Document::create([
            'title' => 'Second Policy Document',
            'document_number' => '002/S.ED/IT/JBM/VIII/2026',
            'division_id' => $this->division->id,
            'owner_id' => $this->owner->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);
        $this->recipient->notify(new DocumentSharedWithUser($doc2, 'viewer', $this->owner->name));

        // Now should count as 2
        $this->assertSame(2, $this->recipient->sharedDocumentsCount());
    }
}
