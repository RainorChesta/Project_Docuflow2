<?php

namespace Tests\Feature;

use App\Models\UnitKerja;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\DocumentUnitKerjaShare;
use App\Models\DocumentShare;
use App\Notifications\DocumentAccessRevoked;
use App\Notifications\DocumentOpenedByGrantedUser;
use App\Notifications\DocumentSharedWithUnitKerja;
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
    protected UnitKerja $unitKerja;
    protected DocumentType $docType;
    protected Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $company = \App\Models\Company::create(['name' => 'PT Jaya', 'code' => 'JBM']);
        $branch = \App\Models\Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'IT Unit', 'kode_unit_kerja' => '01']);
        $this->docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED', 'category' => 'naskah_dinas']);

        $this->owner = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja->id,
            'name' => 'Owner User',
            'is_active' => true,
        ]);
        $this->owner->companies()->sync([$company->id]);
        $this->owner->branches()->sync([$branch->id]);

        $this->recipient = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja->id,
            'name' => 'Recipient User',
            'is_active' => true,
        ]);
        $this->recipient->companies()->sync([$company->id]);
        $this->recipient->branches()->sync([$branch->id]);

        $this->document = Document::create([
            'title' => 'Important Policy Document',
            'document_number' => '001/S.ED/JBM/VIII/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->owner->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
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

    public function test_unit_kerja_members_receive_notification_when_unit_kerja_granted_access(): void
    {
        Notification::fake();

        $financeUnit = UnitKerja::create(['nama_unit_kerja' => 'Finance Unit', 'kode_unit_kerja' => '05']);
        $financeUser = User::factory()->create([
            'unit_kerja_id' => $financeUnit->id,
            'name' => 'Finance Staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post(route('shares.store', $this->document), [
            'type' => 'unit_kerja',
            'unit_kerja_id' => $financeUnit->id,
            'role' => 'viewer',
        ]);

        $response->assertRedirect();

        Notification::assertSentTo(
            $financeUser,
            DocumentSharedWithUnitKerja::class,
            function (DocumentSharedWithUnitKerja $notification) use ($financeUnit) {
                return $notification->document->id === $this->document->id
                    && $notification->unitKerjaName === $financeUnit->nama_unit_kerja
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
                    && $notification->unitKerjaName === null;
            }
        );
    }

    public function test_unit_kerja_members_receive_notification_when_unit_kerja_access_is_revoked(): void
    {
        Notification::fake();

        $financeUnit = UnitKerja::create(['nama_unit_kerja' => 'Finance Unit', 'kode_unit_kerja' => '05']);
        $financeUser = User::factory()->create([
            'unit_kerja_id' => $financeUnit->id,
            'name' => 'Finance Staff',
            'is_active' => true,
        ]);

        $unitKerjaShare = DocumentUnitKerjaShare::create([
            'document_id' => $this->document->id,
            'unit_kerja_id' => $financeUnit->id,
            'role' => 'viewer',
            'invited_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->delete(route('shares.unit-kerja.destroy', [$this->document, $unitKerjaShare]));
        $response->assertRedirect();

        Notification::assertSentTo(
            $financeUser,
            DocumentAccessRevoked::class,
            function (DocumentAccessRevoked $notification) use ($financeUnit) {
                return $notification->document->id === $this->document->id
                    && $notification->revokedByName === $this->owner->name
                    && $notification->unitKerjaName === $financeUnit->nama_unit_kerja;
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
            'document_number' => '002/S.ED/JBM/VIII/2026',
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->owner->id,
            'document_type_id' => $this->docType->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);
        $this->recipient->notify(new DocumentSharedWithUser($doc2, 'viewer', $this->owner->name));

        // Now should count as 2
        $this->assertSame(2, $this->recipient->sharedDocumentsCount());
    }

    public function test_user_sees_shared_and_revoked_notifications_in_notification_endpoint_across_branches(): void
    {
        // Setup recipient in different branch/company context
        $company2 = \App\Models\Company::create(['name' => 'PT Berbeda', 'code' => 'PBD']);
        $branch2 = \App\Models\Branch::create(['company_id' => $company2->id, 'name' => 'Cabang 2', 'is_pusat' => false]);
        $recipient2 = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja->id,
            'name' => 'Cross Branch Recipient',
            'is_active' => true,
        ]);
        $recipient2->companies()->sync([$company2->id]);
        $recipient2->branches()->sync([$branch2->id]);

        // 1. Owner shares document with recipient2
        $shareResponse = $this->actingAs($this->owner)->post(route('shares.store', $this->document), [
            'type' => 'user',
            'user_id' => $recipient2->id,
            'role' => 'editor',
        ]);
        $shareResponse->assertRedirect();

        // Recipient2 in branch2 context fetches notifications
        $notifResp = $this->actingAs($recipient2)->getJson(route('notifications.index'));
        $notifResp->assertOk();
        $notifResp->assertJsonPath('unread_count', 1);
        $notifResp->assertJsonFragment(['type' => 'document_shared']);

        // Unread count endpoint
        $countResp = $this->actingAs($recipient2)->getJson(route('notifications.unread-count'));
        $countResp->assertOk();
        $countResp->assertJsonPath('unread_count', 1);

        // 2. Owner revokes access from recipient2
        $share = DocumentShare::where('document_id', $this->document->id)->where('user_id', $recipient2->id)->firstOrFail();
        $deleteResp = $this->actingAs($this->owner)->delete(route('shares.destroy', [$this->document, $share]));
        $deleteResp->assertRedirect();

        // Recipient2 fetches notifications again -> should see document_access_revoked
        $revokedNotifResp = $this->actingAs($recipient2)->getJson(route('notifications.index'));
        $revokedNotifResp->assertOk();
        $revokedNotifResp->assertJsonPath('unread_count', 1);
        $revokedNotifResp->assertJsonFragment(['type' => 'document_access_revoked']);

        $countResp2 = $this->actingAs($recipient2)->getJson(route('notifications.unread-count'));
        $countResp2->assertOk();
        $countResp2->assertJsonPath('unread_count', 1);
    }
}
